<?php

namespace Modules\Home\Support;

use Modules\Setting\Rules\SiteLink;
use Modules\Setting\Services\SettingService;

/**
 * The homepage hero slider. The three jewelry images are fixed files (not uploads); only the
 * text, the button and the "enabled" switch of each slide come from Settings. A text the owner
 * left empty falls back to the lang file, a disabled slide is skipped, and with every slide
 * disabled slide 1 is shown with its fallback text, so the hero is never empty.
 */
class HeroSlides
{
    /**
     * Slide number => image name and the pixel width of its 1x and 2x WebP in
     * public/assets/images/hero/{name}-1x.webp and -2x.webp (square cutouts).
     */
    public const IMAGES = [
        1 => ['name' => 'ring', 'widths' => [627, 1254]],
        2 => ['name' => 'necklace', 'widths' => [720, 1440]],
        3 => ['name' => 'bangles', 'widths' => [720, 1440]],
    ];

    public function __construct(private readonly SettingService $settings) {}

    /**
     * The slides to show, in order.
     *
     * @return list<array{number: int, title: string, subtitle: string, ctaLabel: string, ctaUrl: string, alt: string, src: string, srcset: string, width: int, height: int}>
     */
    public function slides(string $catalogUrl): array
    {
        $enabled = array_values(array_filter(
            array_keys(self::IMAGES),
            fn (int $number) => (string) $this->settings->get("hero_{$number}_enabled") !== '0',
        ));

        if ($enabled === []) {
            return [$this->slide(1, $catalogUrl, useSettings: false)];
        }

        return array_map(fn (int $number) => $this->slide($number, $catalogUrl, useSettings: true), $enabled);
    }

    /**
     * @return array{number: int, title: string, subtitle: string, ctaLabel: string, ctaUrl: string, alt: string, src: string, srcset: string, width: int, height: int}
     */
    private function slide(int $number, string $catalogUrl, bool $useSettings): array
    {
        $text = fn (string $field, string $fallback) => $useSettings
            ? (string) $this->settings->get("hero_{$number}_{$field}", $fallback)
            : $fallback;

        $image = self::IMAGES[$number];
        [$width1, $width2] = $image['widths'];
        $base = "assets/images/hero/{$image['name']}";

        $link = $useSettings ? $this->settings->get("hero_{$number}_cta_url") : null;

        return [
            'number' => $number,
            'title' => $text('title', __("home::home.hero.slides.$number.title")),
            'subtitle' => $text('subtitle', __("home::home.hero.slides.$number.subtitle")),
            'ctaLabel' => $text('cta_label', __('home::home.hero.cta')),
            'ctaUrl' => $link !== null && SiteLink::isValid($link) ? $this->resolve($link) : $catalogUrl,
            'alt' => __("home::home.hero.slides.$number.alt"),
            'src' => versioned_asset("{$base}-1x.webp"),
            'srcset' => versioned_asset("{$base}-1x.webp")." {$width1}w, ".versioned_asset("{$base}-2x.webp")." {$width2}w",
            'width' => $width1,
            'height' => $width1,
        ];
    }

    /**
     * An https URL is used as is. A path on this site keeps the visitor's language, unless the
     * owner already wrote one ("/en/catalog").
     */
    private function resolve(string $link): string
    {
        if (! str_starts_with($link, '/')) {
            return $link;
        }

        $locales = implode('|', array_map('preg_quote', config('translatable.locales')));

        if (! preg_match('#^/('.$locales.')(?=[/?\#]|$)#', $link)) {
            $link = '/'.app()->getLocale().($link === '/' ? '' : $link);
        }

        return url($link);
    }
}
