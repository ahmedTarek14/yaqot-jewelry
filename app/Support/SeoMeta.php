<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Per-request SEO data for the public layout (<x-front> head: description, canonical, robots,
 * Open Graph and Twitter). Pages add their own description and image by passing
 * $metaDescription, $metaImage and $metaImageAlt to the view; everything else is here.
 *
 * Listings (catalog, bullion, category, collection and brand pages):
 *  - Any query parameter other than `page` (search, filters, sort) makes a near-duplicate of
 *    the plain listing, so the page is `noindex, follow` and its canonical is the plain URL.
 *  - Page 2 and later of the plain listing stay indexable with a canonical of their own
 *    (`?page=N`): that is how the products on later pages are found, and each page is a
 *    different list. (Products are in the sitemap too.)
 * Every other page's canonical is its URL without any query string.
 */
final class SeoMeta
{
    private const LISTINGS = [
        'front.catalog',
        'front.bullion',
        'front.categories.show',
        'front.collections.show',
        'front.brands.show',
    ];

    private const OG_LOCALES = ['ar' => 'ar_AR', 'en' => 'en_US'];

    /** Longest description emitted for text taken from the catalog (search engines show ~155). */
    public const DESCRIPTION_LIMIT = 160;

    /**
     * @return array{canonical: string, robots: ?string, description: ?string, image: string, imageAlt: string, locale: string, alternateLocales: list<string>, siteName: string}
     */
    public static function forRequest(Request $request): array
    {
        // url() (not $request->url()) so the scheme follows the forced https of production.
        $canonical = url($request->path());
        $robots = null;

        if (in_array($request->route()?->getName(), self::LISTINGS, true)) {
            $query = $request->query();
            $page = $query['page'] ?? null;
            unset($query['page']);

            if (array_filter($query, fn ($value) => $value !== null && $value !== '' && $value !== []) !== []) {
                $robots = 'noindex, follow';
            } elseif (is_string($page) && ctype_digit($page) && (int) $page > 1) {
                $canonical .= '?page='.(int) $page;
            }
        }

        $locale = app()->getLocale();
        $description = setting('meta_description');

        return [
            'canonical' => $canonical,
            'robots' => $robots,
            'description' => is_string($description) && trim($description) !== '' ? self::describe($description, 300) : null,
            // The default share image: the brand mark (the page passes its own, e.g. the product photo).
            'image' => asset('assets/images/brand/generated/apple-touch-icon.png'),
            'imageAlt' => __('front.brand.name'),
            'locale' => self::OG_LOCALES[$locale] ?? $locale,
            'alternateLocales' => array_values(array_diff(self::OG_LOCALES, [self::OG_LOCALES[$locale] ?? $locale])),
            'siteName' => __('front.brand.name'),
        ];
    }

    /**
     * Plain text for a meta description: tags and extra whitespace removed, cut at a word
     * boundary. Null when nothing is left.
     */
    public static function describe(?string $text, int $limit = self::DESCRIPTION_LIMIT): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));

        return $text === '' ? null : Str::limit($text, $limit, '…', preserveWords: true);
    }

    /**
     * schema.org BreadcrumbList for the trail <x-front.breadcrumbs> shows: Home, then the items
     * (the last one has no URL: it is the current page).
     *
     * @param  array<int, array{label: string, url?: ?string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbList(array $items): array
    {
        $trail = [['label' => __('front.breadcrumbs.home'), 'url' => route('front.home')], ...array_values($items)];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['label'],
                'item' => ($item['url'] ?? null) ?: url()->current(),
            ], $trail, array_keys($trail)),
        ];
    }

    /**
     * schema.org JewelryStore for the homepage, built only from what the owner configured
     * (no offers, prices or ratings); an empty phone, email or social list is left out.
     *
     * @param  array<int, array{network: string, label: string, url: string}>  $socials
     * @return array<string, mixed>
     */
    public static function organization(?string $phone, ?string $email, array $socials): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'JewelryStore',
            'name' => __('front.brand.name'),
            'url' => route('front.home'),
            'logo' => asset('assets/images/brand/generated/apple-touch-icon.png'),
            'telephone' => $phone,
            'email' => $email,
            'sameAs' => array_column($socials, 'url') ?: null,
        ], fn ($value) => $value !== null);
    }

    /**
     * An absolute URL for a stored or site-relative one (Open Graph needs absolute URLs).
     */
    public static function absolute(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        return preg_match('#^https?://#i', $url) ? $url : url($url);
    }
}
