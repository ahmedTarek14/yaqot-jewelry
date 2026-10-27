<?php

namespace Modules\Category\Providers;

use App\Providers\ModuleServiceProvider;

class CategoryServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Category';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'category';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
