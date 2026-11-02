<?php

namespace Tests\Feature\Home;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The homepage's gold bars and coins section, and the jewelry-only featured and latest lists.
 */
class HomeBullionSectionTest extends TestCase
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
    public function test_the_section_shows_with_bullion_and_links_to_the_page(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        Product::factory()->visible()->bar()->create();

        $this->get("/$locale")
            ->assertOk()
            ->assertSee('aria-labelledby="home-bullion"', false)
            ->assertSee(__('home::home.sections.bullion', [], $locale))
            ->assertSee("href=\"http://localhost/$locale/bullion\"", false);
    }

    public function test_the_section_is_hidden_without_visible_bullion(): void
    {
        Product::factory()->visible()->create();
        Product::factory()->withImages()->bar()->create(); // draft
        Product::factory()->published()->coin()->create(); // no image

        $this->get('/en')->assertOk()->assertDontSee('home-bullion');
    }

    public function test_the_section_lists_the_newest_bullion_up_to_the_limit(): void
    {
        foreach (range(1, 6) as $day) {
            Product::factory()->visible()->coin()->create(['en' => ['name' => "Coin $day"], 'published_at' => now()->subDays($day)]);
        }

        $html = $this->get('/en')->assertOk()->getContent();
        preg_match_all('#<h3 class="yq-card__title">(.*?)</h3>#s', $html, $matches);

        $this->assertSame(['Coin 1', 'Coin 2', 'Coin 3', 'Coin 4'], $matches[1]);
    }

    public function test_featured_and_latest_list_jewelry_only(): void
    {
        Product::factory()->visible()->featured()->bar()->create(['en' => ['name' => 'Featured bar']]);
        Product::factory()->visible()->featured()->create(['en' => ['name' => 'Featured jewel']]);
        Product::factory()->visible()->coin()->create(['en' => ['name' => 'Latest coin']]);
        Product::factory()->visible()->create(['en' => ['name' => 'Latest jewel']]);

        $html = $this->get('/en')->assertOk()->getContent();

        $featured = $this->between($html, 'aria-labelledby="home-featured"', '</section>');
        $this->assertStringContainsString('Featured jewel', $featured);
        $this->assertStringNotContainsString('bar', strtolower($featured));

        $latest = $this->between($html, 'aria-labelledby="home-latest"', '</section>');
        $this->assertStringContainsString('Latest jewel', $latest);
        $this->assertStringNotContainsString('coin', strtolower($latest));

        $bullion = $this->between($html, 'aria-labelledby="home-bullion"', '</section>');
        $this->assertStringContainsString('Featured bar', $bullion);
        $this->assertStringContainsString('Latest coin', $bullion);
        $this->assertStringNotContainsString('jewel', strtolower($bullion));
    }

    public function test_the_home_page_has_no_n_plus_one_queries_with_bullion(): void
    {
        Product::factory()->count(2)->visible()->withImages(2)->bar()->create();
        $this->get('/en')->assertOk();

        $few = $this->countQueries(fn () => $this->get('/en')->assertOk());

        Product::factory()->count(10)->visible()->withImages(2)->coin()->create();
        $many = $this->countQueries(fn () => $this->get('/en')->assertOk());

        $this->assertSame($few, $many);
    }

    private function between(string $html, string $start, string $end): string
    {
        $from = strpos($html, $start);
        $this->assertNotFalse($from, $start);

        return substr($html, $from, strpos($html, $end, $from) - $from);
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
