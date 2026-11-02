<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Database\Factories\AdminFactory;

class Admin extends Authenticatable
{
    use HasFactory;

    /**
     * is_active and last_login_at are deliberately not fillable: they are set explicitly
     * (AdminController after the safeguards, AuthController on login), never mass-assigned.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Admin>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('admins.is_active', true);
    }

    /**
     * Is this the admin signed in to the current request? (Marks "You" in the admin list.)
     */
    public function isSignedIn(): bool
    {
        return Auth::guard('admin')->id() === $this->getKey();
    }

    protected static function newFactory(): AdminFactory
    {
        return AdminFactory::new();
    }
}
