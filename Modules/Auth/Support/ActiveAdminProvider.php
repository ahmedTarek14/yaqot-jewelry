<?php

namespace Modules\Auth\Support;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Only active admins can be resolved, by credentials, by session id or by remember
 * token. A deactivated admin cannot log in, and an open session or remember cookie
 * stops working on the very next request (the guard sees no user and redirects to
 * the login page).
 */
class ActiveAdminProvider extends EloquentUserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->onlyActive(parent::retrieveById($identifier));
    }

    public function retrieveByToken($identifier, #[\SensitiveParameter] $token): ?Authenticatable
    {
        return $this->onlyActive(parent::retrieveByToken($identifier, $token));
    }

    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        return $this->onlyActive(parent::retrieveByCredentials($credentials));
    }

    private function onlyActive(?Authenticatable $admin): ?Authenticatable
    {
        return $admin !== null && $admin->is_active ? $admin : null;
    }
}
