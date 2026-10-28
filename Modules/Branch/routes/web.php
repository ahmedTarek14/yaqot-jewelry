<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Branch\Http\Controllers\Dashboard\BranchController;

// Dashboard: /ar/admin/branches, /en/admin/branches
Route::prefix(LaravelLocalization::setLocale().'/admin')->name('admin.')->middleware(['admin.locale', 'auth:admin'])->group(function () {
    Route::controller(BranchController::class)->prefix('branches')->name('branches.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{branch}/edit', 'edit')->name('edit');
        Route::put('/{branch}', 'update')->name('update');
        Route::patch('/{branch}/active', 'toggleActive')->name('toggle-active');
        Route::delete('/{branch}', 'destroy')->name('destroy');
    });
});
