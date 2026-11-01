<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Category\Http\Controllers\Dashboard\CategoryController;
use Modules\Category\Http\Controllers\Front\CategoryController as FrontCategoryController;

// Public site: /ar/categories/{slug}, /en/categories/{slug}
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeRedirect'],
    'as' => 'front.',
], function () {
    Route::get('/categories/{slug}', [FrontCategoryController::class, 'show'])->name('categories.show');
});

// Dashboard: /ar/admin/categories, /en/admin/categories
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    Route::controller(CategoryController::class)->prefix('categories')->name('categories.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{category}/edit', 'edit')->name('edit');
        Route::put('/{category}', 'update')->name('update');
        Route::patch('/{category}/active', 'toggleActive')->name('toggle-active');
        Route::delete('/{category}', 'destroy')->name('destroy');
    });
});
