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
            ->assertSee('<h1 class="yq-hero__title" data-enter>Your Story Begins in Gold</h1>', false)
            ->assertSee('<h2 class="yq-hero__title" data-enter>Details Worthy of Your Elegance</h2>', false)
            ->assertSee('Gold creations designed to become part of your most beautiful moments.')
            ->assertSee('href="http://localhost/en/catalog" class="btn btn-gold yq-hero__cta">Discover Our Collection</a>', false)
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
            'hero_1_title' => ['ar' => 'عنوان تجريبي', 'en' => 'Demo hero title'],
            'hero_1_subtitle' => ['ar' => 'فرعي', 'en' => 'Demo subtitle'],
            'hero_1_cta_label' => ['ar' => 'تصفح', 'en' => 'See the pieces'],
            'hero_1_cta_url' => 'https://example.com/landing',
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('<h1 class="yq-hero__title" data-enter>Demo hero title</h1>', false)
            ->assertSee('<p class="yq-hero__subtitle" data-enter>Demo subtitle</p>', false)
            ->assertSee('href="https://example.com/landing" class="btn btn-gold yq-hero__cta">See the pieces</a>', false)
            // Slides 2 and 3 are untouched: their fallbacks.
            ->assertSee('Gold That Speaks of You');
    }

    public function test_each_slide_has_its_own_text_and_button(): void
    {
        $this->settings()->setMany([
            'hero_2_title' => ['ar' => 'ثانية', 'en' => 'Second'],
            'hero_2_cta_url' => '/bullion',
            'hero_3_cta_label' => ['ar' => 'الأساور', 'en' => 'See bangles'],
            'hero_3_cta_url' => 'https://example.com/bangles',
        ]);

        $this->get('/en')
            ->assertOk()
            ->assertSee('<h2 class="yq-hero__title" data-enter>Second</h2>', false)
            ->assertSee('href="http://localhost/en/bullion" class="btn btn-gold yq-hero__cta">Discover Our Collection</a>', false)
            ->assertSee('href="https://example.com/bangles" class="btn btn-gold yq-hero__cta">See bangles</a>', false);
    }

    public function test_a_site_path_keeps_the_visitors_language_unless_it_names_one(): void
    {
        $this->settings()->setMany(['hero_1_cta_url' => '/bullion', 'hero_2_cta_url' => '/ar/catalog', 'hero_3_cta_url' => '/']);

        $this->get('/en')
            ->assertSee('href="http://localhost/en/bullion"', false)
            ->assertSee('href="http://localhost/ar/catalog"', false)
            ->assertSee('href="http://localhost/en" class="btn btn-gold yq-hero__cta"', false);
    }

    public function test_a_stored_unsafe_link_falls_back_to_the_catalog(): void
    {
        // Saved before the rule existed, or straight into the table.
        $this->settings()->setMany(['hero_1_cta_url' => 'javascript:alert(1)', 'hero_2_cta_url' => '//evil.example.com']);

        $this->get('/en')
            ->assertOk()
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('evil.example.com', false)
            ->assertSee('href="http://localhost/en/catalog" class="btn btn-gold yq-hero__cta"', false);
    }

    public function test_the_hero_renders_one_item_per_enabled_slide_with_controls(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('data-hero-carousel', false)
            ->assertSee('data-bs-slide-to="2"', false)
            ->assertDontSee('data-bs-slide-to="3"', false)
            ->assertSee('data-hero-toggle', false);

        $this->assertSame(3, substr_count($this->get('/en')->getContent(), 'class="carousel-item yq-hero__slide'));
        $this->assertSame(1, substr_count($this->get('/en')->getContent(), 'carousel-item yq-hero__slide active'));
    }

    public function test_a_disabled_slide_is_skipped(): void
    {
        $this->settings()->setMany(['hero_2_enabled' => '0']);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="carousel-item yq-hero__slide'));
        $this->assertStringNotContainsString('Details Worthy of Your Elegance', $html);
        $this->assertStringNotContainsString('necklace-1x.webp', $html);
        $this->assertStringContainsString('ring-1x.webp', $html);
        $this->assertStringContainsString('bangles-1x.webp', $html);
        $this->assertStringContainsString('data-bs-slide-to="1"', $html);
        $this->assertStringNotContainsString('data-bs-slide-to="2"', $html);
    }

    public function test_with_one_slide_enabled_there_are_no_controls_and_no_autoplay(): void
    {
        $this->settings()->setMany(['hero_1_enabled' => '0', 'hero_3_enabled' => '0']);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="carousel-item yq-hero__slide'));
        $this->assertStringContainsString('<h1 class="yq-hero__title" data-enter>Details Worthy of Your Elegance</h1>', $html);
        foreach (['data-hero-carousel', 'data-hero-toggle', 'carousel-indicators', 'data-bs-slide-to', 'role="region" aria-roledescription="carousel"'] as $absent) {
            $this->assertStringNotContainsString($absent, $html);
        }
    }

    public function test_with_every_slide_disabled_slide_one_shows_with_its_fallback_text(): void
    {
        $this->settings()->setMany([
            'hero_1_title' => ['ar' => 'عنوان', 'en' => 'Saved title'],
            'hero_1_enabled' => '0',
            'hero_2_enabled' => '0',
            'hero_3_enabled' => '0',
        ]);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="carousel-item yq-hero__slide'));
        $this->assertStringContainsString('<h1 class="yq-hero__title" data-enter>Your Story Begins in Gold</h1>', $html);
        $this->assertStringNotContainsString('Saved title', $html);
        $this->assertStringContainsString('ring-1x.webp', $html);
        $this->assertStringNotContainsString('data-hero-carousel', $html);
    }

    #[DataProvider('locales')]
    public function test_the_fallback_texts_follow_the_language(string $locale, string $dir): void
    {
        $this->refreshApplicationWithLocale($locale);
        $titles = ['ar' => ['حكايتكِ من الذهب تبدأ هنا', 'تفاصيل تليق بأناقتكِ', 'ذهب يحكي عنكِ'], 'en' => ['Your Story Begins in Gold', 'Details Worthy of Your Elegance', 'Gold That Speaks of You']];

        $response = $this->get("/$locale")->assertOk();

        foreach ($titles[$locale] as $title) {
            $response->assertSee($title);
        }

        $response->assertSee($locale === 'ar' ? 'اكتشفي مجموعتنا' : 'Discover Our Collection')
            ->assertSee('YAQOT JEWELRY');
    }

    public function test_the_slides_use_the_fixed_images_with_translated_alt_text(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertStringContainsString('alt="Gold engagement ring with a diamond"', $html);
        $this->assertStringContainsString('alt="Gold necklace set with diamonds and a pear-shaped drop"', $html);
        $this->assertStringContainsString('alt="Three gold bangles set with diamonds"', $html);

        foreach (['ring', 'necklace', 'bangles'] as $name) {
            $this->assertMatchesRegularExpression("#/assets/images/hero/$name-1x\.webp\?v=\d+ \d+w, [^ ]+/assets/images/hero/$name-2x\.webp\?v=\d+ \d+w#", $html);
        }

        // The first slide's jewelry and the shared background (the LCP element on phones) are fetched
        // first; the other slides' jewelry loads lazily.
        $this->assertSame(2, substr_count($html, 'fetchpriority="high"'));
        $this->assertMatchesRegularExpression('#<img src="[^"]*silk-2k\.webp[^>]*fetchpriority="high"#', $html);
        $this->assertSame(2, preg_match_all('#<div class="yq-hero__float">\s*<img[^>]*\sloading="lazy"#', $html));
        $this->assertMatchesRegularExpression('#<img src="[^"]*ring-1x\.webp[^>]*fetchpriority="high"#', $html);
    }

    public function test_the_background_is_decorative_and_a_phone_never_asks_for_the_4k_file(): void
    {
        $html = $this->get('/en')->assertOk()->getContent();

        preg_match('#<picture class="yq-hero__bg" aria-hidden="true">(.*?)</picture>#s', $html, $picture);
        $this->assertNotEmpty($picture, 'The shared background is a decorative <picture>.');

        preg_match_all('#<source media="([^"]+)" srcset="([^"?]+)#', $picture[1], $sources, PREG_SET_ORDER);
        $byFile = [];
        foreach ($sources as [, $media, $file]) {
            $byFile[basename($file)] = $media;
        }

        // Portrait phones: the small file, listed before any larger source.
        $this->assertSame('(max-width: 767.98px) and (orientation: portrait)', $byFile['silk-mobile.webp']);
        $this->assertSame(['silk-mobile.webp', 'silk-4k.webp'], array_map('basename', array_column($sources, 2)));
        // The 4K file only matches very wide viewports, so a phone cannot match it.
        $this->assertSame('(min-width: 2200px)', $byFile['silk-4k.webp']);
        // Everything else gets the 2K file, and it is the only <img> source: the 4K one never is.
        $this->assertMatchesRegularExpression('#<img src="[^"]*silk-2k\.webp[^"]*" alt="" #', $picture[1]);
        $this->assertSame(1, substr_count($html, 'silk-4k.webp'));
        $this->assertSame(1, substr_count($html, 'silk-mobile.webp'));
        $this->assertSame(1, substr_count($html, 'silk-2k.webp'));
    }

    public function test_the_header_is_transparent_on_the_homepage_only(): void
    {
        $this->get('/en')->assertSee('<header class="yq-header yq-header--overlay" data-header>', false);
        $this->get('/en/catalog')->assertDontSee('yq-header--overlay', false);
    }

    public function test_the_hero_has_a_scroll_cue_to_the_next_section(): void
    {
        $this->get('/en')
            ->assertSee('href="#after-hero" class="yq-hero__scroll"', false)
            ->assertSee('id="after-hero"', false);
    }

    public function test_the_controls_have_translated_accessible_labels(): void
    {
        $this->get('/en')
            ->assertSee('aria-label="Pause slideshow"', false)
            ->assertSee('data-label-play="Play slideshow"', false)
            ->assertSee('aria-label="Show slide 2"', false)
            ->assertSee('aria-label="Slide 1 of 3"', false);

        $this->refreshApplicationWithLocale('ar');

        $this->get('/ar')
            ->assertSee('aria-label="إيقاف العرض مؤقتًا"', false)
            ->assertSee('aria-label="الشريحة 1 من 3"', false);
    }

    public function test_hero_settings_are_escaped(): void
    {
        $this->settings()->set('hero_1_title', ['ar' => 'x', 'en' => '<script>alert(1)</script>']);

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
            ->assertSee('href="http://localhost/en/categories/'.$listed->slug.'"', false)
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
            ->assertSee('href="http://localhost/en/brands/'.$listed->slug.'" class="yq-brands__link">Listed brand</a>', false)
            ->assertDontSee('Inactive brand')
            ->assertDontSee('Brand without pieces');
    }

    public function test_the_brands_strip_is_a_marquee_with_one_accessible_list(): void
    {
        $brands = Brand::factory()->count(3)->create();
        foreach ($brands as $brand) {
            Product::factory()->visible()->create(['brand_id' => $brand->id]);
        }

        $html = $this->get('/en')->assertOk()->getContent();

        // 3 names are repeated until each half has at least 24: 8 lists per half, 2 halves.
        $this->assertSame(16, substr_count($html, 'class="yq-marquee__list"'));
        $this->assertSame(2, substr_count($html, 'class="yq-marquee__half"'));
        $this->assertStringContainsString('style="--yq-marquee-items: 24"', $html);

        // Exactly one list is exposed (labelled by the heading); every other copy, and the whole
        // second half, is aria-hidden.
        $this->assertSame(1, preg_match_all('/class="yq-marquee__list"\s+aria-labelledby="home-brands"/', $html));
        $this->assertSame(15, preg_match_all('/class="yq-marquee__list"\s+aria-hidden="true"/', $html));
        $this->assertSame(1, preg_match_all('/<div class="yq-marquee__half"\s+aria-hidden="true"\s*>/', $html));

        // The first copy's links are normal; all 45 others are out of the tab order.
        foreach ($brands as $brand) {
            $url = 'http://localhost/en/brands/'.$brand->slug;
            $this->assertSame(1, substr_count($html, 'href="'.$url.'" class="yq-brands__link">'), 'one focusable link per brand');
            $this->assertSame(15, substr_count($html, 'href="'.$url.'" class="yq-brands__link" tabindex="-1">'), 'copies are not focusable');
        }
    }

    public function test_a_long_brand_list_is_not_repeated_more_than_needed(): void
    {
        $brands = Brand::factory()->count(12)->create();
        foreach ($brands as $brand) {
            Product::factory()->visible()->create(['brand_id' => $brand->id]);
        }

        $html = $this->get('/en')->assertOk()->getContent();

        // 12 names twice per half: 24 names per half, 4 lists in all.
        $this->assertSame(4, substr_count($html, 'class="yq-marquee__list"'));
        $this->assertStringContainsString('--yq-marquee-items: 24"', $html);
    }

    public function test_a_single_brand_still_fills_the_marquee(): void
    {
        $brand = Brand::factory()->create();
        Product::factory()->visible()->create(['brand_id' => $brand->id]);

        $html = $this->get('/en')->assertOk()->getContent();

        $this->assertSame(48, substr_count($html, 'class="yq-brands__link"'));
        $this->assertStringContainsString('--yq-marquee-items: 24"', $html);
    }

    public function test_the_brands_strip_is_hidden_without_brands(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertDontSee('yq-marquee', false)
            ->assertDontSee('yq-brands', false)
            ->assertDontSee('id="home-brands"', false);
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
