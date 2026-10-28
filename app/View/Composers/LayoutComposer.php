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

    /** The request $data was built for: a layout renders many partials per request. */
    private ?object $request = null;

    public function compose(View $view): void
    {
        if ($this->request !== request()) {
            $this->request = request();
            $this->data = null;
        }

        $view->with($this->data ??= [
            'textDirection' => $this->textDirection(),
            'frontNav' => $this->menu('front'),
            'adminNav' => $this->menu('admin'),
            'languageSwitch' => $this->languageSwitch(),
            'hreflangLinks' => $this->hreflangLinks(),
            'searchUrl' => $this->urlIfExists('front.catalog'),
            'privacyUrl' => $this->urlIfExists('front.privacy'),
            // Admin "view site" links: the public site in the dashboard's current language.
            'adminSiteUrl' => route('front.home'),
            'adminScriptMessages' => [
                'saved' => __('admin.messages.saved'),
                'validationFailed' => __('admin.messages.validation_failed'),
                'sessionExpired' => __('admin.messages.session_expired'),
                'tooLarge' => __('admin.messages.too_large'),
                'error' => __('admin.messages.error'),
                'saving' => __('admin.messages.saving'),
                'close' => __('admin.messages.close'),
                // ":name" stays a placeholder; admin.js fills it with the item's name.
                'deleteConfirm' => __('admin.modals.delete_body'),
                'multiselectSelected' => __('admin.form.multiselect.selected'),
                ...__('admin.scripts'),
            ],
        ]);
    }

    /**
     * Direction of the application locale (set from the URL prefix by
     * LaravelLocalization on both the public site and the dashboard).
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
                'url' => $this->localizedCurrentUrl($code),
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

    /**
     * The current page in another locale, with the raw query string (as the
     * locale-redirect middlewares do: no reordering, repeated keys kept).
     * The URL is passed explicitly because LaravelLocalization keeps the request
     * it was built with, which is not the current one in tests or long-running workers.
     */
    private function localizedCurrentUrl(string $locale): string
    {
        $url = LaravelLocalization::getLocalizedURL($locale, request()->url());
        $query = request()->server('QUERY_STRING');

        return $query ? "$url?$query" : $url;
    }

    private function urlIfExists(string $route): ?string
    {
        return Route::has($route) ? route($route) : null;
    }
}
