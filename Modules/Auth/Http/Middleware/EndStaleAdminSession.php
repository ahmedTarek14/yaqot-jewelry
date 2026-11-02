<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Models\Admin;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends every admin session that was opened before the admin's password last changed
 * (laravel's AuthenticateSession only watches the default guard). The password hash
 * is remembered in the session on the first request after login; a mismatch logs the
 * session out, and the `auth:admin` middleware then sends it to the login page.
 * Cheap for visitors: without an admin session nothing happens.
 */
class EndStaleAdminSession
{
    public const SESSION_KEY = 'admin_password_hash';

    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('admin');

        if ($request->hasSession() && $guard->check()) {
            $session = $request->session();
            $current = self::fingerprint($guard->user());

            if (! $session->has(self::SESSION_KEY)) {
                $session->put(self::SESSION_KEY, $current);
            } elseif (! hash_equals($session->get(self::SESSION_KEY), $current)) {
                $guard->logout();
                $session->invalidate();
            }
        }

        return $next($request);
    }

    /**
     * Not the hash itself: a keyed digest of it.
     */
    public static function fingerprint(Admin $admin): string
    {
        return hash_hmac('sha256', (string) $admin->getAuthPassword(), (string) config('app.key'));
    }
}
