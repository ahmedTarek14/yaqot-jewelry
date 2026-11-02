<?php

namespace Modules\Option\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AdminListing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Option\Http\Requests\OptionRequest;
use Modules\Option\Models\ProductOption;
use Modules\Product\Models\Product;

/**
 * Admin CRUD shared by the two option groups (gold colours, stone types). A subclass only
 * names its group and its route prefix; every query and the `{option}` binding are scoped
 * to that group, so an id from the other group is a 404.
 */
abstract class OptionController extends Controller
{
    /**
     * Sortable columns => the SQL column (anything else falls back to the default sort).
     */
    private const SORTABLE = [
        'name' => null, // translated: ordered through the current locale's translation
        'products_count' => 'products_count',
        'sort_order' => 'product_options.sort_order',
        'updated_at' => 'product_options.updated_at',
    ];

    /** One of the ProductOption group constants. */
    abstract public function group(): string;

    /** The route name prefix, e.g. 'admin.gold-colors'. */
    abstract protected function routeBase(): string;

    public function index(Request $request): View|RedirectResponse
    {
        $listing = AdminListing::fromRequest($request, array_keys(self::SORTABLE), 'sort_order', 'asc', [
            'status' => ['active', 'inactive'],
        ]);

        $query = ProductOption::query()->inGroup($this->group())->select('product_options.*')->with('translations');

        if ($pattern = $listing->likePattern()) {
            $query->whereHas('translations', fn (Builder $translation) => $translation
                ->whereRaw("name like ? escape '!'", [$pattern]));
        }

        if ($status = $listing->filter('status')) {
            $query->where('product_options.is_active', $status === 'active');
        }

        if ($listing->sort === 'name') {
            $query->orderByTranslation('name', $listing->direction);
        } else {
            $query->orderBy(self::SORTABLE[$listing->sort], $listing->direction);
        }

        // products_count leaves out trashed products (the delete guard still counts them).
        // The relation depends on the group, so the count is built from the group's column here.
        $count = Product::query()->selectRaw('count(*)')
            ->whereColumn('products.'.ProductOption::COLUMNS[$this->group()], 'product_options.id');

        $options = $query->addSelect(['products_count' => $count])
            ->orderBy('product_options.id')
            ->paginate(config('catalog.admin.per_page'))
            ->withQueryString();

        // A page past the end (e.g. after deleting the last row on it) goes to the last page.
        if ($options->isEmpty() && $options->currentPage() > 1) {
            return redirect()->to($options->url($options->lastPage()));
        }

        return view('option::dashboard.index', [
            'options' => $options,
            'listing' => $listing,
        ] + $this->viewData());
    }

    public function create(): View
    {
        return view('option::dashboard.form', [
            'option' => new ProductOption(['group' => $this->group(), 'is_active' => true, 'sort_order' => 0]),
        ] + $this->viewData());
    }

    public function store(OptionRequest $request): JsonResponse
    {
        $this->save(new ProductOption(['group' => $this->group()]), $request);

        return $this->savedResponse(route($this->routeBase().'.index'));
    }

    public function edit(string $option): View
    {
        return view('option::dashboard.form', [
            'option' => $this->find($option)->load('translations'),
        ] + $this->viewData());
    }

    public function update(OptionRequest $request, string $option): JsonResponse
    {
        $this->save($this->find($option)->load('translations'), $request);

        return $this->savedResponse(route($this->routeBase().'.index'));
    }

    public function toggleActive(string $option): JsonResponse
    {
        $option = $this->find($option);
        $option->update(['is_active' => ! $option->is_active]);

        return $this->toggledResponse($option->is_active, __('admin.status.active'), __('admin.status.inactive'));
    }

    /**
     * An option used by any product (trashed ones included) throws ModelInUseException,
     * which redirects back with the reason and the product counts.
     */
    public function destroy(string $option): RedirectResponse
    {
        $this->find($option)->delete();

        return redirect()->back(fallback: route($this->routeBase().'.index'))
            ->with('success', __('admin.messages.deleted'));
    }

    private function find(string $id): ProductOption
    {
        return ProductOption::query()->inGroup($this->group())->findOrFail($id);
    }

    /**
     * @return array{group: string, routes: string, lang: string}
     */
    private function viewData(): array
    {
        return [
            'group' => $this->group(),
            'routes' => $this->routeBase(),
            'lang' => 'option::option.'.$this->group(),
        ];
    }

    private function save(ProductOption $option, OptionRequest $request): void
    {
        DB::transaction(function () use ($option, $request) {
            $option->fill($request->safe()->only(['is_active', 'sort_order']));

            foreach (config('translatable.locales') as $locale) {
                $option->translateOrNew($locale)->name = $request->validated("name_$locale");
            }

            $option->save();

            // A change to the translations alone does not update the row itself.
            $option->wasRecentlyCreated || $option->wasChanged() || $option->touch();
        });
    }
}
