<?php

namespace Modules\Auth\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

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

    /**
     * The page a guest was bounced from (url.intended) keeps the locale it was
     * requested in. If they switched language on the login page, send them on
     * in the language they logged in with.
     */
    protected function authenticated(Request $request, $user)
    {
        if ($intended = $request->session()->get('url.intended')) {
            $request->session()->put('url.intended', LaravelLocalization::getLocalizedURL(app()->getLocale(), $intended));
        }
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('admin.login');
    }
}
