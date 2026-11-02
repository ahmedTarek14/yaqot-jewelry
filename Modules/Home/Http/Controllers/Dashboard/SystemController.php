<?php

namespace Modules\Home\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\Home\Support\PendingMigrations;

/**
 * Runs the pending database migrations from the dashboard, for a host without a shell
 * (the equivalent of `php artisan migrate --force`). Admin only, a POST with a CSRF token, and it
 * takes no input: it can only apply migrations that were uploaded with the code.
 */
class SystemController extends Controller
{
    public function migrate(PendingMigrations $pending): RedirectResponse
    {
        $count = $pending->count();

        if ($count === 0) {
            return redirect()->route('admin.dashboard')->with('success', __('home::home.system.none'));
        }

        // An exception here is not caught on purpose: Laravel logs it and the admin sees the generic
        // error. MySQL does not roll back table changes, so the log says which migration stopped.
        // --isolated: a second press (a double click) waits for no lock and does nothing, instead of
        // running the same migrations twice.
        $status = Artisan::call('migrate', ['--force' => true, '--isolated' => true]);

        if ($status !== 0) {
            return redirect()->route('admin.dashboard')->with('error', __('home::home.system.failed'));
        }

        // Settings and other cached values may have changed shape.
        Cache::flush();

        return redirect()->route('admin.dashboard')->with('success', trans_choice('home::home.system.applied', $count, ['count' => $count]));
    }
}
