<?php

namespace Tests\Feature\Home;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Admin dashboard home: catalog figures and the recently updated products.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function locales(): array
    {
        return ['arabic' => ['ar'], 'english' => ['en']];
    }

    private function signIn(string $locale = 'en'): static
    {
        $this->refreshApplicationWithLocale($locale);

        return $this->actingAs(Admin::factory()->create(), 'admin');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function product(string $en, string $ar, array $attributes = []): Product
    {
        return Product::factory()->create([...$attributes, 'en' => ['name' => $en], 'ar' => ['name' => $ar]]);
    }

    private function statValue(int $value): string
    {
        return '<p class="yq-stat__value mb-0">'.$value.'</p>';
    }

    #[DataProvider('locales')]
    public function test_guests_are_redirected_to_the_localized_login(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);

        $this->get("/$locale/admin")->assertRedirect("http://localhost/$locale/admin/login");
    }

    public function test_users_of_the_web_guard_cannot_reach_the_dashboard(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->actingAs(User::factory()->create())
            ->get('/en/admin')
            ->assertRedirect('http://localhost/en/admin/login');
    }

    #[DataProvider('locales')]
    public function test_an_empty_catalog_shows_zeros_and_the_empty_state(string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin")
            ->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('home::home.dashboard.intro', [], $locale))
            ->assertSeeInOrder([
                __('home::home.dashboard.stats.products', [], $locale), $this->statValue(0),
                __('home::home.dashboard.stats.published', [], $locale), $this->statValue(0),
                __('home::home.dashboard.stats.drafts', [], $locale), $this->statValue(0),
                __('home::home.dashboard.stats.active_categories', [], $locale), $this->statValue(0),
            ], false)
            ->assertSee(__('home::home.dashboard.recent.title', [], $locale))
            ->assertSee(__('home::home.dashboard.recent.empty', [], $locale))
            ->assertSee('href="'.route('admin.products.create').'"', false)
            ->assertDontSee('<table', false)
            ->assertDontSee('empty_stats');
    }

    #[DataProvider('locales')]
    public function test_the_figures_exclude_trashed_products_and_inactive_categories(string $locale): void
    {
        $this->signIn($locale);
        Product::factory()->published()->count(3)->create();
        Product::factory()->count(2)->create();
        Product::factory()->published()->create()->delete();
        Product::factory()->create()->delete();
        Category::factory()->count(2)->create();
        Category::factory()->create(['is_active' => false]);

        $this->get("/$locale/admin")
            ->assertOk()
            ->assertSeeInOrder([
                __('home::home.dashboard.stats.products', [], $locale), $this->statValue(5),
                __('home::home.dashboard.stats.published', [], $locale), $this->statValue(3),
                __('home::home.dashboard.stats.drafts', [], $locale), $this->statValue(2),
                __('home::home.dashboard.stats.active_categories', [], $locale), $this->statValue(2),
            ], false);
    }

    #[DataProvider('locales')]
    public function test_the_stat_cards_link_to_the_filtered_indexes(string $locale): void
    {
        $this->signIn($locale);

        $this->get("/$locale/admin")
            ->assertOk()
            ->assertSeeInOrder([
                'href="'.route('admin.products.index').'"',
                __('home::home.dashboard.stats.products_link', [], $locale),
                'href="'.route('admin.products.index', ['status' => 'published']).'"',
                __('home::home.dashboard.stats.published_link', [], $locale),
                'href="'.route('admin.products.index', ['status' => 'draft']).'"',
                __('home::home.dashboard.stats.drafts_link', [], $locale),
                'href="'.route('admin.categories.index', ['status' => 'active']).'"',
                __('home::home.dashboard.stats.active_categories_link', [], $locale),
            ], false)
            ->assertSee("http://localhost/$locale/admin/products?status=draft", false)
            ->assertSee("http://localhost/$locale/admin/categories?status=active", false);
    }

    #[DataProvider('locales')]
    public function test_the_recent_list_shows_the_latest_eight_untrashed_products(string $locale): void
    {
        $this->signIn($locale);
        $start = Carbon::parse('2026-01-01 10:00:00');

        $products = collect(range(1, 10))->map(fn (int $n) => $this->product(
            "Demo Recent $n",
            "قطعة حديثة $n",
            ['updated_at' => $start->copy()->addDays($n), 'is_published' => $n % 2 === 0],
        ));
        $trashed = $this->product('Demo Trashed Piece', 'قطعة محذوفة', ['updated_at' => $start->copy()->addDays(30)]);
        $trashed->delete();

        $newestFirst = $products->reverse()->take(8);

        $response = $this->get("/$locale/admin")
            ->assertOk()
            ->assertSee(__('admin.status.published', [], $locale))
            ->assertSee(__('admin.status.draft', [], $locale))
            ->assertDontSee('Demo Trashed Piece')
            ->assertDontSee('قطعة محذوفة')
            ->assertDontSee('Demo Recent 1<', false)
            ->assertDontSee('Demo Recent 2<', false)
            ->assertSeeInOrder($newestFirst->map(fn (Product $p) => 'href="'.route('admin.products.edit', $p).'"')->all(), false)
            ->assertSee('<time datetime="'.$products[9]->updated_at->toIso8601String().'"', false)
            ->assertSee('href="'.route('admin.products.index').'"', false)
            ->assertSee('href="'.route('admin.products.create').'"', false)
            ->assertDontSee(__('home::home.dashboard.recent.empty', [], $locale));

        foreach ($newestFirst as $product) {
            $response->assertSee($product->translate('en')->name)->assertSee($product->translate('ar')->name);
        }

        $this->assertSame(8, substr_count($response->getContent(), 'class="yq-badge '));
    }

    public function test_rendering_several_products_with_images_does_not_lazy_load(): void
    {
        Storage::fake('public');
        $this->signIn();
        Product::factory()->published()->withImages(2)->count(4)->create();
        Product::factory()->count(3)->create();

        $response = $this->get('/en/admin')->assertOk();

        $this->assertSame(4, substr_count($response->getContent(), '<img src="'.Storage::disk('public')->url('products/')));
    }
}
