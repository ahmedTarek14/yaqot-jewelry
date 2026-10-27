<?php

namespace Modules\Collection\Providers;

use App\Providers\ModuleServiceProvider;

class CollectionServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Collection';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'collection';
}
