<?php

namespace Modules\Setting\Providers;

use App\Providers\ModuleServiceProvider;
use Modules\Setting\Services\SettingService;

class SettingServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Setting';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'setting';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(SettingService::class);
    }
}
