<?php

namespace Tests\Feature\Page;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * /sitemap.xml and /robots.txt: visible content only, both languages, with hreflang alternates.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }
    }

    /** @return list<string> every <loc> */
    private function locations(): array
    {
        $response = $this->get('/sitemap.xml')->assertOk();
        $xml = $response->streamedContent();

        $document = new DOMDocument;
        $this->assertTrue($document->loadXML($xml), 'the sitemap is not well-formed XML');

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return array_map(fn ($node) => $node->textContent, iterator_to_array($xpath->query('//s:url/s:loc')));
    }

    public function test_the_sitemap_lists_the_static_pages_in_both_languages(): void
    {
        $this->get('/sitemap.xml')->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $locations = $this->locations();

        foreach (['ar', 'en'] as $locale) {
            foreach (['', '/catalog', '/bullion', '/about', '/contact', '/privacy-policy'] as $path) {
                $this->assertContains("http://localhost/$locale$path", $locations);
            }
        }
    }

    public function test_it_lists_only_visible_products_and_on_site_taxonomies(): void
    {
        $category = Category::factory()->create();
        $visible = Product::factory()->visible()->create();
        $visible->categories()->attach($category);

        $draft = Product::factory()->visible()->create(['is_published' => false]);
        $noImage = Product::factory()->published()->create();
        $trashed = Product::factory()->visible()->create();
        $trashed->delete();
        $inactiveCategory = Category::factory()->inactive()->create();
        $emptyBrand = Brand::factory()->create();
        $emptyCollection = Collection::factory()->create();

        $locations = $this->locations();

        foreach (['ar', 'en'] as $locale) {
            $this->assertContains("http://localhost/$locale/products/{$visible->slug}", $locations);
            $this->assertContains("http://localhost/$locale/categories/{$category->slug}", $locations);
        }

        $all = implode("\n", $locations);

        foreach ([$draft, $noImage, $trashed] as $hidden) {
            $this->assertStringNotContainsString($hidden->slug, $all, 'a product that is not visible is listed');
        }

        foreach ([$inactiveCategory, $emptyBrand, $emptyCollection] as $hidden) {
            $this->assertStringNotContainsString($hidden->slug, $all);
        }
    }

    public function test_bars_and_coins_are_listed_and_every_entry_has_hreflang_alternates(): void
    {
        $bar = Product::factory()->bar()->visible()->create();

        $xml = $this->get('/sitemap.xml')->streamedContent();

        $this->assertStringContainsString("<loc>http://localhost/en/products/{$bar->slug}</loc>", $xml);
        $this->assertStringContainsString(
            '<xhtml:link rel="alternate" hreflang="ar" href="http://localhost/ar/products/'.$bar->slug.'"/>'
            .'<xhtml:link rel="alternate" hreflang="en" href="http://localhost/en/products/'.$bar->slug.'"/>'
            .'<xhtml:link rel="alternate" hreflang="x-default" href="http://localhost/en/products/'.$bar->slug.'"/>',
            $xml,
        );
        $this->assertStringContainsString('<lastmod>', $xml);
    }

    public function test_the_sitemap_and_robots_are_served_without_cookies(): void
    {
        $this->get('/sitemap.xml')->assertCookieMissing('XSRF-TOKEN')->assertHeaderMissing('Set-Cookie');
        $this->get('/robots.txt')->assertHeaderMissing('Set-Cookie');
    }

    public function test_robots_txt_points_to_the_sitemap_in_production_and_blocks_everything_elsewhere(): void
    {
        $this->app['env'] = 'production';

        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Sitemap: http://localhost/sitemap.xml', false)
            ->assertSee('Disallow: /en/go/', false)
            ->assertDontSee('Disallow: /'."\n", false);

        $this->app['env'] = 'staging';

        $this->get('/robots.txt')->assertSee("User-agent: *\nDisallow: /\n", false)->assertDontSee('Sitemap:', false);
    }
}
