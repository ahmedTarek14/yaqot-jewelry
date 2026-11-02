<?php

namespace Modules\Auth\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Console\CreateAdminCommand;
use Modules\Auth\Support\ActiveAdminProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Auth';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'auth';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        CreateAdminCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // config/auth.php "admins" provider: driver "active-admin".
        Auth::provider('active-admin', fn ($app, array $config) => new ActiveAdminProvider($app['hash'], $config['model']));
    }
}
