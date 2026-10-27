<?php

namespace App\View\Composers;

use App\Http\Middleware\SetAdminLocale;
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
            'textDirection' => $this->textDirection(),
            'frontNav' => $this->menu('front'),
            'adminNav' => $this->menu('admin'),
            'languageSwitch' => $this->languageSwitch(),
            'hreflangLinks' => $this->hreflangLinks(),
            'searchUrl' => $this->urlIfExists('front.catalog'),
            'privacyUrl' => $this->urlIfExists('front.privacy'),
            // Admin "view site" links: the Arabic site, matching the dashboard's language.
            'adminSiteUrl' => LaravelLocalization::getLocalizedURL(SetAdminLocale::LOCALE, route('front.home')),
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
     * Direction of the *application* locale. LaravelLocalization's own current
     * locale is the URL's (English by default on /admin), while SetAdminLocale
     * switches the dashboard to Arabic.
     */
    private function textDirection(): string
    {
        $script = LaravelLocalization::getSupportedLocales()[app()->getLocale()]['script'] ?? 'Latn';

        return in_array($script, ['Arab', 'Hebr', 'Thaa', 'Syrc'], true) ? 'rtl' : 'ltr';
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
                'url' => LaravelLocalization::getLocalizedURL($code),
            ])
            ->values()
            ->all();
    }

    /**
     * <link rel="alternate" hreflang> for every locale, plus x-default
     * pointing at the default (English) version.
     *
     * @return array<int, array{hreflang: string, url: string}>
     */
    private function hreflangLinks(): array
    {
        // Built from the URL without its query string, so tracking or filter
        // parameters never end up in the alternates search engines index.
        $current = url()->current();

        $links = collect(LaravelLocalization::getSupportedLanguagesKeys())
            ->map(fn (string $code) => [
                'hreflang' => $code,
                'url' => LaravelLocalization::getLocalizedURL($code, $current),
            ]);

        return $links->push([
            'hreflang' => 'x-default',
            'url' => LaravelLocalization::getLocalizedURL(LaravelLocalization::getDefaultLocale(), $current),
        ])->all();
    }

    private function urlIfExists(string $route): ?string
    {
        return Route::has($route) ? route($route) : null;
    }
}
