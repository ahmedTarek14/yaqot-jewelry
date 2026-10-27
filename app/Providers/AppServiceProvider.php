<?php

namespace App\Providers;

use App\View\Composers\LayoutComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;

class AppServiceProvider extends ServiceProvider
{
    // Route caching: `php artisan route:trans:cache` writes one cache file per
    // locale; plain `route:cache` would make every /ar and /en URL return 404.
    use LoadsTranslatedCachedRoutes;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(LayoutComposer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RouteServiceProvider::loadCachedRoutesUsing(fn () => $this->loadCachedRoutes());

        Model::preventLazyLoading(! $this->app->isProduction());

        Paginator::useBootstrapFive();

        View::composer(['layouts.front.*', 'layouts.admin.*', 'auth::login'], LayoutComposer::class);
    }
}
