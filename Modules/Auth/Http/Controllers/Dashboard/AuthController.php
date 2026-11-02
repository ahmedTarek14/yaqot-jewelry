<?php

namespace Modules\Auth\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Auth\Models\Admin;

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
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        if ($intended = $request->session()->get('url.intended')) {
            $request->session()->put('url.intended', LaravelLocalization::getLocalizedURL(app()->getLocale(), $intended));
        }
    }

    /**
     * A deactivated admin is never authenticated (ActiveAdminProvider), so the attempt fails
     * like any other. Only when the password is right is the reason revealed, so the message
     * does not tell anyone whether an email exists. The attempt has already been counted by
     * the throttle at this point.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $email = mb_strtolower(trim((string) $request->input($this->username())));
        $admin = Admin::query()->where('email', $email)->first();

        if ($admin && ! $admin->is_active && Hash::check((string) $request->input('password'), $admin->password)) {
            throw ValidationException::withMessages([$this->username() => [__('auth::admins.login.deactivated')]]);
        }

        throw ValidationException::withMessages([$this->username() => [trans('auth.failed')]]);
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('admin.login');
    }
}
