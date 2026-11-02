<?php

namespace Modules\Option\Http\Controllers\Dashboard;

use Modules\Option\Models\ProductOption;

class GoldColorController extends OptionController
{
    public function group(): string
    {
        return ProductOption::GOLD_COLOR;
    }

    protected function routeBase(): string
    {
        return 'admin.gold-colors';
    }
}
