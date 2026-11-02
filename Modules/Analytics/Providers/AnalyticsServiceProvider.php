<?php

namespace Modules\Analytics\Providers;

use App\Http\Middleware\SecurityHeaders;
use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\View;
use Modules\Analytics\Console\PruneStatsCommand;
use Modules\Analytics\Support\GoogleAnalytics;

class AnalyticsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Analytics';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'analytics';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        PruneStatsCommand::class,
    ];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // The public layout's <x-front.analytics-consent /> gets the measurement id (null = render nothing).
        View::composer('components.front.analytics-consent', function ($view): void {
            $measurementId = GoogleAnalytics::measurementId();

            // The security headers allow Google's hosts only on a page that carries the notice.
            request()->attributes->set(SecurityHeaders::ANALYTICS_ATTRIBUTE, $measurementId !== null);
            $view->with('measurementId', $measurementId);
        });
    }
}
