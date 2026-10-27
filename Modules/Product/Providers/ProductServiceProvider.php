<?php

namespace Modules\Product\Providers;

use App\Providers\ModuleServiceProvider;

class ProductServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Product';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'product';
}
