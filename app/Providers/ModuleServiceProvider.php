<?php

namespace App\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider as BaseModuleServiceProvider;

/**
 * Base provider for every module.
 *
 * nwidart v12 registers module translations without a namespace, which lets a
 * module's lang/ar/auth.php silently merge into Laravel's own auth.php.
 * We register them under the module alias instead: __('auth::auth.login').
 */
abstract class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected function registerTranslations(): void
    {
        $langPath = module_path($this->name, config('modules.paths.generator.lang.path'));

        $this->loadTranslationsFrom($langPath, $this->nameLower);
    }
}
