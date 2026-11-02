<?php

namespace Modules\Auth\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AdminListing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Auth\Http\Requests\AdminRequest;
use Modules\Auth\Models\Admin;
use Modules\Auth\Support\AdminGuard;
use Modules\Auth\Support\AdminSafeguardException;

/**
 * Admin users. Every admin has equal rights. Deactivating or deleting goes through
 * AdminGuard inside a transaction (not yourself, never the last active admin).
 */
class AdminController extends Controller
{
    /**
     * Sortable columns => the SQL column (anything else falls back to the default sort).
     */
    private const SORTABLE = [
        'name' => 'admins.name',
        'email' => 'admins.email',
        'is_active' => 'admins.is_active',
        'last_login_at' => 'admins.last_login_at',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        $listing = AdminListing::fromRequest($request, array_keys(self::SORTABLE), 'name', 'asc', [
            'status' => ['active', 'inactive'],
        ]);

        $query = Admin::query();

        if ($pattern = $listing->likePattern()) {
            $query->where(fn ($search) => $search
                ->whereRaw("admins.name like ? escape '!'", [$pattern])
                ->orWhereRaw("admins.email like ? escape '!'", [$pattern]));
        }

        if ($status = $listing->filter('status')) {
            $query->where('admins.is_active', $status === 'active');
        }

        $admins = $query->orderBy(self::SORTABLE[$listing->sort], $listing->direction)
            ->orderBy('admins.id')
            ->paginate(config('catalog.admin.per_page'))
            ->withQueryString();

        // A page past the end (e.g. after deleting the last row on it) goes to the last page.
        if ($admins->isEmpty() && $admins->currentPage() > 1) {
            return redirect()->to($admins->url($admins->lastPage()));
        }

        return view('auth::dashboard.index', compact('admins', 'listing'));
    }

    public function create(): View
    {
        return view('auth::dashboard.form', ['admin' => new Admin]);
    }

    public function store(AdminRequest $request): JsonResponse
    {
        $admin = new Admin($request->safe()->only(['name', 'email', 'password']));
        $admin->is_active = true;
        $admin->save();

        return $this->savedResponse(route('admin.admins.index'));
    }

    public function edit(Admin $admin): View|RedirectResponse
    {
        // Your own name, email and password live on the "My account" page.
        if ($admin->is($this->actor())) {
            return redirect()->route('admin.account.edit');
        }

        return view('auth::dashboard.form', ['admin' => $admin]);
    }

    public function update(AdminRequest $request, Admin $admin): JsonResponse
    {
        $actor = $this->actor();
        $active = $request->boolean('is_active');

        // Editing yourself here would skip the current-password check of "My account",
        // so only the refusal to deactivate yourself is answered; anything else is not allowed.
        if ($admin->is($actor)) {
            if (! $active) {
                throw AdminSafeguardException::self('deactivate')->asValidation('is_active');
            }

            abort(403);
        }

        try {
            DB::transaction(function () use ($request, $admin, $actor, $active) {
                $activeIds = AdminGuard::lock($admin);

                if ($admin->is_active && ! $active) {
                    AdminGuard::ensureCanLoseAccess($admin, $actor, $activeIds, 'deactivate');
                }

                $admin->fill($request->safe()->only(['name', 'email']));
                $admin->is_active = $active;

                if ($request->filled('password')) {
                    $admin->password = $request->validated('password');
                    $admin->setRememberToken(Str::random(60));
                }

                $admin->save();
            });
        } catch (AdminSafeguardException $refusal) {
            throw $refusal->asValidation('is_active');
        }

        return $this->savedResponse(route('admin.admins.index'));
    }

    public function toggleActive(Admin $admin): JsonResponse
    {
        $actor = $this->actor();

        DB::transaction(function () use ($admin, $actor) {
            $activeIds = AdminGuard::lock($admin);

            if ($admin->is_active) {
                AdminGuard::ensureCanLoseAccess($admin, $actor, $activeIds, 'deactivate');
            }

            $admin->is_active = ! $admin->is_active;
            $admin->save();
        });

        return $this->toggledResponse($admin->is_active, __('admin.status.active'), __('admin.status.inactive'));
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        $actor = $this->actor();

        DB::transaction(function () use ($admin, $actor) {
            $activeIds = AdminGuard::lock($admin);

            AdminGuard::ensureCanLoseAccess($admin, $actor, $activeIds, 'delete');

            $admin->delete();
        });

        return redirect()->back(fallback: route('admin.admins.index'))
            ->with('success', __('admin.messages.deleted'));
    }

    private function actor(): Admin
    {
        return Auth::guard('admin')->user();
    }
}
