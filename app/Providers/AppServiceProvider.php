<?php

namespace App\Providers;

use App\Support\UploadLimits;
use App\View\Composers\FooterComposer;
use App\View\Composers\LayoutComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
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

        // A site whose APP_URL is https builds every link, asset and redirect as https, even when
        // the host's proxy ends TLS and PHP itself only sees plain http (no mixed content, and the
        // canonical, sitemap and Open Graph URLs are right).
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceHttps();
        }

        Paginator::useBootstrapFive();

        // Admin passwords: at least 10 characters with mixed case, a digit and a symbol.
        // Not uncompromised(): that would send hash prefixes to a third-party service.
        Password::defaults(fn () => Password::min(10)->letters()->mixedCase()->numbers()->symbols());

        View::composer(['layouts.front.*', 'layouts.admin.*', 'auth::login'], LayoutComposer::class);
        View::composer('layouts.front.footer', FooterComposer::class);
        // The gallery names the server's real upload limits under its file field.
        View::composer('components.admin.image-gallery', fn ($view) => $view->with('uploadLimits', UploadLimits::summary()));
    }
}
