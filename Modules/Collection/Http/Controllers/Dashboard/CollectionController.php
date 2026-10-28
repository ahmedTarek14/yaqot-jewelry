<?php

namespace Modules\Collection\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AdminListing;
use App\Support\ImageUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Collection\Http\Requests\CollectionRequest;
use Modules\Collection\Models\Collection;

class CollectionController extends Controller
{
    /**
     * Sortable columns => the SQL column (anything else falls back to the default sort).
     */
    private const SORTABLE = [
        'name' => null, // translated: ordered through the current locale's translation
        'products_count' => 'products_count',
        'sort_order' => 'collections.sort_order',
        'updated_at' => 'collections.updated_at',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $listing = AdminListing::fromRequest($request, array_keys(self::SORTABLE), 'sort_order', 'asc', [
            'status' => ['active', 'inactive'],
            'homepage' => ['yes', 'no'],
        ]);

        $query = Collection::query()->with('translations');

        if ($pattern = $listing->likePattern()) {
            $query->whereHas('translations', fn (Builder $translation) => $translation
                ->whereRaw("name like ? escape '!'", [$pattern]));
        }

        if ($status = $listing->filter('status')) {
            $query->where('collections.is_active', $status === 'active');
        }

        if ($homepage = $listing->filter('homepage')) {
            $query->where('collections.show_on_homepage', $homepage === 'yes');
        }

        if ($listing->sort === 'name') {
            $query->orderByTranslation('name', $listing->direction);
        } else {
            $query->orderBy(self::SORTABLE[$listing->sort], $listing->direction);
        }

        $collections = $query->withCount('products')
            ->orderBy('collections.id')
            ->paginate(config('catalog.admin.per_page'))
            ->withQueryString();

        // A page past the end (e.g. after deleting the last row on it) goes to the last page.
        if ($collections->isEmpty() && $collections->currentPage() > 1) {
            return redirect()->to($collections->url($collections->lastPage()));
        }

        return view('collection::dashboard.index', compact('collections', 'listing'));
    }

    public function create(): View
    {
        return view('collection::dashboard.form', [
            'collection' => new Collection(['is_active' => true, 'show_on_homepage' => false, 'sort_order' => 0]),
        ]);
    }

    public function store(CollectionRequest $request, ImageUploader $images): JsonResponse
    {
        $this->save(new Collection, $request, $images);

        return $this->savedResponse(route('admin.collections.index'));
    }

    public function edit(Collection $collection): View
    {
        return view('collection::dashboard.form', ['collection' => $collection->load('translations')]);
    }

    public function update(CollectionRequest $request, Collection $collection, ImageUploader $images): JsonResponse
    {
        $this->save($collection->load('translations'), $request, $images);

        return $this->savedResponse(route('admin.collections.index'));
    }

    public function toggleActive(Collection $collection): JsonResponse
    {
        $collection->update(['is_active' => ! $collection->is_active]);

        return $this->toggledResponse($collection->is_active, __('admin.status.active'), __('admin.status.inactive'));
    }

    public function toggleHomepage(Collection $collection): JsonResponse
    {
        $collection->update(['show_on_homepage' => ! $collection->show_on_homepage]);

        return $this->toggledResponse(
            $collection->show_on_homepage,
            __('collection::collection.homepage.on'),
            __('collection::collection.homepage.off'),
        );
    }

    /**
     * A collection with products (trashed ones included) throws ModelInUseException,
     * which redirects back with the reason and the product counts.
     */
    public function destroy(Collection $collection): RedirectResponse
    {
        $collection->delete();

        return redirect()->back(fallback: route('admin.collections.index'))
            ->with('success', __('admin.messages.deleted'));
    }

    /**
     * The new image (if any) is stored first; ImageUploader removes it again if the
     * transaction fails. A replaced or removed image is deleted after commit (HasPublicImage).
     */
    private function save(Collection $collection, CollectionRequest $request, ImageUploader $images): void
    {
        $images->storeWhile($request->file('image'), 'collections', function (?string $path) use ($collection, $request) {
            DB::transaction(function () use ($collection, $request, $path) {
                $collection->fill($request->safe()->only(['is_active', 'show_on_homepage', 'sort_order']));

                if ($path !== null) {
                    $collection->image_path = $path;
                } elseif ($request->boolean('remove_image')) {
                    $collection->image_path = null;
                }

                foreach (config('translatable.locales') as $locale) {
                    $translation = $collection->translateOrNew($locale);
                    $translation->name = $request->validated("name_$locale");
                    $translation->description = $request->validated("description_$locale");
                }

                $collection->save();

                // A change to the translations alone does not update the row itself.
                $collection->wasRecentlyCreated || $collection->wasChanged() || $collection->touch();
            });
        });
    }
}
