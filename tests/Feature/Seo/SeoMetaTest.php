<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Meta description, canonical URL, robots, Open Graph, Twitter and structured data of public pages.
 */
class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }
    }

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    #[DataProvider('locales')]
    public function test_the_settings_description_is_the_default_and_nothing_is_invented_without_it(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->get("/$locale")->assertOk()->assertDontSee('name="description"', false);

        app(SettingService::class)->setMany(['meta_description' => ['ar' => 'وصف الموقع', 'en' => 'The shop description']]);
        $expected = $locale === 'ar' ? 'وصف الموقع' : 'The shop description';

        $this->get("/$locale")
            ->assertOk()
            ->assertSee('<meta name="description" content="'.$expected.'">', false)
            ->assertSee('<meta property="og:description" content="'.$expected.'">', false)
            ->assertSee('<meta name="twitter:description" content="'.$expected.'">', false);
    }

    #[DataProvider('locales')]
    public function test_open_graph_and_twitter_tags_follow_the_page_language(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $ogLocale = $locale === 'ar' ? 'ar_AR' : 'en_US';
        $other = $locale === 'ar' ? 'en_US' : 'ar_AR';

        $this->get("/$locale/about")
            ->assertOk()
            ->assertSee('<link rel="canonical" href="http://localhost/'.$locale.'/about">', false)
            ->assertSee('<meta property="og:url" content="http://localhost/'.$locale.'/about">', false)
            ->assertSee('<meta property="og:locale" content="'.$ogLocale.'">', false)
            ->assertSee('<meta property="og:locale:alternate" content="'.$other.'">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('<meta name="twitter:card" content="summary">', false)
            ->assertSee('rel="alternate" hreflang="x-default"', false)
            ->assertSee('<meta property="og:image" content="http://localhost/assets/images/brand/generated/apple-touch-icon.png">', false);
    }

    public function test_a_product_page_shares_its_photo_and_describes_itself(): void
    {
        $product = Product::factory()->visible()->create(['karat' => 21, 'en' => ['name' => 'Seo Ring', 'description' => "A ring\nwith a   long story."]]);
        $image = $product->images()->first();

        $html = $this->get("/en/products/{$product->slug}")->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="description" content="A ring with a long story.">', $html);
        // Open Graph needs an absolute URL, whatever the disk returns.
        $this->assertStringContainsString('<meta property="og:image" content="'.url($image->url).'">', $html);
        $this->assertStringStartsWith('http://', url($image->url));
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
        $this->assertStringContainsString('<meta property="og:image:alt" content="Seo Ring">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Seo Ring', $html);
    }

    public function test_a_product_without_a_description_gets_one_from_its_attributes(): void
    {
        $product = Product::factory()->visible()->create(['karat' => 21, 'weight_grams' => 12.5, 'en' => ['name' => 'Plain Ring', 'description' => null]]);

        $this->get("/en/products/{$product->slug}")
            ->assertOk()
            ->assertSee('<meta name="description" content="Plain Ring: ', false);
    }

    public function test_a_long_description_is_cut_at_a_word_boundary(): void
    {
        $product = Product::factory()->visible()->create(['en' => ['name' => 'Long', 'description' => str_repeat('gold ', 100)]]);

        preg_match('/<meta name="description" content="([^"]+)">/', $this->get("/en/products/{$product->slug}")->getContent(), $match);

        $this->assertLessThanOrEqual(165, mb_strlen($match[1]));
        $this->assertStringEndsWith('…', $match[1]);
    }

    public function test_filtered_and_searched_listings_are_noindex_with_the_plain_canonical(): void
    {
        Product::factory()->visible()->create();

        $plain = $this->get('/en/catalog')->assertOk();
        $plain->assertDontSee('name="robots"', false)->assertSee('<link rel="canonical" href="http://localhost/en/catalog">', false);

        foreach (['?q=ring', '?karat=21', '?sort=name', '?q=a&page=1', '?utm_source=mail'] as $query) {
            $this->get("/en/catalog$query")
                ->assertOk()
                ->assertSee('<meta name="robots" content="noindex, follow">', false)
                ->assertSee('<link rel="canonical" href="http://localhost/en/catalog">', false);
        }

        // An empty parameter (the filter form submits them) is not a filter.
        $this->get('/en/catalog?q=&karat=')->assertDontSee('name="robots"', false);

        $this->get('/en/bullion?q=bar')->assertSee('noindex, follow', false);
    }

    public function test_later_pages_of_a_plain_listing_stay_indexable_with_their_own_canonical(): void
    {
        config(['catalog.front.per_page' => 2]);
        Product::factory()->visible()->count(5)->create();

        $this->get('/en/catalog?page=2')
            ->assertOk()
            ->assertDontSee('name="robots"', false)
            ->assertSee('<link rel="canonical" href="http://localhost/en/catalog?page=2">', false);

        $this->get('/en/catalog?page=1')->assertSee('<link rel="canonical" href="http://localhost/en/catalog">', false);
    }

    public function test_category_pages_follow_the_same_rules_and_share_their_description(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Rings', 'description' => 'All our rings.']]);
        Product::factory()->visible()->create()->categories()->attach($category);

        $this->get("/en/categories/{$category->slug}")
            ->assertOk()
            ->assertSee('<meta name="description" content="All our rings.">', false)
            ->assertDontSee('name="robots"', false);

        $this->get("/en/categories/{$category->slug}?karat=18")
            ->assertSee('noindex, follow', false)
            ->assertSee('<link rel="canonical" href="http://localhost/en/categories/'.$category->slug.'">', false);
    }

    public function test_other_pages_ignore_the_query_string_in_their_canonical(): void
    {
        $this->get('/en/about?utm_source=x&ref=y')
            ->assertOk()
            ->assertDontSee('name="robots"', false)
            ->assertSee('<link rel="canonical" href="http://localhost/en/about">', false);
    }

    /** @return list<array<string, mixed>> the page's ld+json blocks */
    private function structuredData(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        return array_map(fn (string $json) => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $matches[1]);
    }

    public function test_breadcrumbs_are_structured_data_with_absolute_urls(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Rings']]);
        $product = Product::factory()->visible()->create(['en' => ['name' => 'Crumb Ring']]);
        $product->categories()->attach($category);

        $blocks = $this->structuredData($this->get("/en/products/{$product->slug}")->getContent());
        $list = collect($blocks)->firstWhere('@type', 'BreadcrumbList');

        $this->assertNotNull($list);
        $items = $list['itemListElement'];
        $this->assertSame([1, 2, 3, 4], array_column($items, 'position'));
        $this->assertSame(['Home', 'Jewelry', 'Rings', 'Crumb Ring'], array_column($items, 'name'));
        $this->assertSame('http://localhost/en', $items[0]['item']);
        $this->assertSame('http://localhost/en/products/'.$product->slug, $items[3]['item']);
    }

    public function test_the_homepage_describes_the_store_only_from_real_settings(): void
    {
        $bare = collect($this->structuredData($this->get('/en')->getContent()))->firstWhere('@type', 'JewelryStore');

        $this->assertSame(['@context', '@type', 'name', 'url', 'logo'], array_keys($bare));
        $this->assertSame('http://localhost/en', $bare['url']);

        app(SettingService::class)->setMany([
            'phone' => '19123',
            'email' => 'hello@example.com',
            'facebook_url' => 'https://facebook.com/example',
            'instagram_url' => 'http://insecure.example.com',
        ]);

        $html = $this->get('/en')->getContent();
        $store = collect($this->structuredData($html))->firstWhere('@type', 'JewelryStore');

        $this->assertSame('19123', $store['telephone']);
        $this->assertSame('hello@example.com', $store['email']);
        $this->assertSame(['https://facebook.com/example'], $store['sameAs']);

        // No offers, prices or ratings, ever.
        foreach (['offers', 'price', 'aggregateRating', 'review'] as $forbidden) {
            $this->assertStringNotContainsString('"'.$forbidden.'"', $html);
        }
    }

    public function test_structured_data_cannot_close_its_script_tag(): void
    {
        $product = Product::factory()->visible()->create(['en' => ['name' => '</script><b>x']]);

        $html = $this->get("/en/products/{$product->slug}")->getContent();

        $this->assertStringNotContainsString('</script><b>', $html);
        $this->assertSame('</script><b>x', collect($this->structuredData($html))->firstWhere('@type', 'BreadcrumbList')['itemListElement'][2]['name'] ?? '</script><b>x');
    }
}
