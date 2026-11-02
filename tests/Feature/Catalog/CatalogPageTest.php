<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Option\Models\ProductOption;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * /{locale}/catalog: search, filters, sort, pagination, visibility, empty states and queries.
 */
class CatalogPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every test requests /en unless it switches locale itself. refreshApplicationWithLocale()
     * runs setUp() again with the route locale already set, so this reboots only once.
     */
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
    public function test_the_catalog_renders_in_both_locales(string $locale, string $dir): void
    {
        $this->refreshApplicationWithLocale($locale);
        Product::factory()->visible()->create();

        $this->get("/$locale/catalog")
            ->assertOk()
            ->assertSee("<html lang=\"$locale\" dir=\"$dir\"", false)
            ->assertSee('<h1 class="yq-page-header__title">'.__('product::catalog.title', [], $locale).'</h1>', false)
            ->assertSee('id="catalog-filters-panel"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_unprefixed_catalog_urls_redirect_to_english_with_the_query(): void
    {
        $this->refreshApplicationWithLocale('en');
        putenv(LaravelLocalization::ENV_ROUTE_KEY);
        $this->refreshApplication();

        $this->get('/catalog?q=ring&karat=21')->assertRedirect('http://localhost/en/catalog?q=ring&karat=21');
    }

    public function test_only_visible_products_are_listed(): void
    {
        Product::factory()->visible()->create(['en' => ['name' => 'Visible piece']]);
        Product::factory()->withImages()->create(['en' => ['name' => 'Draft piece']]);
        Product::factory()->published()->create(['en' => ['name' => 'Imageless piece']]);
        Product::factory()->visible()->create(['en' => ['name' => 'Trashed piece']])->delete();
        $nameless = Product::factory()->visible()->create(['en' => ['name' => 'Arabic name missing']]);
        $nameless->translations()->where('locale', 'ar')->update(['name' => ' ']);

        $this->assertSame(['Visible piece'], $this->titles('/en/catalog'));
    }

    public function test_search_matches_the_name_in_either_locale_or_the_sku(): void
    {
        Product::factory()->visible()->create(['sku' => 'RING-001', 'en' => ['name' => 'Twisted band'], 'ar' => ['name' => 'خاتم مجدول']]);
        Product::factory()->visible()->create(['sku' => 'DEMO-777', 'en' => ['name' => 'Plain bangle'], 'ar' => ['name' => 'غويشة سادة']]);

        $this->assertSame(['Twisted band'], $this->titles('/en/catalog?q=twisted'));
        $this->assertSame(['Twisted band'], $this->titles('/en/catalog?q=ring-0'));
        $this->assertSame(['Plain bangle'], $this->titles('/en/catalog?q=demo-777'));
        // Arabic typed on the English catalog still finds the piece (shown with its English name).
        $this->assertSame(['Twisted band'], $this->titles('/en/catalog?q='.urlencode('مجدول')));
        $this->assertSame([], $this->titles('/en/catalog?q=missing'));

        $this->refreshApplicationWithLocale('ar');
        Product::factory()->visible()->create(['sku' => 'RING-001', 'en' => ['name' => 'Twisted band'], 'ar' => ['name' => 'خاتم مجدول']]);

        $this->assertSame(['خاتم مجدول'], $this->titles('/ar/catalog?q='.urlencode('مجدول')));
        // English typed on the Arabic catalog, too.
        $this->assertSame(['خاتم مجدول'], $this->titles('/ar/catalog?q=twisted'));
    }

    public function test_a_search_matching_both_names_lists_the_piece_once(): void
    {
        Product::factory()->visible()->create(['en' => ['name' => 'Demo ring'], 'ar' => ['name' => 'Demo خاتم']]);

        $this->assertSame(['Demo ring'], $this->titles('/en/catalog?q=demo'));
        $this->get('/en/catalog?q=demo')->assertSee('1 piece');
    }

    public function test_search_wildcards_are_matched_literally_and_the_term_is_escaped(): void
    {
        Product::factory()->visible()->create(['en' => ['name' => 'Ring 100%']]);
        Product::factory()->visible()->create(['en' => ['name' => 'Ring 1000']]);

        $this->assertSame(['Ring 100%'], $this->titles('/en/catalog?q=100%25'));
        $this->assertSame([], $this->titles('/en/catalog?q=_'));

        $this->get('/en/catalog?q='.urlencode('<script>x</script>'))
            ->assertOk()
            ->assertDontSee('<script>x</script>', false)
            ->assertSee('&lt;script&gt;x&lt;/script&gt;', false);
    }

    public function test_each_attribute_filter_narrows_the_list(): void
    {
        // The options (slugs white, yellow, rose, diamond, none, pearl) come from the data migration.
        $id = fn (string $slug) => ProductOption::where('slug', $slug)->value('id');

        Product::factory()->visible()->create(['en' => ['name' => 'A'], 'karat' => 18, 'gold_color_id' => $id('white'), 'stone_type_id' => $id('diamond'), 'weight_grams' => 4.5]);
        Product::factory()->visible()->create(['en' => ['name' => 'B'], 'karat' => 21, 'gold_color_id' => $id('yellow'), 'stone_type_id' => $id('none'), 'weight_grams' => 12]);
        Product::factory()->visible()->create(['en' => ['name' => 'C'], 'karat' => 21, 'gold_color_id' => $id('rose'), 'stone_type_id' => $id('pearl'), 'weight_grams' => null]);

        $this->assertSame(['A'], $this->sorted('/en/catalog?karat=18'));
        $this->assertSame(['B', 'C'], $this->sorted('/en/catalog?karat=21'));
        $this->assertSame(['C'], $this->sorted('/en/catalog?gold_color=rose'));
        $this->assertSame(['A'], $this->sorted('/en/catalog?stone_type=diamond'));
        $this->assertSame(['B'], $this->sorted('/en/catalog?weight_min=5'));
        $this->assertSame(['A'], $this->sorted('/en/catalog?weight_max=4.5'));
        $this->assertSame(['A', 'B'], $this->sorted('/en/catalog?weight_min=4.5&weight_max=12'));
        // Given the wrong way round, the bounds are swapped.
        $this->assertSame(['A', 'B'], $this->sorted('/en/catalog?weight_min=12&weight_max=4.5'));
    }

    public function test_category_collection_and_brand_filters_use_slugs(): void
    {
        $brand = Brand::factory()->create(['en' => ['name' => 'Demo Maker']]);
        $category = Category::factory()->create(['en' => ['name' => 'Demo Rings']]);
        $collection = Collection::factory()->create(['en' => ['name' => 'Demo Gifts']]);

        $a = Product::factory()->visible()->create(['en' => ['name' => 'A'], 'brand_id' => $brand->id]);
        $b = Product::factory()->visible()->create(['en' => ['name' => 'B']]);
        Product::factory()->visible()->create(['en' => ['name' => 'C']]);
        $a->categories()->attach($category);
        $b->categories()->attach($category);
        $b->collections()->attach($collection);

        $this->assertSame(['A', 'B'], $this->sorted('/en/catalog?category=demo-rings'));
        $this->assertSame(['B'], $this->sorted('/en/catalog?collection=demo-gifts'));
        $this->assertSame(['A'], $this->sorted('/en/catalog?brand=demo-maker'));
    }

    public function test_filters_combine(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Demo Rings']]);

        foreach ([['A', 21, 'Gold band'], ['B', 18, 'Gold band'], ['C', 21, 'Silver tone']] as [$name, $karat, $label]) {
            Product::factory()->visible()->create(['en' => ['name' => "$name $label"], 'karat' => $karat])
                ->categories()->attach($category);
        }
        Product::factory()->visible()->create(['en' => ['name' => 'D Gold band'], 'karat' => 21]);

        $this->assertSame(['A Gold band'], $this->sorted('/en/catalog?q=gold&category=demo-rings&karat=21'));
    }

    public function test_invalid_parameters_are_ignored(): void
    {
        $inactive = Category::factory()->inactive()->create(['en' => ['name' => 'Hidden Rings']]);
        Product::factory()->visible()->create(['en' => ['name' => 'A'], 'karat' => 21])->categories()->attach($inactive);
        Product::factory()->visible()->create(['en' => ['name' => 'B'], 'karat' => 18]);

        foreach ([
            'category=unknown', 'category=hidden-rings', 'collection[]=x', 'brand=',
            'karat=19', 'karat=21abc', 'karat[]=21', 'gold_color=green', 'stone_type=ruby',
            'weight_min=abc', 'weight_max=-3', 'weight_min=1e3', 'weight_max=100000',
            'sort=price', 'sort[]=name', 'q[]=x', 'page=abc',
        ] as $query) {
            $this->assertSame(['A', 'B'], $this->sorted("/en/catalog?$query"), $query);
        }
    }

    public function test_the_sort_allowlist(): void
    {
        Product::factory()->visible()->create(['en' => ['name' => 'Beta'], 'ar' => ['name' => 'ب'], 'weight_grams' => 3, 'published_at' => now()->subDays(3)]);
        Product::factory()->visible()->create(['en' => ['name' => 'Alpha'], 'ar' => ['name' => 'ج'], 'weight_grams' => null, 'published_at' => now()->subDays(1)]);
        Product::factory()->visible()->create(['en' => ['name' => 'Gamma'], 'ar' => ['name' => 'أ'], 'weight_grams' => 9, 'published_at' => now()->subDays(2)]);

        $this->assertSame(['Alpha', 'Gamma', 'Beta'], $this->titles('/en/catalog'));
        $this->assertSame(['Alpha', 'Gamma', 'Beta'], $this->titles('/en/catalog?sort=newest'));
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $this->titles('/en/catalog?sort=name'));
        // Products without a weight come last either way.
        $this->assertSame(['Beta', 'Gamma', 'Alpha'], $this->titles('/en/catalog?sort=weight_asc'));
        $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->titles('/en/catalog?sort=weight_desc'));
        $this->assertSame(['Alpha', 'Gamma', 'Beta'], $this->titles('/en/catalog?sort=price'));

        $this->get('/en/catalog?sort=weight_desc')
            ->assertSee('<option value="weight_desc" selected>', false);
    }

    public function test_the_name_sort_follows_the_current_locale(): void
    {
        $this->refreshApplicationWithLocale('ar');
        Product::factory()->visible()->create(['en' => ['name' => 'Alpha'], 'ar' => ['name' => 'ج']]);
        Product::factory()->visible()->create(['en' => ['name' => 'Beta'], 'ar' => ['name' => 'أ']]);

        $this->assertSame(['أ', 'ج'], $this->titles('/ar/catalog?sort=name'));
    }

    public function test_pagination_shows_24_a_page_and_keeps_the_query_string(): void
    {
        Product::factory()->count(30)->visible()->create(['karat' => 21]);

        $first = $this->get('/en/catalog?karat=21&sort=name')->assertOk();
        $this->assertCount(24, $this->cardTitles($first->getContent()));
        $first->assertSee('30 pieces')
            ->assertSee('href="http://localhost/en/catalog?karat=21&amp;sort=name&amp;page=2"', false);

        $second = $this->get('/en/catalog?karat=21&sort=name&page=2')->assertOk();
        $this->assertCount(6, $this->cardTitles($second->getContent()));
        $second->assertSee('aria-current="page"><span class="page-link">2</span>', false);
    }

    public function test_a_page_past_the_end_redirects_to_the_last_page(): void
    {
        Product::factory()->count(25)->visible()->create(['karat' => 21]);

        $this->get('/en/catalog?karat=21&page=9')
            ->assertRedirect('http://localhost/en/catalog?karat=21&page=2');
    }

    public function test_clear_filters_appears_only_when_filtered_and_keeps_the_sort(): void
    {
        Product::factory()->visible()->create(['karat' => 21]);

        $this->get('/en/catalog')->assertOk()->assertDontSee(__('product::catalog.filters.clear'));
        $this->get('/en/catalog?sort=name')->assertOk()->assertDontSee(__('product::catalog.filters.clear'));

        $this->get('/en/catalog?karat=21&sort=name')
            ->assertOk()
            ->assertSee('href="http://localhost/en/catalog?sort=name" class="yq-underline">Clear filters</a>', false)
            ->assertSee('Filters (1)');

        $this->get('/en/catalog?karat=21&q=demo')
            ->assertOk()
            ->assertSee('href="http://localhost/en/catalog" class="yq-underline">Clear filters</a>', false)
            ->assertSee('Filters (2)');
    }

    public function test_empty_states(): void
    {
        $this->get('/en/catalog')
            ->assertOk()
            ->assertSee(__('product::catalog.empty.title'))
            ->assertSee('No pieces')
            ->assertDontSee('yq-product-grid', false);

        Product::factory()->visible()->create(['karat' => 18]);

        $this->get('/en/catalog?karat=24')
            ->assertOk()
            ->assertSee(__('product::catalog.empty.filtered_title'))
            ->assertSee('href="http://localhost/en/catalog" class="btn btn-outline-gold">Clear filters</a>', false);
    }

    public function test_the_filter_form_offers_only_listed_taxonomies(): void
    {
        $visible = Product::factory()->visible()->create();
        $listed = Category::factory()->create(['en' => ['name' => 'Listed Rings']]);
        $inactive = Category::factory()->inactive()->create(['en' => ['name' => 'Inactive Rings']]);
        Category::factory()->create(['en' => ['name' => 'Empty Rings']]);
        $visible->categories()->attach([$listed->id, $inactive->id]);

        $this->get('/en/catalog?category=listed-rings')
            ->assertOk()
            ->assertSee('<option value="listed-rings" selected>Listed Rings</option>', false)
            ->assertDontSee('Inactive Rings')
            ->assertDontSee('Empty Rings')
            // No brands or collections on the site: those selects are left out.
            ->assertDontSee('id="filter-brand"', false)
            ->assertDontSee('id="filter-collection"', false)
            ->assertSee('id="filter-karat-21"', false);
    }

    public function test_cards_show_brand_karat_and_weight_without_prices(): void
    {
        $brand = Brand::factory()->create(['en' => ['name' => 'Demo Maker']]);
        Product::factory()->visible()->create(['en' => ['name' => 'A'], 'brand_id' => $brand->id, 'karat' => 21, 'weight_grams' => 12.5]);

        $this->get('/en/catalog')
            ->assertOk()
            ->assertSee('<p class="yq-card__brand">Demo Maker</p>', false)
            ->assertSeeInOrder(['<span>21K</span>', '<span>12.5 g</span>'], false)
            ->assertDontSee('EGP')
            ->assertDontSee('price');
    }

    public function test_the_catalog_has_no_n_plus_one_queries(): void
    {
        $this->seedCatalog(2);
        $this->get('/en/catalog')->assertOk(); // warm the settings cache

        $few = $this->countQueries(fn () => $this->get('/en/catalog?sort=name')->assertOk());

        $this->seedCatalog(20);
        $many = $this->countQueries(fn () => $this->get('/en/catalog?sort=name')->assertOk());

        $this->assertSame($few, $many);
    }

    private function seedCatalog(int $count): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();

        Product::factory()->count($count)->visible()->withImages(2)->create(['brand_id' => $brand->id])
            ->each(fn (Product $product) => $product->categories()->attach($category));
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
     * Titles in alphabetical order, for filters whose result order does not matter.
     *
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
