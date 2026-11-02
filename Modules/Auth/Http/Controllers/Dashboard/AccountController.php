<?php

namespace Modules\Auth\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Auth\Http\Middleware\EndStaleAdminSession;
use Modules\Auth\Http\Requests\AccountRequest;
use Modules\Auth\Models\Admin;

/**
 * "My account": the signed-in admin edits their own name, email and password.
 */
class AccountController extends Controller
{
    public function edit(): View
    {
        return view('auth::dashboard.account', ['admin' => $this->admin()]);
    }

    public function update(AccountRequest $request): JsonResponse
    {
        $guard = Auth::guard('admin');
        $admin = $this->admin();
        $remembered = $guard->viaRemember();

        $admin->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $admin->password = $request->validated('password');
            // Other browsers' "remember me" cookies stop working; this session stays valid.
            $admin->setRememberToken(Str::random(60));
        }

        $admin->save();

        if ($request->filled('password')) {
            $request->session()->regenerate();
            // This session stays valid; every other one of this admin ends.
            $request->session()->put(EndStaleAdminSession::SESSION_KEY, EndStaleAdminSession::fingerprint($admin));

            if ($remembered) {
                $guard->login($admin, true);
            }
        }

        return $this->savedResponse(route('admin.account.edit'));
    }

    private function admin(): Admin
    {
        return Auth::guard('admin')->user();
    }
}
