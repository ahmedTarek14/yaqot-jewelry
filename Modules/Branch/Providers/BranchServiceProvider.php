<?php

namespace Modules\Branch\Providers;

use App\Providers\ModuleServiceProvider;

class BranchServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Branch';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'branch';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
