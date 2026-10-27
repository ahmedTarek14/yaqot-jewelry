<?php

/*
|--------------------------------------------------------------------------
| Navigation
|--------------------------------------------------------------------------
|
| Menu entries for the public header/footer and the admin sidebar.
| An entry is rendered only when its route exists, so a module's links
| appear automatically once the module registers its routes.
|
| label  : translation key
| route  : route name
| active : route name pattern(s) that mark the entry as current
| icon   : name passed to <x-icon> (admin only)
|
*/

return [

    'front' => [
        ['label' => 'front.nav.home', 'route' => 'front.home', 'active' => 'front.home'],
        ['label' => 'front.nav.catalog', 'route' => 'front.catalog', 'active' => ['front.catalog', 'front.products.*', 'front.categories.*', 'front.brands.*']],
        ['label' => 'front.nav.collections', 'route' => 'front.collections.index', 'active' => 'front.collections.*'],
        ['label' => 'front.nav.about', 'route' => 'front.about', 'active' => 'front.about'],
        ['label' => 'front.nav.contact', 'route' => 'front.contact', 'active' => 'front.contact'],
    ],

    'admin' => [
        ['label' => 'admin.nav.dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'grid'],
        ['label' => 'admin.nav.products', 'route' => 'admin.products.index', 'active' => 'admin.products.*', 'icon' => 'gem'],
        ['label' => 'admin.nav.categories', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'icon' => 'tag'],
        ['label' => 'admin.nav.collections', 'route' => 'admin.collections.index', 'active' => 'admin.collections.*', 'icon' => 'layers'],
        ['label' => 'admin.nav.brands', 'route' => 'admin.brands.index', 'active' => 'admin.brands.*', 'icon' => 'award'],
        ['label' => 'admin.nav.branches', 'route' => 'admin.branches.index', 'active' => 'admin.branches.*', 'icon' => 'map-pin'],
        ['label' => 'admin.nav.settings', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'icon' => 'settings'],
    ],

];
