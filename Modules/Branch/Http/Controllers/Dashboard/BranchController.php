<?php

namespace Modules\Branch\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AdminListing;
use App\Support\EgyptianPhone;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Branch\Http\Requests\BranchRequest;
use Modules\Branch\Models\Branch;

class BranchController extends Controller
{
    /**
     * Sortable columns => the SQL column (anything else falls back to the default sort).
     */
    private const SORTABLE = [
        'name' => null, // translated: ordered through the current locale's translation
        'sort_order' => 'branches.sort_order',
        'updated_at' => 'branches.updated_at',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $listing = AdminListing::fromRequest($request, array_keys(self::SORTABLE), 'sort_order', 'asc', [
            'status' => ['active', 'inactive'],
        ]);

        $query = Branch::query()->with('translations');

        // Matches the name in either language, or the stored phone number, also when the
        // search is a full number typed in another form (+20 2 2345 6789 → 0223456789).
        if ($pattern = $listing->likePattern()) {
            $phone = EgyptianPhone::normalizePhone($listing->search);

            $query->where(fn (Builder $where) => $where
                ->whereHas('translations', fn (Builder $translation) => $translation
                    ->whereRaw("name like ? escape '!'", [$pattern]))
                ->orWhereRaw("branches.phone like ? escape '!'", [$pattern])
                ->when($phone, fn (Builder $where) => $where->orWhere('branches.phone', $phone)));
        }

        if ($status = $listing->filter('status')) {
            $query->where('branches.is_active', $status === 'active');
        }

        if ($listing->sort === 'name') {
            $query->orderByTranslation('name', $listing->direction);
        } else {
            $query->orderBy(self::SORTABLE[$listing->sort], $listing->direction);
        }

        $branches = $query->orderBy('branches.id')
            ->paginate(config('catalog.admin.per_page'))
            ->withQueryString();

        // A page past the end (e.g. after deleting the last row on it) goes to the last page.
        if ($branches->isEmpty() && $branches->currentPage() > 1) {
            return redirect()->to($branches->url($branches->lastPage()));
        }

        return view('branch::dashboard.index', compact('branches', 'listing'));
    }

    public function create(): View
    {
        return view('branch::dashboard.form', [
            'branch' => new Branch(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(BranchRequest $request): JsonResponse
    {
        $this->save(new Branch, $request);

        return $this->savedResponse(route('admin.branches.index'));
    }

    public function edit(Branch $branch): View
    {
        return view('branch::dashboard.form', ['branch' => $branch->load('translations')]);
    }

    public function update(BranchRequest $request, Branch $branch): JsonResponse
    {
        $this->save($branch->load('translations'), $request);

        return $this->savedResponse(route('admin.branches.index'));
    }

    public function toggleActive(Branch $branch): JsonResponse
    {
        $branch->update(['is_active' => ! $branch->is_active]);

        return $this->toggledResponse($branch->is_active, __('admin.status.active'), __('admin.status.inactive'));
    }

    /**
     * Branches have no products, so there is nothing to guard; translations cascade.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->delete();

        return redirect()->back(fallback: route('admin.branches.index'))
            ->with('success', __('admin.messages.deleted'));
    }

    private function save(Branch $branch, BranchRequest $request): void
    {
        DB::transaction(function () use ($branch, $request) {
            $branch->fill([
                ...$request->safe()->only(['is_active', 'sort_order']),
                'map_url' => $request->validated('map_url'),
                'phone' => EgyptianPhone::normalizePhone($request->validated('phone')),
                'whatsapp' => EgyptianPhone::normalizeMobile($request->validated('whatsapp')),
            ]);

            foreach (config('translatable.locales') as $locale) {
                $translation = $branch->translateOrNew($locale);
                $translation->name = $request->validated("name_$locale");
                $translation->address = $request->validated("address_$locale");
                $translation->working_hours = $request->validated("working_hours_$locale");
            }

            $branch->save();

            // A change to the translations alone does not update the row itself.
            $branch->wasRecentlyCreated || $branch->wasChanged() || $branch->touch();
        });
    }
}
