<?php

namespace Modules\Analytics\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Analytics';

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
}
