<?php

namespace Modules\Option\Providers;

use App\Providers\ModuleServiceProvider;

class OptionServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Option';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'option';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
