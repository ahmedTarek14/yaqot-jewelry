<?php

namespace Tests\Feature\Product;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Option\Models\ProductOption;
use Modules\Product\Models\Product;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductPageTest extends TestCase
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

    private function visible(array $attributes = []): Product
    {
        return Product::factory()->visible()->create($attributes);
    }

    #[DataProvider('locales')]
    public function test_a_visible_product_renders_in_both_locales(string $locale, string $dir): void
    {
        $this->refreshApplicationWithLocale($locale);
        $product = $this->visible(['sku' => 'YQ-100']);

        $this->get("/$locale/products/{$product->slug}")
            ->assertOk()
            ->assertSee("<html lang=\"$locale\" dir=\"$dir\"", false)
            ->assertSee('<h1 class="yq-product__title">'.$product->translate($locale)->name.'</h1>', false)
            ->assertSee('YQ-100');
    }

    public function test_unavailable_products_are_404(): void
    {
        $draft = Product::factory()->withImages()->create();
        $unpublished = Product::factory()->withImages()->create(['is_published' => false]);
        $trashed = $this->visible();
        $trashed->delete();
        $noImage = Product::factory()->published()->create();
        $noArabicName = $this->visible(['ar' => ['name' => '  ']]);

        foreach ([$draft, $unpublished, $trashed, $noImage, $noArabicName] as $product) {
            $this->get("/en/products/{$product->slug}")->assertNotFound();
        }

        $this->get('/en/products/does-not-exist')->assertNotFound();
    }

    public function test_the_title_has_the_product_and_its_brand(): void
    {
        $brand = Brand::factory()->create(['en' => ['name' => 'Maison Test'], 'ar' => ['name' => 'ميزون']]);
        $product = $this->visible(['brand_id' => $brand->id, 'en' => ['name' => 'Lotus Ring', 'description' => null]]);

        $this->get("/en/products/{$product->slug}")
            ->assertSee('<title>Lotus Ring | Maison Test | ', false);
    }

    public function test_empty_attributes_are_hidden(): void
    {
        $product = $this->visible([
            'karat' => null, 'gold_color_id' => null, 'stone_type_id' => null, 'weight_grams' => null,
            'sku' => 'ONLY-SKU',
        ]);

        $this->get("/en/products/{$product->slug}")
            ->assertOk()
            ->assertSee('ONLY-SKU')
            ->assertSee('<dt>Code</dt>', false)
            ->assertDontSee('<dt>Karat</dt>', false)
            ->assertDontSee('<dt>Gold colour</dt>', false)
            ->assertDontSee('<dt>Stones</dt>', false)
            ->assertDontSee('<dt>Weight</dt>', false);
    }

    public function test_filled_attributes_are_listed_with_their_labels(): void
    {
        $product = $this->visible([
            'karat' => 21, 'weight_grams' => '12.500',
            'gold_color_id' => ProductOption::where('slug', 'rose')->value('id'),
            'stone_type_id' => ProductOption::where('slug', 'diamond')->value('id'),
        ]);

        $this->get("/en/products/{$product->slug}")
            ->assertSee('<dd>21K</dd>', false)
            ->assertSee('<dd>Rose gold</dd>', false)
            ->assertSee('<dd>Diamond</dd>', false)
            ->assertSee('<dd>12.5 g</dd>', false)
            ->assertDontSee('<dt>Type</dt>', false);
    }

    public function test_a_deactivated_option_is_hidden_on_the_product_page(): void
    {
        $rose = ProductOption::where('slug', 'rose')->sole();
        $product = $this->visible(['gold_color_id' => $rose->id, 'stone_type_id' => ProductOption::where('slug', 'pearl')->value('id')]);
        $rose->update(['is_active' => false]);

        $this->get("/en/products/{$product->slug}")
            ->assertDontSee('<dt>Gold colour</dt>', false)
            ->assertDontSee('Rose gold')
            ->assertSee('<dd>Pearl</dd>', false);
    }

    public function test_a_bar_shows_its_type_but_no_gold_colour_or_stones(): void
    {
        $brand = Brand::factory()->create(['en' => ['name' => 'Bar Maker'], 'ar' => ['name' => 'مصنع']]);
        $bar = Product::factory()->bar()->visible()->create(['brand_id' => $brand->id, 'karat' => 24, 'weight_grams' => 10]);
        // Even a stale gold colour on a bar is never shown.
        $bar->update(['gold_color_id' => ProductOption::where('slug', 'rose')->value('id')]);
        $category = Category::factory()->create(['en' => ['name' => 'Rings']]);
        $bar->categories()->attach($category);
        $other = Product::factory()->coin()->visible()->create();
        $jewel = Product::factory()->visible()->create();
        $jewel->categories()->attach($category);

        $response = $this->get("/en/products/{$bar->slug}")->assertOk();
        $response
            ->assertSee('<dt>Type</dt>', false)
            ->assertSee('<dd>Gold bar</dd>', false)
            ->assertSee('<dd>24K</dd>', false)
            ->assertDontSee('<dt>Gold colour</dt>', false)
            ->assertDontSee('<dt>Stones</dt>', false)
            ->assertDontSee('Rose gold')
            ->assertSee(route('front.bullion'), false)
            ->assertSee(route('front.bullion', ['brand' => $brand->slug]), false)
            ->assertDontSee(route('front.brands.show', $brand->slug), false)
            ->assertDontSee(route('front.categories.show', $category->slug), false)
            ->assertSee(route('front.products.show', $other->slug), false)
            ->assertDontSee(route('front.products.show', $jewel->slug), false);
    }

    public function test_an_empty_description_is_hidden_and_a_filled_one_shows_paragraphs(): void
    {
        $empty = $this->visible(['en' => ['name' => 'No Text', 'description' => null]]);
        $filled = $this->visible(['en' => ['name' => 'With Text', 'description' => "First part.\n\nSecond part."]]);

        $this->get("/en/products/{$empty->slug}")->assertDontSee('About this piece');
        $this->get("/en/products/{$filled->slug}")
            ->assertSee('About this piece')
            ->assertSee('<p class="yq-text">First part.</p>', false)
            ->assertSee('<p class="yq-text">Second part.</p>', false);
    }

    public function test_an_active_brand_is_shown_and_linked_but_a_deactivated_one_is_hidden(): void
    {
        $active = Brand::factory()->create(['en' => ['name' => 'Visible House'], 'ar' => ['name' => 'بيت ظاهر']]);
        $inactive = Brand::factory()->inactive()->create(['en' => ['name' => 'Hidden House'], 'ar' => ['name' => 'بيت مخفي']]);
        $first = $this->visible(['brand_id' => $active->id]);
        $second = $this->visible(['brand_id' => $inactive->id]);

        $this->get("/en/products/{$first->slug}")
            ->assertSee('Visible House')
            ->assertSee('/en/brands/'.$active->slug);
        $this->get("/en/products/{$second->slug}")
            ->assertOk()
            ->assertDontSee('Hidden House')
            ->assertDontSee('/en/brands/'.$inactive->slug);
    }

    public function test_only_active_categories_and_collections_are_linked(): void
    {
        $product = $this->visible();
        $category = Category::factory()->create();
        $hiddenCategory = Category::factory()->inactive()->create();
        $collection = Collection::factory()->create();
        $hiddenCollection = Collection::factory()->inactive()->create();
        $product->categories()->attach([$category->id, $hiddenCategory->id]);
        $product->collections()->attach([$collection->id, $hiddenCollection->id]);

        $this->get("/en/products/{$product->slug}")
            ->assertOk()
            ->assertSee('href="http://localhost/en/categories/'.$category->slug.'"', false)
            ->assertSee('href="http://localhost/en/collections/'.$collection->slug.'"', false)
            ->assertDontSee('/categories/'.$hiddenCategory->slug)
            ->assertDontSee('/collections/'.$hiddenCollection->slug);
    }

    public function test_breadcrumbs_run_from_the_catalog_through_the_category_to_the_product(): void
    {
        $product = $this->visible(['en' => ['name' => 'Crumb Piece', 'description' => null]]);
        $category = Category::factory()->create();
        $product->categories()->attach($category);

        $html = $this->get("/en/products/{$product->slug}")->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<ol class="breadcrumb">.*href="http://localhost/en/catalog".*href="http://localhost/en/categories/'.$category->slug.'".*<li class="breadcrumb-item active"\s+aria-current="page"\s*>Crumb Piece</li>#s',
            $html
        );
    }

    public function test_breadcrumbs_skip_the_category_when_there_is_none(): void
    {
        $product = $this->visible();

        $this->get("/en/products/{$product->slug}")
            ->assertOk()
            ->assertDontSee('/en/categories/');
    }

    public function test_related_products_share_a_category_exclude_itself_and_drafts(): void
    {
        $category = Category::factory()->create();
        $other = Category::factory()->create();
        $product = $this->visible();
        $sibling = $this->visible(['en' => ['name' => 'Sibling Piece', 'description' => null]]);
        $draft = Product::factory()->withImages()->create(['en' => ['name' => 'Draft Sibling', 'description' => null]]);
        $stranger = $this->visible(['en' => ['name' => 'Stranger Piece', 'description' => null]]);
        $product->categories()->attach($category);
        $sibling->categories()->attach($category);
        $draft->categories()->attach($category);
        $stranger->categories()->attach($other);

        $html = $this->get("/en/products/{$product->slug}")
            ->assertOk()
            ->assertSee('id="product-related"', false)
            ->assertSee('Sibling Piece')
            ->assertDontSee('Draft Sibling')
            ->assertDontSee('Stranger Piece')
            ->getContent();

        $this->assertSame(1, substr_count($html, '<h3 class="yq-card__title">'));
    }

    public function test_related_products_are_limited_and_newest_first(): void
    {
        $category = Category::factory()->create();
        $product = $this->visible();
        $product->categories()->attach($category);

        foreach (range(1, 6) as $n) {
            $this->visible([
                'published_at' => now()->subDays(10 - $n),
                'en' => ['name' => "Related $n", 'description' => null],
            ])->categories()->attach($category);
        }

        $html = $this->get("/en/products/{$product->slug}")->getContent();

        $this->assertSame((int) config('catalog.front.related'), substr_count($html, '<h3 class="yq-card__title">'));
        $this->assertLessThan(strpos($html, 'Related 5'), strpos($html, 'Related 6'));
        $this->assertStringNotContainsString('Related 1<', $html);
    }

    public function test_there_is_no_related_section_without_a_category_or_siblings(): void
    {
        $alone = $this->visible();
        $categorised = $this->visible();
        $categorised->categories()->attach(Category::factory()->create());

        $this->get("/en/products/{$alone->slug}")->assertDontSee('id="product-related"', false);
        $this->get("/en/products/{$categorised->slug}")->assertDontSee('id="product-related"', false);
    }

    public function test_the_whatsapp_button_links_to_the_store_with_the_product_message(): void
    {
        app(SettingService::class)->setMany(['whatsapp_number' => '01012345678']);
        $product = $this->visible(['sku' => 'WA-7', 'en' => ['name' => 'Chat Piece', 'description' => null]]);
        $url = route('front.products.show', $product->slug);
        $expected = 'https://wa.me/201012345678?text='.rawurlencode(
            "Hello Yaqot, I am interested in this piece:\nChat Piece (code WA-7)\n$url\nIs it available?"
        );

        // Phase 4d: the button points at the counting redirect, which sends visitors to the same wa.me link.
        $go = route('front.products.whatsapp', $product->slug);

        $this->get("/en/products/{$product->slug}")
            ->assertOk()
            ->assertSee('href="'.e($go).'" class="btn btn-gold btn-lg" target="_blank" rel="noopener"', false)
            ->assertSee('(opens in a new tab)');

        $this->get($go)->assertRedirect($expected);
    }

    public function test_the_whatsapp_message_is_in_the_arabic_locale(): void
    {
        $this->refreshApplicationWithLocale('ar');
        app(SettingService::class)->setMany(['whatsapp_number' => '01012345678']);
        $product = $this->visible(['sku' => 'WA-8']);

        $this->get("/ar/products/{$product->slug}")->assertOk()
            ->assertSee('href="'.e(route('front.products.whatsapp', $product->slug)).'"', false);

        // The Arabic message is built by the redirect, in the locale of its URL.
        $html = $this->get("/ar/go/whatsapp/{$product->slug}")->assertRedirect()->headers->get('Location');

        $this->assertStringContainsString('https://wa.me/201012345678?text='.rawurlencode('مرحبًا ياقوت'), $html);
        $this->assertStringContainsString(rawurlencode($product->translate('ar')->name), $html);
        $this->assertStringContainsString(rawurlencode('WA-8'), $html);
        $this->assertStringContainsString(rawurlencode(route('front.products.show', $product->slug)), $html);
    }

    public function test_without_a_whatsapp_number_the_button_is_absent_and_the_page_still_works(): void
    {
        $product = $this->visible();

        $this->get("/en/products/{$product->slug}")
            ->assertOk()
            ->assertDontSee('wa.me', false)
            ->assertDontSee('Message us on WhatsApp')
            ->assertSee('Our prices follow the daily gold price.');
    }

    public function test_an_invalid_whatsapp_number_hides_the_button(): void
    {
        app(SettingService::class)->setMany(['whatsapp_number' => '12345']);
        $product = $this->visible();

        $this->get("/en/products/{$product->slug}")->assertOk()->assertDontSee('wa.me', false);
    }

    public function test_gallery_markup_with_several_images(): void
    {
        $product = Product::factory()->published()->withImages(3)->create(['en' => ['name' => 'Gallery Piece', 'description' => null]]);

        $html = $this->get("/en/products/{$product->slug}")->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'data-gallery-thumb data-image-url'));
        $this->assertSame(3, substr_count($html, 'class="yq-gallery__thumb"'));
        $this->assertSame(1, substr_count($html, 'aria-pressed="true"'));
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('Image 1 of 3', $html);
        $this->assertMatchesRegularExpression('#<img src="[^"]+" alt="Gallery Piece" width="800" height="1000"\s+fetchpriority="high"[^>]*data-gallery-main>#', $html);
        $this->assertDoesNotMatchRegularExpression('#<img[^>]*data-gallery-main[^>]*data-reveal#', $html);
        $this->assertStringNotContainsString('yq-gallery" data-reveal', $html);
        $this->assertStringContainsString('<img src="'.$product->images->first()->url.'" alt="Gallery Piece"', $html);
    }

    public function test_a_single_image_has_no_thumbnails(): void
    {
        $product = $this->visible();

        $this->get("/en/products/{$product->slug}")
            ->assertOk()
            ->assertSee('data-gallery-main', false)
            ->assertDontSee('data-gallery-thumb', false)
            ->assertDontSee('data-gallery-caption', false);
    }

    public function test_the_page_has_no_prices(): void
    {
        foreach (['en', 'ar'] as $locale) {
            // Rebooting the app gives a fresh in-memory database, so the data is built per locale.
            $this->refreshApplicationWithLocale($locale);
            app(SettingService::class)->setMany(['whatsapp_number' => '01012345678']);
            $category = Category::factory()->create();
            $product = $this->visible();
            $product->categories()->attach($category);
            $this->visible()->categories()->attach($category);

            $text = strip_tags($this->get("/$locale/products/{$product->slug}")->getContent());

            // «جنيه» is the currency, but «جنيهات» (the nav: bars and coins) and «جنيه ذهب» (a gold coin) are not.
            $this->assertDoesNotMatchRegularExpression('/(EGP|LE\b|E£|\$|€|جنيه(?!ات| ذهب)|ج\.م|\d\s*(%|off))/iu', $text);
        }
    }

    public function test_cards_link_to_the_product_page(): void
    {
        $product = $this->visible();

        $this->get('/en/catalog')->assertSee('href="http://localhost/en/products/'.$product->slug.'"', false);
        $this->get("/en/products/{$product->slug}")->assertOk();
    }

    public function test_the_page_renders_with_empty_settings(): void
    {
        $product = $this->visible();

        $this->assertDatabaseCount('settings', 0);
        $this->get("/en/products/{$product->slug}")->assertOk();
    }

    public function test_query_count_does_not_grow_with_images_categories_or_related_products(): void
    {
        $few = $this->visible(['brand_id' => Brand::factory()->create()->id]);
        $many = $this->visible(['brand_id' => Brand::factory()->create()->id]);
        $this->fill($few, 1, 1);

        $manyImages = Product::factory()->published()->withImages(8)->create(['brand_id' => $many->brand_id]);
        $this->fill($manyImages, 4, 4, 7);

        // Warm the settings cache so both measured requests start from the same state.
        $this->get("/en/products/{$few->slug}")->assertOk();

        $fewCount = $this->countQueries(fn () => $this->get("/en/products/{$few->slug}")->assertOk());
        $manyCount = $this->countQueries(fn () => $this->get("/en/products/{$manyImages->slug}")->assertOk());

        $this->assertSame($fewCount, $manyCount);
    }

    private function fill(Product $product, int $categories, int $collections, int $siblings = 1): void
    {
        $categoryModels = Category::factory()->count($categories)->create();
        $product->categories()->attach($categoryModels->modelKeys());
        $product->collections()->attach(Collection::factory()->count($collections)->create()->modelKeys());

        foreach (range(1, $siblings) as $ignored) {
            $this->visible(['brand_id' => Brand::factory()->create()->id])->categories()->attach($categoryModels->modelKeys());
        }
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }
}
