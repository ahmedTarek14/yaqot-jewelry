<?php

namespace Modules\Auth\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin login/logout. AuthenticatesUsers (laravel/ui) provides validation,
 * session regeneration and login throttling (5 attempts per minute).
 */
class AuthController extends Controller
{
    use AuthenticatesUsers;

    public function showLoginForm()
    {
        return view('auth::login');
    }

    public function redirectTo(): string
    {
        return route('admin.dashboard');
    }

    protected function guard(): StatefulGuard
    {
        return Auth::guard('admin');
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('admin.login');
    }
}
