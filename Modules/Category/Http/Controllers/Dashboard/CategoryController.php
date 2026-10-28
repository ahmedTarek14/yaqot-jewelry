<?php

namespace Modules\Category\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AdminListing;
use App\Support\ImageUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Category\Http\Requests\CategoryRequest;
use Modules\Category\Models\Category;

class CategoryController extends Controller
{
    /**
     * Sortable columns => the SQL column (anything else falls back to the default sort).
     */
    private const SORTABLE = [
        'name' => null, // translated: ordered through the current locale's translation
        'products_count' => 'products_count',
        'sort_order' => 'categories.sort_order',
        'updated_at' => 'categories.updated_at',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $listing = AdminListing::fromRequest($request, array_keys(self::SORTABLE), 'sort_order', 'asc', [
            'status' => ['active', 'inactive'],
        ]);

        $query = Category::query()->with('translations');

        if ($pattern = $listing->likePattern()) {
            $query->whereHas('translations', fn (Builder $translation) => $translation
                ->whereRaw("name like ? escape '!'", [$pattern]));
        }

        if ($status = $listing->filter('status')) {
            $query->where('categories.is_active', $status === 'active');
        }

        if ($listing->sort === 'name') {
            $query->orderByTranslation('name', $listing->direction);
        } else {
            $query->orderBy(self::SORTABLE[$listing->sort], $listing->direction);
        }

        $categories = $query->withCount('products')
            ->orderBy('categories.id')
            ->paginate(config('catalog.admin.per_page'))
            ->withQueryString();

        // A page past the end (e.g. after deleting the last row on it) goes to the last page.
        if ($categories->isEmpty() && $categories->currentPage() > 1) {
            return redirect()->to($categories->url($categories->lastPage()));
        }

        return view('category::dashboard.index', compact('categories', 'listing'));
    }

    public function create(): View
    {
        return view('category::dashboard.form', [
            'category' => new Category(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(CategoryRequest $request, ImageUploader $images): JsonResponse
    {
        $this->save(new Category, $request, $images);

        return $this->savedResponse(route('admin.categories.index'));
    }

    public function edit(Category $category): View
    {
        return view('category::dashboard.form', ['category' => $category->load('translations')]);
    }

    public function update(CategoryRequest $request, Category $category, ImageUploader $images): JsonResponse
    {
        $this->save($category->load('translations'), $request, $images);

        return $this->savedResponse(route('admin.categories.index'));
    }

    public function toggleActive(Category $category): JsonResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return $this->toggledResponse($category->is_active, __('admin.status.active'), __('admin.status.inactive'));
    }

    /**
     * A category with products (trashed ones included) throws ModelInUseException,
     * which redirects back with the reason and the product counts.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->back(fallback: route('admin.categories.index'))
            ->with('success', __('admin.messages.deleted'));
    }

    /**
     * The new image (if any) is stored first; ImageUploader removes it again if the
     * transaction fails. A replaced or removed image is deleted after commit (HasPublicImage).
     */
    private function save(Category $category, CategoryRequest $request, ImageUploader $images): void
    {
        $images->storeWhile($request->file('image'), 'categories', function (?string $path) use ($category, $request) {
            DB::transaction(function () use ($category, $request, $path) {
                $category->fill($request->safe()->only(['is_active', 'sort_order']));

                if ($path !== null) {
                    $category->image_path = $path;
                } elseif ($request->boolean('remove_image')) {
                    $category->image_path = null;
                }

                foreach (config('translatable.locales') as $locale) {
                    $translation = $category->translateOrNew($locale);
                    $translation->name = $request->validated("name_$locale");
                    $translation->description = $request->validated("description_$locale");
                }

                $category->save();

                // A change to the translations alone does not update the row itself.
                $category->wasRecentlyCreated || $category->wasChanged() || $category->touch();
            });
        });
    }
}
