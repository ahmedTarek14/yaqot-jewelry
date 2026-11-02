<?php

namespace Tests\Feature\Analytics;

use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Analytics\Models\ProductDailyStat;
use Modules\Product\Models\Product;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The product page's WhatsApp button goes through /{locale}/go/whatsapp/{slug}: the click is
 * counted, then the visitor is redirected (302) to the same wa.me link the page used to carry.
 */
class WhatsAppRedirectTest extends TestCase
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

    private function configure(): void
    {
        app(SettingService::class)->setMany(['whatsapp_number' => '01012345678']);
    }

    private function visible(array $attributes = []): Product
    {
        return Product::factory()->visible()->create($attributes);
    }

    #[DataProvider('locales')]
    public function test_the_redirect_goes_to_wa_me_with_the_message_in_the_locale_of_the_url(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $this->configure();
        $product = $this->visible(['sku' => 'WA-77']);

        $expected = WhatsApp::link('01012345678', __('front.whatsapp.product_message', [
            'name' => $product->translate($locale)->name,
            'sku' => 'WA-77',
            'url' => route('front.products.show', $product->slug),
        ], $locale));

        $response = $this->get("/$locale/go/whatsapp/{$product->slug}");

        $response->assertStatus(302)->assertRedirect($expected);
        $this->assertStringStartsWith('https://wa.me/201012345678?text=', $response->headers->get('Location'));
        // The message carries the product page in the same locale.
        $this->assertStringContainsString(rawurlencode("/$locale/products/{$product->slug}"), $response->headers->get('Location'));
    }

    public function test_the_response_is_never_cached_or_indexed(): void
    {
        $this->configure();
        $product = $this->visible();

        $response = $this->get("/en/go/whatsapp/{$product->slug}");

        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_a_click_is_counted_per_product_and_day(): void
    {
        $this->configure();
        $this->travelTo('2026-11-02 09:00:00');
        $product = $this->visible();
        $other = $this->visible();

        $this->get("/en/go/whatsapp/{$product->slug}")->assertRedirect();
        $this->get("/en/go/whatsapp/{$product->slug}")->assertRedirect();
        $this->get("/en/go/whatsapp/{$other->slug}")->assertRedirect();
        $this->travelTo('2026-11-03 09:00:00');
        $this->get("/en/go/whatsapp/{$product->slug}")->assertRedirect();

        $this->assertSame(4, ProductDailyStat::sum('whatsapp_clicks'));
        $this->assertSame(0, (int) ProductDailyStat::sum('views'));
        $this->assertSame(2, ProductDailyStat::where('product_id', $product->id)->where('date', '2026-11-02')->sole()->whatsapp_clicks);
        $this->assertSame(1, ProductDailyStat::where('product_id', $other->id)->sole()->whatsapp_clicks);
        $this->assertSame(1, ProductDailyStat::where('product_id', $product->id)->where('date', '2026-11-03')->sole()->whatsapp_clicks);
    }

    public function test_bots_are_redirected_but_not_counted(): void
    {
        $this->configure();
        $product = $this->visible();

        foreach (['Googlebot/2.1', 'curl/8.4.0', 'WhatsApp/2.23', ''] as $agent) {
            $this->withHeaders(['User-Agent' => $agent])->get("/en/go/whatsapp/{$product->slug}")->assertRedirect();
        }
        $this->flushHeaders()->call('HEAD', "/en/go/whatsapp/{$product->slug}");

        $this->assertSame(0, ProductDailyStat::count());
    }

    public function test_it_is_a_404_and_counts_nothing_without_a_valid_whatsapp_number(): void
    {
        $product = $this->visible();

        $this->get("/en/go/whatsapp/{$product->slug}")->assertNotFound();

        app(SettingService::class)->setMany(['whatsapp_number' => '0223456789']); // a landline
        $this->get("/en/go/whatsapp/{$product->slug}")->assertNotFound();

        $this->assertSame(0, ProductDailyStat::count());
    }

    public function test_products_the_public_cannot_see_are_a_404_and_counted_nowhere(): void
    {
        $this->configure();
        $draft = Product::factory()->withImages()->create();
        $trashed = $this->visible();
        $trashed->delete();
        $noImage = Product::factory()->published()->create();

        foreach ([$draft, $trashed, $noImage] as $product) {
            $this->get("/en/go/whatsapp/{$product->slug}")->assertNotFound();
        }
        $this->get('/en/go/whatsapp/does-not-exist')->assertNotFound();

        $this->assertSame(0, ProductDailyStat::count());
    }

    #[DataProvider('locales')]
    public function test_the_product_page_button_points_at_the_redirect(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $this->configure();
        $product = $this->visible();
        $go = route('front.products.whatsapp', $product->slug);

        $this->assertSame("http://localhost/$locale/go/whatsapp/{$product->slug}", $go);

        $this->get("/$locale/products/{$product->slug}")
            ->assertOk()
            ->assertSee('href="'.$go.'" class="btn btn-gold btn-lg" target="_blank" rel="noopener"', false)
            // The product message is no longer in the page; only the footer's general chat link remains.
            ->assertDontSee(rawurlencode('code '.$product->sku), false);
    }

    public function test_the_product_page_has_no_button_without_a_number(): void
    {
        $product = $this->visible();

        $this->get("/en/products/{$product->slug}")->assertOk()->assertDontSee('/go/whatsapp/', false);
    }
}
