<?php

namespace Modules\Auth\Support;

use Modules\Auth\Models\Admin;

/**
 * Keeps the dashboard reachable: nobody can deactivate or delete themselves, and the last
 * active admin can never be deactivated or deleted.
 *
 * Call inside DB::transaction(). {@see self::lock()} locks every active admin row, so two
 * concurrent requests (say, two admins deactivating each other) cannot both pass the check.
 */
final class AdminGuard
{
    /**
     * Lock the active admins and reload $target, so its is_active is current.
     *
     * @return int[] ids of the active admins
     */
    public static function lock(Admin $target): array
    {
        $activeIds = Admin::query()->active()->lockForUpdate()->pluck('id')->all();

        $target->refresh();

        return $activeIds;
    }

    /**
     * @param  int[]  $activeIds  from {@see self::lock()}
     * @param  'deactivate'|'delete'  $action
     *
     * @throws AdminSafeguardException
     */
    public static function ensureCanLoseAccess(Admin $target, ?Admin $actor, array $activeIds, string $action): void
    {
        if ($actor !== null && $actor->is($target)) {
            throw AdminSafeguardException::self($action);
        }

        if (in_array($target->getKey(), $activeIds) && count($activeIds) <= 1) {
            throw AdminSafeguardException::lastActive($action);
        }
    }
}
