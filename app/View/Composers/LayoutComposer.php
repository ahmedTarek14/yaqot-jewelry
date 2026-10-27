<?php

namespace App\View\Composers;

use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Shared data for the front and admin layouts, so the layouts hold no logic:
 * text direction, menus (config/navigation.php), language switch links and
 * links to pages that only exist once their module registers the route.
 */
class LayoutComposer
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function compose(View $view): void
    {
        $view->with($this->data ??= [
            'textDirection' => LaravelLocalization::getCurrentLocaleDirection(),
            'frontNav' => $this->menu('front'),
            'adminNav' => $this->menu('admin'),
            'languageSwitch' => $this->languageSwitch(),
            'searchUrl' => $this->urlIfExists('front.catalog'),
            'privacyUrl' => $this->urlIfExists('front.privacy'),
            'adminScriptMessages' => [
                'saved' => __('admin.messages.saved'),
                'validationFailed' => __('admin.messages.validation_failed'),
                'sessionExpired' => __('admin.messages.session_expired'),
                'error' => __('admin.messages.error'),
                'saving' => __('admin.messages.saving'),
                'close' => __('admin.messages.close'),
            ],
        ]);
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool, icon: ?string}>
     */
    private function menu(string $menu): array
    {
        return collect(config("navigation.$menu", []))
            ->filter(fn (array $item) => Route::has($item['route']))
            ->map(fn (array $item) => [
                'label' => __($item['label']),
                'url' => route($item['route']),
                'active' => request()->routeIs(...(array) $item['active']),
                'icon' => $item['icon'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * Links to the current page in every other supported locale.
     *
     * @return array<int, array{code: string, native: string, url: string}>
     */
    private function languageSwitch(): array
    {
        $current = app()->getLocale();

        return collect(LaravelLocalization::getSupportedLocales())
            ->reject(fn (array $properties, string $code) => $code === $current)
            ->map(fn (array $properties, string $code) => [
                'code' => $code,
                'native' => $properties['native'],
                // Not forced: the default locale (Arabic) links to its unprefixed URL, not /ar.
                'url' => LaravelLocalization::getLocalizedURL($code),
            ])
            ->values()
            ->all();
    }

    private function urlIfExists(string $route): ?string
    {
        return Route::has($route) ? route($route) : null;
    }
}
