<?php

namespace Modules\Brand\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AdminListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Brand\Http\Requests\BrandRequest;
use Modules\Brand\Models\Brand;

class BrandController extends Controller
{
    /**
     * Sortable columns => the SQL column (anything else falls back to the default sort).
     */
    private const SORTABLE = [
        'name' => null, // translated: ordered through the current locale's translation
        'products_count' => 'products_count',
        'sort_order' => 'brands.sort_order',
        'updated_at' => 'brands.updated_at',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $listing = AdminListing::fromRequest($request, array_keys(self::SORTABLE), 'sort_order', 'asc', [
            'status' => ['active', 'inactive'],
        ]);

        $query = Brand::query()->with('translations');

        if ($pattern = $listing->likePattern()) {
            $query->whereHas('translations', fn (Builder $translation) => $translation
                ->whereRaw("name like ? escape '!'", [$pattern]));
        }

        if ($status = $listing->filter('status')) {
            $query->where('brands.is_active', $status === 'active');
        }

        if ($listing->sort === 'name') {
            $query->orderByTranslation('name', $listing->direction);
        } else {
            $query->orderBy(self::SORTABLE[$listing->sort], $listing->direction);
        }

        // products_count leaves out trashed products; the delete guard still counts them.
        $brands = $query->withCount('products')
            ->orderBy('brands.id')
            ->paginate(config('catalog.admin.per_page'))
            ->withQueryString();

        // A page past the end (e.g. after deleting the last row on it) goes to the last page.
        if ($brands->isEmpty() && $brands->currentPage() > 1) {
            return redirect()->to($brands->url($brands->lastPage()));
        }

        return view('brand::dashboard.index', compact('brands', 'listing'));
    }

    public function create(): View
    {
        return view('brand::dashboard.form', [
            'brand' => new Brand(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(BrandRequest $request): JsonResponse
    {
        $this->save(new Brand, $request);

        return $this->savedResponse(route('admin.brands.index'));
    }

    public function edit(Brand $brand): View
    {
        return view('brand::dashboard.form', ['brand' => $brand->load('translations')]);
    }

    public function update(BrandRequest $request, Brand $brand): JsonResponse
    {
        $this->save($brand->load('translations'), $request);

        return $this->savedResponse(route('admin.brands.index'));
    }

    public function toggleActive(Brand $brand): JsonResponse
    {
        $brand->update(['is_active' => ! $brand->is_active]);

        return $this->toggledResponse($brand->is_active, __('admin.status.active'), __('admin.status.inactive'));
    }

    /**
     * A brand with products (trashed ones included) throws ModelInUseException,
     * which redirects back with the reason and the product counts.
     */
    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()->back(fallback: route('admin.brands.index'))
            ->with('success', __('admin.messages.deleted'));
    }

    private function save(Brand $brand, BrandRequest $request): void
    {
        DB::transaction(function () use ($brand, $request) {
            $brand->fill($request->safe()->only(['is_active', 'sort_order']));

            foreach (config('translatable.locales') as $locale) {
                $brand->translateOrNew($locale)->name = $request->validated("name_$locale");
            }

            $brand->save();

            // A change to the translations alone does not update the row itself.
            $brand->wasRecentlyCreated || $brand->wasChanged() || $brand->touch();
        });
    }
}
