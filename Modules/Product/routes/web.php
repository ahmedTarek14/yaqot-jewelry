<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Product\Http\Controllers\Dashboard\ProductController;
use Modules\Product\Http\Controllers\Front\CatalogController;
use Modules\Product\Http\Controllers\Front\ProductController as FrontProductController;

// Public site: /ar/catalog, /en/catalog, /en/products/{slug}
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeRedirect'],
    'as' => 'front.',
], function () {
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::get('/products/{slug}', [FrontProductController::class, 'show'])->name('products.show');
});

// Dashboard: /ar/admin/products, /en/admin/products
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{product}/edit', 'edit')->name('edit');
        Route::put('/{product}', 'update')->name('update');
        Route::patch('/{product}/published', 'togglePublished')->name('toggle-published');
        Route::patch('/{product}/featured', 'toggleFeatured')->name('toggle-featured');
        Route::delete('/{product}', 'destroy')->name('destroy');
        Route::patch('/{product}/restore', 'restore')->name('restore')->withTrashed();
        Route::delete('/{product}/force', 'forceDestroy')->name('force-destroy')->withTrashed();
    });
});
