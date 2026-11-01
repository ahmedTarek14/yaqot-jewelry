<?php

namespace Modules\Page\Providers;

use App\Providers\ModuleServiceProvider;

class PageServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Page';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'page';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
