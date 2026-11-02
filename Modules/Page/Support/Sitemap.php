<?php

namespace Modules\Page\Support;

use Generator;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;

/**
 * sitemap.xml: every public page in both languages, each with its hreflang alternates.
 * Only what the site really shows: the static pages, the bullion page, categories, collections
 * and brands that are onSite(), and Product::visible() products (so drafts, trashed products,
 * products without an image or a name in either language never appear).
 *
 * The XML is produced piece by piece (a generator, products read in chunks), so a large
 * catalogue never sits in memory. A sitemap file holds at most 50,000 URLs: beyond MAX_URLS the
 * list stops (static pages, taxonomies and the oldest products come first); at that size
 * (~25,000 products) split it into a sitemap index.
 */
final class Sitemap
{
    public const MAX_URLS = 50000;

    private const CHUNK = 500;

    private int $urls = 0;

    /**
     * @return Generator<int, string>
     */
    public function xml(): Generator
    {
        yield '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        yield '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach ($this->staticPages() as $url) {
            yield from $this->entry($url);
        }

        foreach ([
            [Category::query()->onSite(), 'front.categories.show'],
            [Collection::query()->onSite(), 'front.collections.show'],
            [Brand::query()->onSite(), 'front.brands.show'],
            [Product::query()->visible(), 'front.products.show'],
        ] as [$query, $route]) {
            yield from $this->records($query, $route);
        }

        yield '</urlset>'."\n";
    }

    /**
     * @return list<string> the pages every site has (the bullion page only when its module is on)
     */
    private function staticPages(): array
    {
        return array_values(array_filter(array_map(
            fn (string $route) => Route::has($route) ? route($route) : null,
            ['front.home', 'front.catalog', 'front.bullion', 'front.about', 'front.contact', 'front.privacy'],
        )));
    }

    /**
     * @return Generator<int, string>
     */
    private function records(BuilderContract $query, string $route): Generator
    {
        foreach ($query->select(['id', 'slug', 'updated_at'])->lazyById(self::CHUNK) as $record) {
            yield from $this->entry(route($route, $record->slug), $record->updated_at?->toAtomString());
        }
    }

    /**
     * One <url> per language for the page whose URL (in any locale) is $url.
     *
     * @return Generator<int, string>
     */
    private function entry(string $url, ?string $lastModified = null): Generator
    {
        $locales = LaravelLocalization::getSupportedLanguagesKeys();

        if ($this->urls + count($locales) > self::MAX_URLS) {
            return;
        }

        $alternates = [];

        foreach ($locales as $locale) {
            $alternates[$locale] = LaravelLocalization::getLocalizedURL($locale, $url);
        }

        $links = '';

        foreach ($alternates as $locale => $href) {
            $links .= '<xhtml:link rel="alternate" hreflang="'.$this->escape($locale).'" href="'.$this->escape($href).'"/>';
        }

        $links .= '<xhtml:link rel="alternate" hreflang="x-default" href="'
            .$this->escape($alternates[LaravelLocalization::getDefaultLocale()]).'"/>';

        foreach ($alternates as $href) {
            $this->urls++;

            yield '<url><loc>'.$this->escape($href).'</loc>'
                .($lastModified ? '<lastmod>'.$this->escape($lastModified).'</lastmod>' : '')
                .$links.'</url>'."\n";
        }
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
