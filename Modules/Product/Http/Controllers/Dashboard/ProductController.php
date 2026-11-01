<?php

namespace Modules\Product\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AdminListing;
use App\Support\ImageUploader;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Http\Requests\ProductRequest;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductImage;

class ProductController extends Controller
{
    /**
     * Sortable columns => the SQL column (anything else falls back to the default sort).
     */
    private const SORTABLE = [
        'name' => null, // translated: ordered through the current locale's translation
        'sku' => 'products.sku',
        'updated_at' => 'products.updated_at',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $categories = $this->names(Category::class);
        $collections = $this->names(Collection::class);
        $brands = $this->names(Brand::class);

        $listing = AdminListing::fromRequest($request, array_keys(self::SORTABLE), 'updated_at', 'desc', [
            'status' => ['published', 'draft'],
            'category' => array_map('strval', array_keys($categories)),
            'collection' => array_map('strval', array_keys($collections)),
            'brand' => [...array_map('strval', array_keys($brands)), 'none'],
            'karat' => array_map('strval', config('catalog.karats')),
            'featured' => ['yes', 'no'],
            'trashed' => ['only', 'with'],
        ]);

        $query = Product::query()->with(['translations', 'primaryImage']);

        match ($listing->filter('trashed')) {
            'only' => $query->onlyTrashed(),
            'with' => $query->withTrashed(),
            default => null,
        };

        if ($pattern = $listing->likePattern()) {
            $query->where(fn (Builder $search) => $search
                ->whereHas('translations', fn (Builder $translation) => $translation
                    ->whereRaw("name like ? escape '!'", [$pattern]))
                ->orWhereRaw("products.sku like ? escape '!'", [$pattern]));
        }

        if ($status = $listing->filter('status')) {
            $query->where('products.is_published', $status === 'published');
        }

        if ($featured = $listing->filter('featured')) {
            $query->where('products.is_featured', $featured === 'yes');
        }

        if ($category = $listing->filter('category')) {
            $query->whereHas('categories', fn (Builder $related) => $related->where('categories.id', (int) $category));
        }

        if ($collection = $listing->filter('collection')) {
            $query->whereHas('collections', fn (Builder $related) => $related->where('collections.id', (int) $collection));
        }

        if ($brand = $listing->filter('brand')) {
            $brand === 'none'
                ? $query->whereNull('products.brand_id')
                : $query->where('products.brand_id', (int) $brand);
        }

        if ($karat = $listing->filter('karat')) {
            $query->where('products.karat', (int) $karat);
        }

        if ($listing->sort === 'name') {
            $query->orderByTranslation('name', $listing->direction);
        } else {
            $query->orderBy(self::SORTABLE[$listing->sort], $listing->direction);
        }

        $products = $query->orderBy('products.id', $listing->direction)
            ->paginate(config('catalog.admin.per_page'))
            ->withQueryString();

        // A page past the end (e.g. after deleting the last row on it) goes to the last page.
        if ($products->isEmpty() && $products->currentPage() > 1) {
            return redirect()->to($products->url($products->lastPage()));
        }

        return view('product::dashboard.index', [
            'products' => $products,
            'listing' => $listing,
            'categories' => $categories,
            'collections' => $collections,
            'brands' => $brands,
            'karats' => $this->optionLabels('karats'),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Product(['is_published' => false, 'is_featured' => false]));
    }

    public function store(ProductRequest $request, ImageUploader $images): JsonResponse
    {
        $this->save(new Product, $request, $images);

        return $this->savedResponse(route('admin.products.index'));
    }

    public function edit(Product $product): View
    {
        return $this->form($product->load(['translations', 'images', 'categories:id', 'collections:id']));
    }

    public function update(ProductRequest $request, Product $product, ImageUploader $images): JsonResponse
    {
        $this->save($product->load('translations'), $request, $images);

        return $this->savedResponse(route('admin.products.index'));
    }

    public function togglePublished(Product $product): JsonResponse
    {
        // The model stamps published_at the first time a product is published.
        $product->update(['is_published' => ! $product->is_published]);

        return $this->toggledResponse($product->is_published, __('admin.status.published'), __('admin.status.draft'));
    }

    public function toggleFeatured(Product $product): JsonResponse
    {
        $product->update(['is_featured' => ! $product->is_featured]);

        return $this->toggledResponse(
            $product->is_featured,
            __('product::product.featured.on'),
            __('product::product.featured.off'),
        );
    }

    /**
     * Soft delete: the row, its images and their files stay until a force delete.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->back(fallback: route('admin.products.index'))
            ->with('success', __('product::product.messages.trashed'));
    }

    public function restore(Product $product): RedirectResponse
    {
        $product->restore();

        return redirect()->back(fallback: route('admin.products.index'))
            ->with('success', __('admin.messages.restored'));
    }

    /**
     * Only a product in the trash can be deleted permanently. Its translations, image rows
     * and category/collection links go with the row (FK cascade); the model deletes the
     * image files once the transaction has committed.
     */
    public function forceDestroy(Product $product): RedirectResponse
    {
        abort_unless($product->trashed(), 404);

        DB::transaction(fn () => $product->forceDelete());

        return redirect()->back(fallback: route('admin.products.index', ['trashed' => 'only']))
            ->with('success', __('product::product.messages.deleted_permanently'));
    }

    private function form(Product $product): View
    {
        return view('product::dashboard.form', [
            'product' => $product,
            'images' => $product->exists ? $product->images : [],
            'brands' => $this->names(Brand::class),
            'categories' => $this->names(Category::class),
            'collections' => $this->names(Collection::class),
            'selectedCategories' => $product->exists ? $product->categories->modelKeys() : [],
            'selectedCollections' => $product->exists ? $product->collections->modelKeys() : [],
            'karats' => $this->optionLabels('karats'),
            'goldColors' => $this->optionLabels('gold_colors'),
            'stoneTypes' => $this->optionLabels('stone_types'),
            // Public URLs use the slug, not the id; a new product has no page yet.
            'viewUrl' => $product->exists ? route('front.products.show', $product->slug) : null,
        ]);
    }

    /**
     * Every record of a translated model as id => name in the current locale, in its admin order.
     *
     * @param  class-string<Brand|Category|Collection>  $model
     * @return array<int, string>
     */
    private function names(string $model): array
    {
        return $model::query()->with('translations')->ordered()->get()
            ->mapWithKeys(fn ($record) => [$record->id => (string) $record->name])
            ->all();
    }

    /**
     * A config('catalog.*') allowlist as value => translated label (the raw value when untranslated).
     *
     * @return array<int|string, string>
     */
    private function optionLabels(string $list): array
    {
        return collect(config("catalog.$list"))
            ->mapWithKeys(fn ($value) => [$value => Lang::has("product::product.$list.$value")
                ? __("product::product.$list.$value")
                : (string) $value])
            ->all();
    }

    /**
     * New files are stored inside the transaction once the product id exists; if anything
     * throws, ImageUploader removes them and the transaction rolls back. Removed images
     * delete their files after commit (HasPublicImage).
     */
    private function save(Product $product, ProductRequest $request, ImageUploader $images): void
    {
        $images->storeDuring(function (Closure $store) use ($product, $request) {
            DB::transaction(function () use ($product, $request, $store) {
                $attributes = $request->safe()->only([
                    'sku', 'brand_id', 'karat', 'gold_color', 'stone_type', 'weight_grams', 'is_published', 'is_featured',
                ]);
                // Validated as an integer string; the model's allowlist guard compares integers.
                $attributes['karat'] = isset($attributes['karat']) ? (int) $attributes['karat'] : null;

                $product->fill($attributes);

                foreach (config('translatable.locales') as $locale) {
                    $translation = $product->translateOrNew($locale);
                    $translation->name = $request->validated("name_$locale");
                    $translation->description = $request->validated("description_$locale");
                }

                $product->save();

                // Translations, categories, collections and images alone do not update the row itself.
                $product->wasRecentlyCreated || $product->wasChanged() || $product->touch();

                $product->categories()->sync($request->validated('categories', []));
                $product->collections()->sync($request->validated('collections', []));

                $this->saveImages($product, $request, $store);
            });
        });
    }

    /**
     * Applies the gallery: removals, then the final order from the images_order tokens
     * ("id:{id}" = a saved image, "new:{n}" = images[n]). Saved images and new files
     * the tokens do not mention (e.g. without JS) follow in their current order.
     *
     * @param  Closure(UploadedFile, string): string  $store
     */
    private function saveImages(Product $product, ProductRequest $request, Closure $store): void
    {
        $saved = $product->images()->get()->keyBy('id');

        foreach ($request->validated('remove_images', []) as $id) {
            $saved->pull((int) $id)?->delete();
        }

        $files = $request->file('images', []);
        $files = is_array($files) ? $files : [];

        $ordered = [];
        $usedSaved = [];
        $usedNew = [];

        foreach ($request->validated('images_order', []) as $token) {
            [$kind, $key] = explode(':', $token, 2);
            $key = (int) $key;

            if ($kind === 'id' && $saved->has($key) && ! isset($usedSaved[$key])) {
                $usedSaved[$key] = true;
                $ordered[] = $saved->get($key);
            } elseif ($kind === 'new' && isset($files[$key]) && ! isset($usedNew[$key])) {
                $usedNew[$key] = true;
                $ordered[] = $files[$key];
            }
        }

        foreach ($saved as $id => $image) {
            if (! isset($usedSaved[$id])) {
                $ordered[] = $image;
            }
        }

        foreach ($files as $key => $file) {
            if (! isset($usedNew[$key])) {
                $ordered[] = $file;
            }
        }

        foreach ($ordered as $position => $item) {
            if ($item instanceof ProductImage) {
                if ($item->sort_order !== $position) {
                    $item->update(['sort_order' => $position]);
                }
            } else {
                $product->images()->create([
                    'path' => $store($item, "products/{$product->id}"),
                    'sort_order' => $position,
                ]);
            }
        }
    }
}
