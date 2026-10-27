<?php

namespace Modules\Home\Providers;

use App\Providers\ModuleServiceProvider;
use Modules\Home\Console\BrandAssetsCommand;

class HomeServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Home';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'home';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        BrandAssetsCommand::class,
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
}
