<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Option\Http\Controllers\Dashboard\GoldColorController;
use Modules\Option\Http\Controllers\Dashboard\StoneTypeController;

// Dashboard: /ar/admin/gold-colors, /en/admin/gold-colors, and the same for stone-types.
// Both groups share one base controller; ids from the other group are a 404.
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    foreach ([
        'gold-colors' => GoldColorController::class,
        'stone-types' => StoneTypeController::class,
    ] as $prefix => $controller) {
        Route::controller($controller)->prefix($prefix)->name($prefix.'.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{option}/edit', 'edit')->name('edit');
            Route::put('/{option}', 'update')->name('update');
            Route::patch('/{option}/active', 'toggleActive')->name('toggle-active');
            Route::delete('/{option}', 'destroy')->name('destroy');
        });
    }
});
