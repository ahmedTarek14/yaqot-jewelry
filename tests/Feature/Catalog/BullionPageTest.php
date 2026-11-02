<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * /{locale}/bullion: gold bars and coins, kept apart from the jewelry listings.
 */
class BullionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }
    }

    /** @return array<string, array{string, string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar', 'rtl'], 'english' => ['en', 'ltr']];
    }

    #[DataProvider('locales')]
    public function test_the_page_renders_in_both_locales(string $locale, string $dir): void
    {
        $this->refreshApplicationWithLocale($locale);
        Product::factory()->visible()->bar()->create();

        $this->get("/$locale/bullion")
            ->assertOk()
            ->assertSee("<html lang=\"$locale\" dir=\"$dir\"", false)
            ->assertSee('<h1 class="yq-page-header__title">'.e(__('product::bullion.title', [], $locale)).'</h1>', false)
            ->assertSee(__('product::bullion.lead', [], $locale))
            ->assertSee('id="catalog-filters-panel"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_the_gold_price_note_is_shown_and_no_price_appears(): void
    {
        Product::factory()->visible()->bar()->create(['weight_grams' => 10]);

        $html = $this->get('/en/bullion')
            ->assertOk()
            ->assertSee('Our prices follow the daily gold price. Ask us about any bar or coin.')
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/EGP|L\.E|£|ج\.م|\d\s*(?:جنيه|pounds)/iu', $html);
        // Empty page too.
        Product::query()->forceDelete();
        $this->get('/en/bullion')->assertOk()->assertSee('daily gold price');
    }

    public function test_only_visible_bars_and_coins_are_listed(): void
    {
        Product::factory()->visible()->bar()->create(['en' => ['name' => 'Visible bar']]);
        Product::factory()->visible()->coin()->create(['en' => ['name' => 'Visible coin']]);
        Product::factory()->visible()->create(['en' => ['name' => 'Jewelry piece']]);
        Product::factory()->withImages()->bar()->create(['en' => ['name' => 'Draft bar']]);
        Product::factory()->published()->coin()->create(['en' => ['name' => 'Imageless coin']]);
        Product::factory()->visible()->bar()->create(['en' => ['name' => 'Trashed bar']])->delete();

        $this->assertSame(['Visible bar', 'Visible coin'], $this->sorted('/en/bullion'));
    }

    public function test_jewelry_listings_never_show_bars_or_coins(): void
    {
        $brand = Brand::factory()->create(['en' => ['name' => 'Shared Maker']]);
        $category = Category::factory()->create(['en' => ['name' => 'Shared Cat']]);
        $collection = Collection::factory()->create(['en' => ['name' => 'Shared Set']]);

        $jewel = Product::factory()->visible()->create(['en' => ['name' => 'Jewel'], 'brand_id' => $brand->id]);
        $bar = Product::factory()->visible()->bar()->create(['en' => ['name' => 'Bar'], 'brand_id' => $brand->id]);
        $coin = Product::factory()->visible()->coin()->create(['en' => ['name' => 'Coin'], 'brand_id' => $brand->id]);
        foreach ([$jewel, $bar, $coin] as $product) {
            $product->categories()->attach($category);
            $product->collections()->attach($collection);
        }

        foreach (['/en/catalog', '/en/categories/shared-cat', '/en/brands/shared-maker', '/en/collections/shared-set', '/en/catalog?q=bar'] as $url) {
            $titles = $this->titles($url);
            $this->assertNotContains('Bar', $titles, $url);
            $this->assertNotContains('Coin', $titles, $url);
        }
        $this->assertSame(['Jewel'], $this->titles('/en/catalog'));
        $this->assertSame(['Jewel'], $this->titles('/en/brands/shared-maker'));
        $this->assertSame(['Bar', 'Coin'], $this->sorted('/en/bullion?brand=shared-maker'));
    }

    public function test_a_category_holding_only_bullion_is_not_on_site(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Bullion Only']]);
        Product::factory()->visible()->bar()->create()->categories()->attach($category);

        $this->get('/en/categories/bullion-only')->assertNotFound();
    }

    public function test_the_type_filter(): void
    {
        Product::factory()->visible()->bar()->create(['en' => ['name' => 'Bar']]);
        Product::factory()->visible()->coin()->create(['en' => ['name' => 'Coin']]);
        Product::factory()->visible()->create(['en' => ['name' => 'Jewel']]);

        $this->assertSame(['Bar'], $this->titles('/en/bullion?type=bar'));
        $this->assertSame(['Coin'], $this->titles('/en/bullion?type=coin'));
        // "jewelry" and junk are ignored: never jewelry on this page.
        foreach (['type=jewelry', 'type=ring', 'type[]=bar', 'type='] as $query) {
            $this->assertSame(['Bar', 'Coin'], $this->sorted("/en/bullion?$query"), $query);
        }
    }

    public function test_weight_range_and_karat_filters(): void
    {
        Product::factory()->visible()->bar()->create(['en' => ['name' => 'A'], 'weight_grams' => 5, 'karat' => 24]);
        Product::factory()->visible()->bar()->create(['en' => ['name' => 'B'], 'weight_grams' => 20, 'karat' => 21]);
        Product::factory()->visible()->coin()->create(['en' => ['name' => 'C'], 'weight_grams' => 8, 'karat' => 21]);

        $this->assertSame(['B'], $this->sorted('/en/bullion?weight_min=10'));
        $this->assertSame(['A'], $this->sorted('/en/bullion?weight_max=5'));
        $this->assertSame(['A', 'C'], $this->sorted('/en/bullion?weight_min=5&weight_max=8'));
        $this->assertSame(['A', 'C'], $this->sorted('/en/bullion?weight_min=8&weight_max=5'));
        $this->assertSame(['A'], $this->sorted('/en/bullion?karat=24'));
        $this->assertSame(['B', 'C'], $this->sorted('/en/bullion?karat=21'));
        $this->assertSame(['C'], $this->sorted('/en/bullion?karat=21&type=coin'));

        foreach (['karat=19', 'karat=21abc', 'karat[]=21', 'weight_min=abc', 'weight_max=-3', 'sort=price', 'q[]=x', 'page=abc', 'gold_color=x', 'category=x'] as $query) {
            $this->assertSame(['A', 'B', 'C'], $this->sorted("/en/bullion?$query"), $query);
        }
    }

    public function test_the_brand_filter_offers_only_brands_with_visible_bullion(): void
    {
        $maker = Brand::factory()->create(['en' => ['name' => 'Bullion Maker']]);
        $jeweler = Brand::factory()->create(['en' => ['name' => 'Ring Jeweler']]);
        $inactive = Brand::factory()->inactive()->create(['en' => ['name' => 'Closed Mint']]);
        $draftOnly = Brand::factory()->create(['en' => ['name' => 'Draft Mint']]);

        Product::factory()->visible()->bar()->create(['en' => ['name' => 'A'], 'brand_id' => $maker->id]);
        Product::factory()->visible()->coin()->create(['en' => ['name' => 'B'], 'brand_id' => $inactive->id]);
        Product::factory()->visible()->create(['brand_id' => $jeweler->id]);
        Product::factory()->withImages()->bar()->create(['brand_id' => $draftOnly->id]);

        $this->get('/en/bullion')
            ->assertOk()
            ->assertSee('value="bullion-maker"', false)
            ->assertSee('Manufacturer')
            ->assertDontSee('Ring Jeweler')
            ->assertDontSee('Closed Mint')
            ->assertDontSee('Draft Mint');

        $this->assertSame(['A'], $this->titles('/en/bullion?brand=bullion-maker'));
        // Unknown, jeweler-only and inactive brands are ignored.
        foreach (['unknown', 'ring-jeweler', 'closed-mint'] as $slug) {
            $this->assertSame(['A', 'B'], $this->sorted("/en/bullion?brand=$slug"), $slug);
        }
    }

    public function test_search_matches_the_name_in_either_locale_or_the_sku(): void
    {
        Product::factory()->visible()->bar()->create(['sku' => 'BAR-001', 'en' => ['name' => 'Ten gram bar'], 'ar' => ['name' => 'سبيكة عشرة جرام']]);
        Product::factory()->visible()->coin()->create(['sku' => 'COIN-77', 'en' => ['name' => 'Royal coin'], 'ar' => ['name' => 'جنيه ملكي']]);
        Product::factory()->visible()->create(['en' => ['name' => 'Royal ring'], 'ar' => ['name' => 'خاتم ملكي']]);

        $this->assertSame(['Royal coin'], $this->titles('/en/bullion?q=royal'));
        $this->assertSame(['Ten gram bar'], $this->titles('/en/bullion?q=bar-0'));
        $this->assertSame(['Royal coin'], $this->titles('/en/bullion?q=coin-77'));
        $this->assertSame(['Ten gram bar'], $this->titles('/en/bullion?q='.urlencode('سبيكة')));
        $this->assertSame([], $this->titles('/en/bullion?q=missing'));

        $this->get('/en/bullion?q=royal')->assertSee('Results for “royal”');

        $this->refreshApplicationWithLocale('ar');
        Product::factory()->visible()->coin()->create(['en' => ['name' => 'Arabic coin'], 'ar' => ['name' => 'جنيه عربي']]);
        $this->assertSame(['جنيه عربي'], $this->titles('/ar/bullion?q='.urlencode('عربي')));
    }

    public function test_the_sort_allowlist(): void
    {
        Product::factory()->visible()->bar()->create(['en' => ['name' => 'Beta'], 'ar' => ['name' => 'ب'], 'weight_grams' => 5, 'published_at' => now()->subDays(3)]);
        Product::factory()->visible()->bar()->create(['en' => ['name' => 'Alpha'], 'ar' => ['name' => 'ج'], 'weight_grams' => null, 'published_at' => now()->subDays(1)]);
        Product::factory()->visible()->coin()->create(['en' => ['name' => 'Gamma'], 'ar' => ['name' => 'أ'], 'weight_grams' => 1, 'published_at' => now()->subDays(2)]);

        // The default is the lightest first, products without a weight last.
        $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->titles('/en/bullion'));
        $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->titles('/en/bullion?sort=weight_asc'));
        $this->assertSame(['Beta', 'Gamma', 'Alpha'], $this->titles('/en/bullion?sort=weight_desc'));
        $this->assertSame(['Alpha', 'Gamma', 'Beta'], $this->titles('/en/bullion?sort=newest'));
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $this->titles('/en/bullion?sort=name'));
        $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->titles('/en/bullion?sort=price'));

        $this->get('/en/bullion?sort=name')->assertSee('<option value="name" selected>', false);
        $this->get('/en/bullion')->assertSee('<option value="" selected>', false);
    }

    public function test_pagination_keeps_the_query_string_and_a_page_past_the_end_redirects(): void
    {
        Product::factory()->count(30)->visible()->coin()->create(['karat' => 21]);

        $first = $this->get('/en/bullion?karat=21&sort=name')->assertOk();
        $this->assertCount(24, $this->cardTitles($first->getContent()));
        $first->assertSee('href="http://localhost/en/bullion?karat=21&amp;sort=name&amp;page=2"', false);

        $this->assertCount(6, $this->titles('/en/bullion?karat=21&sort=name&page=2'));
        $this->get('/en/bullion?karat=21&page=9')->assertRedirect('http://localhost/en/bullion?karat=21&page=2');
    }

    public function test_clear_filters_and_empty_states(): void
    {
        $this->get('/en/bullion')
            ->assertOk()
            ->assertSee('No gold bars or coins yet')
            ->assertDontSee('No pieces yet')
            ->assertDontSee('yq-product-grid', false);

        $this->refreshApplicationWithLocale('ar');
        $this->get('/ar/bullion')->assertOk()->assertSee('لا توجد سبايك أو جنيهات بعد');
        $this->refreshApplicationWithLocale('en');

        Product::factory()->visible()->bar()->create(['karat' => 21]);

        $this->get('/en/bullion')->assertOk()->assertDontSee('Clear filters');
        $this->get('/en/bullion?karat=24&sort=name')
            ->assertOk()
            ->assertSee('No bars or coins match your search')
            ->assertSee('href="http://localhost/en/bullion?sort=name" class="btn btn-outline-gold">Clear filters</a>', false);
        $this->get('/en/bullion?type=bar&q=demo')
            ->assertOk()
            ->assertSee('href="http://localhost/en/bullion" class="yq-underline">Clear filters</a>', false)
            ->assertSee('Filters (2)');
    }

    public function test_the_nav_item_shows_on_every_page(): void
    {
        foreach (['/en', '/en/catalog', '/en/bullion', '/en/about', '/en/contact'] as $url) {
            $this->get($url)->assertOk()->assertSee('href="http://localhost/en/bullion"', false)->assertSee('Gold bars &amp; coins', false);
        }

        $this->get('/en/bullion')->assertSeeInOrder(['/en/bullion"', 'aria-current="page"', 'Gold bars &amp; coins</a>'], false);
    }

    public function test_cards_show_the_type_label_for_bullion_only(): void
    {
        Product::factory()->visible()->bar()->create(['en' => ['name' => 'Bar'], 'karat' => 24, 'weight_grams' => 10]);
        Product::factory()->visible()->coin()->create(['en' => ['name' => 'Coin'], 'karat' => 21, 'weight_grams' => 8]);
        Product::factory()->visible()->create(['en' => ['name' => 'Jewel'], 'karat' => 21, 'weight_grams' => 12]);

        $this->get('/en/bullion')
            ->assertOk()
            ->assertSeeInOrder(['<span>Gold bar</span>', '<span>24K</span>', '<span>10 g</span>'], false)
            ->assertSeeInOrder(['<span>Gold coin</span>', '<span>21K</span>'], false);

        $this->get('/en/catalog')->assertOk()->assertDontSee('<span>Gold bar</span>', false)->assertDontSee('<span>Gold coin</span>', false);
    }

    public function test_the_page_has_no_n_plus_one_queries(): void
    {
        $this->seedBullion(2);
        $this->get('/en/bullion')->assertOk();

        $few = $this->countQueries(fn () => $this->get('/en/bullion?sort=name')->assertOk());

        $this->seedBullion(20);
        $many = $this->countQueries(fn () => $this->get('/en/bullion?sort=name')->assertOk());

        $this->assertSame($few, $many);
    }

    private function seedBullion(int $count): void
    {
        $brand = Brand::factory()->create();

        Product::factory()->count($count)->visible()->withImages(2)->bar()->create(['brand_id' => $brand->id]);
        Product::factory()->count($count)->visible()->withImages(2)->coin()->create(['brand_id' => $brand->id]);
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }

    /**
     * @return array<int, string>
     */
    private function titles(string $url): array
    {
        return $this->cardTitles($this->get($url)->assertOk()->getContent());
    }

    /**
     * @return array<int, string>
     */
    private function sorted(string $url): array
    {
        $titles = $this->titles($url);
        sort($titles);

        return $titles;
    }

    /**
     * @return array<int, string>
     */
    private function cardTitles(string $html): array
    {
        preg_match_all('#<h3 class="yq-card__title">(.*?)</h3>#s', $html, $matches);

        return array_map('html_entity_decode', $matches[1]);
    }
}
