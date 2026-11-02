<?php

namespace Modules\Option\Http\Controllers\Dashboard;

use Modules\Option\Models\ProductOption;

class StoneTypeController extends OptionController
{
    public function group(): string
    {
        return ProductOption::STONE_TYPE;
    }

    protected function routeBase(): string
    {
        return 'admin.stone-types';
    }
}
