<?php

namespace Tests\Feature\Home;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Brand\Models\Brand;
use Modules\Category\Models\Category;
use Modules\Collection\Models\Collection;
use Modules\Product\Models\Product;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HomePageTest extends TestCase
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

    private function settings(): SettingService
    {
        return app(SettingService::class);
    }

    /** @return array<string, array{string, string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar', 'rtl'], 'english' => ['en', 'ltr']];
    }

    #[DataProvider('locales')]
    public function test_the_home_page_renders_in_both_locales(string $locale, string $dir): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->get("/$locale")
            ->assertOk()
            ->assertSee("<html lang=\"$locale\" dir=\"$dir\"", false);
    }

    public function test_an_empty_site_shows_only_the_hero_with_safe_fallbacks(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('<h1 class="yq-hero__title" id="yq-hero-title" data-enter>Yaqot</h1>', false)
            ->assertSee('href="http://localhost/en/catalog" class="btn btn-gold">Browse jewelry</a>', false)
            ->assertDontSee('yq-hero__subtitle', false)
            ->assertDontSee('yq-hero__media', false)
            ->assertDontSee('id="home-categories"', false)
            ->assertDontSee('id="home-featured"', false)
            ->assertDontSee('id="home-collections"', false)
            ->assertDontSee('id="home-brands"', false)
            ->assertDontSee('id="home-latest"', false)
            ->assertDontSee('id="home-about"', false)
            ->assertDontSee('id="home-contact"', false)
            ->assertDontSee('wa.me', false);
    }

    public function test_the_hero_comes_from_settings(): void
    {
        $this->settings()->setMany([
            'hero_title' => ['ar' => 'عنوان تجريبي', 'en' => 'Demo hero title'],
            'hero_subtitle' => ['ar' => 'فرعي', 'en' => 'Demo subtitle'],
            'hero_cta_label' => ['ar' => 'تصفح', 'en' => 'See the pieces'],
            'hero_cta_url' => 'https://example.com/landing',
            'hero_image' => 'settings/hero.webp',
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('Demo hero title')
            ->assertSee('<p class="yq-hero__subtitle" data-enter>Demo subtitle</p>', false)
            ->assertSee('href="https://example.com/landing" class="btn btn-gold">See the pieces</a>', false)
            ->assertSee('yq-hero yq-hero--with-image', false)
            ->assertSee('src="/storage/settings/hero.webp"', false);
    }

    public function test_hero_settings_are_escaped(): void
    {
        $this->settings()->set('hero_title', ['ar' => 'x', 'en' => '<script>alert(1)</script>']);

        $this->get('/en')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_featured_shows_only_visible_featured_products(): void
    {
        $shown = Product::factory()->visible()->featured()->create(['en' => ['name' => 'Shown featured']]);
        Product::factory()->featured()->withImages()->create(['en' => ['name' => 'Draft featured']]);
        Product::factory()->featured()->published()->create(['en' => ['name' => 'Imageless featured']]);

        $response = $this->get('/en')->assertOk()->assertSee('id="home-featured"', false);

        $this->assertSame(['Shown featured'], $this->cardTitlesIn($response->getContent(), 'home-featured'));
        $response->assertDontSee('Draft featured')->assertDontSee('Imageless featured');
        $this->assertTrue($shown->exists);
    }

    public function test_latest_additions_skip_pieces_already_featured(): void
    {
        Product::factory()->visible()->featured()->create(['en' => ['name' => 'Featured piece'], 'published_at' => now()]);
        Product::factory()->visible()->create(['en' => ['name' => 'Older piece'], 'published_at' => now()->subDays(2)]);
        Product::factory()->visible()->create(['en' => ['name' => 'Newer piece'], 'published_at' => now()->subDay()]);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertSame(['Featured piece'], $this->cardTitlesIn($html, 'home-featured'));
        $this->assertSame(['Newer piece', 'Older piece'], $this->cardTitlesIn($html, 'home-latest'));
    }

    public function test_categories_are_the_active_ones_with_visible_products(): void
    {
        $product = Product::factory()->visible()->create();
        $draft = Product::factory()->withImages()->create();

        $listed = Category::factory()->create(['en' => ['name' => 'Listed rings'], 'sort_order' => 1]);
        $inactive = Category::factory()->inactive()->create(['en' => ['name' => 'Inactive category']]);
        $onlyDrafts = Category::factory()->create(['en' => ['name' => 'Drafts only']]);
        Category::factory()->create(['en' => ['name' => 'Empty category']]);

        $product->categories()->attach([$listed->id, $inactive->id]);
        $draft->categories()->attach($onlyDrafts);

        $this->get('/en')
            ->assertOk()
            ->assertSee('id="home-categories"', false)
            ->assertSee('Listed rings')
            ->assertSee('href="http://localhost/en/catalog?category='.$listed->slug.'"', false)
            ->assertDontSee('Inactive category')
            ->assertDontSee('Drafts only')
            ->assertDontSee('Empty category');
    }

    public function test_the_prominent_collection_setting_puts_it_first_and_large(): void
    {
        $product = Product::factory()->visible()->create();

        $first = Collection::factory()->create(['en' => ['name' => 'Wedding'], 'show_on_homepage' => true, 'sort_order' => 1]);
        $chosen = Collection::factory()->create(['en' => ['name' => 'Engagement'], 'show_on_homepage' => true, 'sort_order' => 5]);
        $hidden = Collection::factory()->create(['en' => ['name' => 'Not on homepage'], 'show_on_homepage' => false]);
        $product->collections()->attach([$first->id, $chosen->id, $hidden->id]);

        $this->settings()->set('prominent_collection', (string) $chosen->id);

        $this->get('/en')
            ->assertOk()
            ->assertSee('yq-collection-grid has-prominent', false)
            ->assertSeeInOrder(['class="is-prominent"', 'Engagement', 'Wedding'], false)
            ->assertDontSee('Not on homepage');
    }

    public function test_without_the_setting_no_collection_is_prominent_even_shabka(): void
    {
        $product = Product::factory()->visible()->create();
        $shabka = Collection::factory()->create(['en' => ['name' => 'Shabka'], 'show_on_homepage' => true]);
        $product->collections()->attach($shabka);
        $this->assertSame('shabka', $shabka->slug);

        $this->get('/en')
            ->assertOk()
            ->assertSee('Shabka')
            ->assertDontSee('has-prominent', false)
            ->assertDontSee('is-prominent', false);
    }

    public function test_a_prominent_collection_that_cannot_be_shown_is_ignored(): void
    {
        $product = Product::factory()->visible()->create();
        $shown = Collection::factory()->create(['en' => ['name' => 'Gifts'], 'show_on_homepage' => true]);
        $offHome = Collection::factory()->create(['en' => ['name' => 'Off homepage'], 'show_on_homepage' => false]);
        $empty = Collection::factory()->create(['en' => ['name' => 'No pieces'], 'show_on_homepage' => true]);
        $product->collections()->attach([$shown->id, $offHome->id]);

        foreach ([(string) $offHome->id, (string) $empty->id, '999999'] as $id) {
            $this->settings()->set('prominent_collection', $id);

            $this->get('/en')
                ->assertOk()
                ->assertSee('Gifts')
                ->assertDontSee('has-prominent', false)
                ->assertDontSee('Off homepage')
                ->assertDontSee('No pieces');
        }
    }

    public function test_the_brands_strip_lists_active_brands_with_visible_products(): void
    {
        $listed = Brand::factory()->create(['en' => ['name' => 'Listed brand']]);
        $inactive = Brand::factory()->create(['en' => ['name' => 'Inactive brand'], 'is_active' => false]);
        Brand::factory()->create(['en' => ['name' => 'Brand without pieces']]);

        Product::factory()->visible()->create(['brand_id' => $listed->id]);
        Product::factory()->visible()->create(['brand_id' => $inactive->id]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('id="home-brands"', false)
            ->assertSee('href="http://localhost/en/catalog?brand='.$listed->slug.'" class="yq-brands__link">Listed brand</a>', false)
            ->assertDontSee('Inactive brand')
            ->assertDontSee('Brand without pieces');
    }

    public function test_the_about_intro_shows_the_first_paragraph(): void
    {
        $this->settings()->set('about_content', [
            'ar' => "فقرة أولى\n\nفقرة ثانية",
            'en' => "First paragraph\nsame paragraph\n\nSecond paragraph",
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('id="home-about"', false)
            ->assertSee("First paragraph\nsame paragraph", false)
            ->assertDontSee('Second paragraph');
    }

    public function test_the_contact_section_uses_whatsapp_and_the_phone(): void
    {
        $this->settings()->setMany(['whatsapp_number' => '201012345678', 'phone' => '0223456789']);

        $this->get('/en')
            ->assertOk()
            ->assertSee('id="home-contact"', false)
            ->assertSee('href="https://wa.me/201012345678?text='.rawurlencode(__('front.whatsapp.general_message')).'"', false)
            ->assertSee('href="tel:+20223456789"', false);
    }

    public function test_the_contact_section_shows_only_what_is_configured(): void
    {
        $this->settings()->set('phone', '0223456789');

        $this->get('/en')
            ->assertOk()
            ->assertSee('id="home-contact"', false)
            ->assertSee('href="tel:+20223456789"', false)
            ->assertDontSee('wa.me', false);
    }

    public function test_the_home_page_has_no_n_plus_one_queries(): void
    {
        $this->seedHomeContent(2);
        $this->get('/en')->assertOk(); // warm the settings cache

        $few = $this->countQueries(fn () => $this->get('/en')->assertOk());

        $this->seedHomeContent(6);
        $many = $this->countQueries(fn () => $this->get('/en')->assertOk());

        $this->assertSame($few, $many);
    }

    private function seedHomeContent(int $count): void
    {
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $collection = Collection::factory()->create(['show_on_homepage' => true]);

        Product::factory()->count($count)->visible()->featured()->create(['brand_id' => $brand->id])
            ->merge(Product::factory()->count($count)->visible()->withImages(2)->create(['brand_id' => $brand->id]))
            ->each(function (Product $product) use ($category, $collection) {
                $product->categories()->attach($category);
                $product->collections()->attach($collection);
            });
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
     * The product card titles inside the section labelled by $id.
     *
     * @return array<int, string>
     */
    private function cardTitlesIn(string $html, string $id): array
    {
        $start = strpos($html, "aria-labelledby=\"$id\"");
        $this->assertNotFalse($start, "section $id not found");
        $section = substr($html, $start, strpos($html, '</section>', $start) - $start);

        preg_match_all('#<h3 class="yq-card__title">(.*?)</h3>#s', $section, $matches);

        return array_map('html_entity_decode', $matches[1]);
    }
}
