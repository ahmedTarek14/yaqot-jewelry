<?php

namespace Modules\Brand\Providers;

use App\Providers\ModuleServiceProvider;

class BrandServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Brand';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'brand';
}
