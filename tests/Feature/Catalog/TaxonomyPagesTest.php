<?php

namespace Tests\Feature\Catalog;

use Illuminate\Database\Eloquent\Model;
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
 * /{locale}/categories/{slug}, /collections/{slug} and /brands/{slug}: the catalog's filters,
 * sort and pagination scoped to one record, with its own header.
 */
class TaxonomyPagesTest extends TestCase
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
    public static function types(): array
    {
        return ['category' => ['category', 'categories'], 'collection' => ['collection', 'collections'], 'brand' => ['brand', 'brands']];
    }

    /** @return array<string, array{string, string, string}> */
    public static function typesAndLocales(): array
    {
        $cases = [];
        foreach (self::types() as $name => [$type, $path]) {
            $cases["$name arabic"] = [$type, $path, 'ar'];
            $cases["$name english"] = [$type, $path, 'en'];
        }

        return $cases;
    }

    #[DataProvider('typesAndLocales')]
    public function test_each_page_renders_in_both_locales(string $type, string $path, string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $record = $this->record($type, ['en' => ['name' => 'Demo Thing'], 'ar' => ['name' => 'شيء تجريبي']]);
        $this->attachVisible($type, $record, 1);
        $dir = $locale === 'ar' ? 'rtl' : 'ltr';
        $name = $locale === 'ar' ? 'شيء تجريبي' : 'Demo Thing';

        $this->get("/$locale/$path/{$record->slug}")
            ->assertOk()
            ->assertSee("<html lang=\"$locale\" dir=\"$dir\"", false)
            ->assertSee("<h1 class=\"yq-page-header__title\">$name</h1>", false)
            ->assertSee('<title>'.$name.' | ', false)
            ->assertSee('id="catalog-filters-panel"', false)
            ->assertSee('href="http://localhost/'.$locale.'/catalog"', false)
            ->assertSee('aria-current="page"', false);
    }

    #[DataProvider('types')]
    public function test_inactive_missing_and_empty_records_are_404(string $type, string $path): void
    {
        $inactive = $this->record($type, [], inactive: true);
        $this->attachVisible($type, $inactive, 1);
        $empty = $this->record($type);
        $hidden = $this->record($type);
        $this->attachProduct($type, $hidden, Product::factory()->withImages()->create()); // a draft

        $this->get("/en/$path/{$inactive->slug}")->assertNotFound();
        $this->get("/en/$path/{$empty->slug}")->assertNotFound();
        $this->get("/en/$path/{$hidden->slug}")->assertNotFound();
        $this->get("/en/$path/does-not-exist")->assertNotFound();
    }

    #[DataProvider('types')]
    public function test_only_that_records_visible_products_are_listed(string $type, string $path): void
    {
        $record = $this->record($type);
        $other = $this->record($type);
        $this->attachProduct($type, $record, Product::factory()->visible()->create(['en' => ['name' => 'Mine']]));
        $this->attachProduct($type, $record, Product::factory()->published()->create(['en' => ['name' => 'No image']]));
        $this->attachProduct($type, $record, Product::factory()->visible()->create(['en' => ['name' => 'Trashed']]))->delete();
        $this->attachProduct($type, $other, Product::factory()->visible()->create(['en' => ['name' => 'Theirs']]));
        Product::factory()->visible()->create(['en' => ['name' => 'Unrelated']]);

        $this->assertSame(['Mine'], $this->titles("/en/$path/{$record->slug}"));
        $this->get("/en/$path/{$record->slug}")->assertSee('1 piece');
    }

    public function test_the_header_shows_the_description_as_paragraphs_and_the_image(): void
    {
        $category = Category::factory()->create([
            'image_path' => 'categories/demo.webp',
            'en' => ['name' => 'Rings', 'description' => "First line\nsecond line\n\nSecond <b>paragraph</b>"],
        ]);
        $this->attachVisible('category', $category, 1);

        $response = $this->get("/en/categories/{$category->slug}")->assertOk();

        $response->assertSee('<p class="yq-page-header__lead yq-text">First line'."\n".'second line</p>', false)
            ->assertSee('Second &lt;b&gt;paragraph&lt;/b&gt;', false)
            ->assertDontSee('<b>paragraph</b>', false)
            ->assertSee('class="yq-frame yq-frame--landscape yq-taxonomy-header__media"', false)
            ->assertSee('/storage/categories/demo.webp', false);
    }

    public function test_the_description_and_image_are_hidden_when_empty(): void
    {
        $collection = Collection::factory()->create(['image_path' => null]);
        $this->attachVisible('collection', $collection, 1);

        $this->get("/en/collections/{$collection->slug}")
            ->assertOk()
            ->assertDontSee('yq-page-header__lead', false)
            ->assertDontSee('yq-taxonomy-header__media', false);
    }

    public function test_brand_pages_have_neither_description_nor_image(): void
    {
        $brand = Brand::factory()->create(['en' => ['name' => 'Demo Maker']]);
        $this->attachVisible('brand', $brand, 1);

        $this->get("/en/brands/{$brand->slug}")
            ->assertOk()
            ->assertSee('<h1 class="yq-page-header__title">Demo Maker</h1>', false)
            ->assertDontSee('yq-page-header__lead', false)
            ->assertDontSee('yq-taxonomy-header__media', false);
    }

    public function test_the_brand_page_lists_only_that_brands_products(): void
    {
        $brand = Brand::factory()->create();
        $other = Brand::factory()->create();
        Product::factory()->visible()->create(['brand_id' => $brand->id, 'en' => ['name' => 'Brand piece']]);
        Product::factory()->visible()->create(['brand_id' => $other->id, 'en' => ['name' => 'Other brand piece']]);
        Product::factory()->visible()->create(['brand_id' => null, 'en' => ['name' => 'House piece']]);

        $this->assertSame(['Brand piece'], $this->titles("/en/brands/{$brand->slug}"));
    }

    public function test_the_own_filter_is_ignored_but_the_others_apply(): void
    {
        $rings = Category::factory()->create(['en' => ['name' => 'Rings']]);
        $bangles = Category::factory()->create(['en' => ['name' => 'Bangles']]);
        $gifts = Collection::factory()->create(['en' => ['name' => 'Gifts']]);

        $a = Product::factory()->visible()->create(['en' => ['name' => 'A'], 'karat' => 21]);
        $b = Product::factory()->visible()->create(['en' => ['name' => 'B'], 'karat' => 18]);
        $c = Product::factory()->visible()->create(['en' => ['name' => 'C'], 'karat' => 21]);
        $a->categories()->attach($rings);
        $b->categories()->attach($rings);
        $c->categories()->attach($bangles);
        $a->collections()->attach($gifts);

        // The page's own filter (?category=bangles) never widens or changes the list.
        $this->assertSame(['A', 'B'], $this->sorted('/en/categories/rings?category=bangles'));
        $this->assertSame(['A'], $this->sorted('/en/categories/rings?karat=21'));
        $this->assertSame(['A'], $this->sorted('/en/categories/rings?collection=gifts'));
        $this->assertSame(['A'], $this->sorted('/en/categories/rings?category=bangles&collection=gifts&karat=21'));
        $this->assertSame(['B'], $this->sorted('/en/categories/rings?q=b'));
    }

    public function test_the_filter_form_leaves_out_the_pages_own_filter(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Rings']]);
        $collection = Collection::factory()->create(['en' => ['name' => 'Gifts']]);
        $brand = Brand::factory()->create(['en' => ['name' => 'Maker']]);
        $product = Product::factory()->visible()->create(['brand_id' => $brand->id]);
        $product->categories()->attach($category);
        $product->collections()->attach($collection);

        $this->get('/en/categories/rings')->assertOk()
            ->assertDontSee('id="filter-category"', false)
            ->assertSee('id="filter-collection"', false)
            ->assertSee('id="filter-brand"', false);
        $this->get('/en/collections/gifts')->assertOk()
            ->assertDontSee('id="filter-collection"', false)
            ->assertSee('id="filter-category"', false)
            ->assertSee('id="filter-brand"', false);
        $this->get('/en/brands/maker')->assertOk()
            ->assertDontSee('id="filter-brand"', false)
            ->assertSee('id="filter-category"', false)
            ->assertSee('id="filter-collection"', false)
            ->assertSee('action="http://localhost/en/brands/maker"', false);
    }

    public function test_search_attribute_filters_and_the_sort_allowlist_apply(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Rings']]);
        foreach ([['Beta', 3, 21, 'white'], ['Alpha', null, 18, 'yellow'], ['Gamma', 9, 21, 'yellow']] as [$name, $weight, $karat, $color]) {
            Product::factory()->visible()->create([
                'en' => ['name' => $name], 'ar' => ['name' => $name], 'weight_grams' => $weight, 'karat' => $karat, 'gold_color' => $color,
            ])->categories()->attach($category);
        }

        $this->assertSame(['Gamma'], $this->titles('/en/categories/rings?q=gamma'));
        $this->assertSame(['Beta', 'Gamma'], $this->sorted('/en/categories/rings?karat=21'));
        $this->assertSame(['Alpha', 'Gamma'], $this->sorted('/en/categories/rings?gold_color=yellow'));
        $this->assertSame(['Gamma'], $this->sorted('/en/categories/rings?weight_min=5'));
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $this->titles('/en/categories/rings?sort=name'));
        $this->assertSame(['Beta', 'Gamma', 'Alpha'], $this->titles('/en/categories/rings?sort=weight_asc'));
        $this->assertSame(['Gamma', 'Beta', 'Alpha'], $this->titles('/en/categories/rings?sort=weight_desc'));
    }

    public function test_invalid_parameters_are_ignored(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Rings']]);
        foreach (['A', 'B'] as $name) {
            Product::factory()->visible()->create(['en' => ['name' => $name]])->categories()->attach($category);
        }

        foreach ([
            'category[]=x', 'collection=unknown', 'brand=', 'karat=19', 'karat[]=21', 'gold_color=green',
            'stone_type=ruby', 'weight_min=abc', 'weight_max=-3', 'sort=price', 'sort[]=name', 'q[]=x', 'page=abc',
        ] as $query) {
            $this->assertSame(['A', 'B'], $this->sorted("/en/categories/rings?$query"), $query);
        }
    }

    public function test_pagination_keeps_the_query_string_and_a_page_past_the_end_redirects(): void
    {
        $category = Category::factory()->create(['en' => ['name' => 'Rings']]);
        Product::factory()->count(30)->visible()->create(['karat' => 21])
            ->each(fn (Product $product) => $product->categories()->attach($category));
        Product::factory()->count(3)->visible()->create(['karat' => 21]);

        $first = $this->get('/en/categories/rings?karat=21&sort=name')->assertOk();
        $this->assertCount(24, $this->cardTitles($first->getContent()));
        $first->assertSee('30 pieces')
            ->assertSee('href="http://localhost/en/categories/rings?karat=21&amp;sort=name&amp;page=2"', false);

        $second = $this->get('/en/categories/rings?karat=21&sort=name&page=2')->assertOk();
        $this->assertCount(6, $this->cardTitles($second->getContent()));

        $this->get('/en/categories/rings?karat=21&page=9')
            ->assertRedirect('http://localhost/en/categories/rings?karat=21&page=2');
    }

    public function test_a_filtered_empty_state_clears_to_the_page_and_keeps_the_sort(): void
    {
        $collection = Collection::factory()->create(['en' => ['name' => 'Gifts']]);
        Product::factory()->visible()->create(['karat' => 18])->collections()->attach($collection);

        $this->get('/en/collections/gifts')->assertOk()
            ->assertDontSee(__('product::catalog.filters.clear'))
            ->assertDontSee(__('product::catalog.empty.title'));

        $this->get('/en/collections/gifts?karat=24&sort=name')
            ->assertOk()
            ->assertSee(__('product::catalog.empty.filtered_title'))
            ->assertDontSee('yq-product-grid', false)
            ->assertSee('href="http://localhost/en/collections/gifts?sort=name" class="btn btn-outline-gold">Clear filters</a>', false)
            ->assertSee('href="http://localhost/en/collections/gifts?sort=name" class="yq-underline">Clear filters</a>', false);
    }

    #[DataProvider('types')]
    public function test_the_page_has_no_n_plus_one_queries(string $type, string $path): void
    {
        $record = $this->record($type);
        $this->attachVisible($type, $record, 2, withBrand: true);
        $url = "/en/$path/{$record->slug}?sort=name";
        $this->get($url)->assertOk(); // warm the settings cache

        $few = $this->countQueries(fn () => $this->get($url)->assertOk());

        $this->attachVisible($type, $record, 20, withBrand: true);
        $many = $this->countQueries(fn () => $this->get($url)->assertOk());

        $this->assertSame($few, $many);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function record(string $type, array $attributes = [], bool $inactive = false): Model
    {
        $factory = match ($type) {
            'category' => Category::factory(),
            'collection' => Collection::factory(),
            'brand' => Brand::factory(),
        };

        return $factory->when($inactive, fn ($factory) => $factory->inactive())->create($attributes);
    }

    private function attachProduct(string $type, Model $record, Product $product): Product
    {
        match ($type) {
            'category' => $product->categories()->attach($record),
            'collection' => $product->collections()->attach($record),
            'brand' => $product->update(['brand_id' => $record->getKey()]),
        };

        return $product;
    }

    private function attachVisible(string $type, Model $record, int $count, bool $withBrand = false): void
    {
        $brand = $withBrand && $type !== 'brand' ? Brand::factory()->create() : null;

        Product::factory()->count($count)->visible()->withImages(2)->create(['brand_id' => $brand?->id])
            ->each(fn (Product $product) => $this->attachProduct($type, $record, $product));
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
