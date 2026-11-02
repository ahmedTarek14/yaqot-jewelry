<?php

namespace Tests\Feature\Analytics;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Auth\Models\Admin;
use Modules\Product\Models\Product;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Google Analytics 4 is optional (Setting "ga_measurement_id"). Without an id the public site has
 * no notice, no script and no cookie. With one it renders a hidden cookie notice; Google itself is
 * injected by JavaScript only after the visitor accepts, so the server HTML never names Google.
 */
class GoogleAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private const ID = 'G-ABC123XYZ9';

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

    private function settings(): SettingService
    {
        return app(SettingService::class);
    }

    /**
     * @return array<int, string> public paths (without locale) that exercise the public layout
     */
    private function publicPaths(Product $product): array
    {
        return ['', '/catalog', '/bullion', '/about', '/contact', '/privacy-policy', "/products/{$product->slug}"];
    }

    #[DataProvider('locales')]
    public function test_without_an_id_no_public_page_has_any_google_or_notice_output(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $product = Product::factory()->visible()->create();

        foreach ($this->publicPaths($product) as $path) {
            $response = $this->get("/$locale$path")->assertOk();
            $html = $response->getContent();

            $this->assertStringNotContainsStringIgnoringCase('googletagmanager', $html, $path);
            $this->assertStringNotContainsStringIgnoringCase('google-analytics', $html, $path);
            $this->assertStringNotContainsString('data-analytics-consent', $html, $path);
            $this->assertStringNotContainsString('yq-consent', $html, $path);
            $this->assertStringNotContainsString('analytics.js', $html, $path);
            $this->assertStringNotContainsString('data-consent-open', $html, $path);
            $this->assertStringNotContainsString('gtag', $html, $path);

            foreach ($response->headers->getCookies() as $cookie) {
                $this->assertNotSame('yq_consent', $cookie->getName(), $path);
            }
        }
    }

    #[DataProvider('locales')]
    public function test_with_an_id_the_notice_is_in_the_html_but_google_is_not(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $this->settings()->set('ga_measurement_id', self::ID);
        $product = Product::factory()->visible()->create();

        foreach ($this->publicPaths($product) as $path) {
            $response = $this->get("/$locale$path")->assertOk();
            $html = $response->getContent();

            // Hidden until analytics.js reveals it: no JavaScript means no notice and no tracking.
            $this->assertMatchesRegularExpression(
                '/<div class="yq-consent" role="region" aria-label="[^"]+" data-analytics-consent\s+data-measurement-id="'.self::ID.'" hidden>/',
                $html,
                $path,
            );
            $this->assertStringContainsString(__('analytics::analytics.consent.accept', [], $locale), $html);
            $this->assertStringContainsString(__('analytics::analytics.consent.decline', [], $locale), $html);
            $this->assertSame(2, substr_count($html, 'data-consent-choice='), $path);
            $this->assertStringContainsString('<button type="button" class="btn btn-outline-gold" data-consent-choice="granted">', $html);
            $this->assertStringContainsString('href="'.route('front.privacy').'"', $html);
            $this->assertStringContainsString('assets/js/analytics.js?v=', $html, $path);

            // Nothing from Google in the server HTML, and the server sets no consent cookie.
            $this->assertStringNotContainsStringIgnoringCase('googletagmanager', $html, $path);
            $this->assertStringNotContainsString('gtag(', $html, $path);
            foreach ($response->headers->getCookies() as $cookie) {
                $this->assertNotSame('yq_consent', $cookie->getName(), $path);
            }
        }
    }

    public function test_a_malformed_stored_id_renders_nothing(): void
    {
        // Settings are validated on save; even a bad stored value must never reach an attribute or URL.
        $this->settings()->set('ga_measurement_id', '"><script>alert(1)</script>');

        $this->get('/en')->assertOk()
            ->assertDontSee('data-analytics-consent', false)
            ->assertDontSee('alert(1)', false);
    }

    public function test_the_script_only_loads_google_after_consent_and_keeps_the_choice_first_party(): void
    {
        $js = file_get_contents(public_path('assets/js/analytics.js'));

        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=', $js);
        $this->assertStringContainsString('yq_consent', $js);
        $this->assertStringContainsString('SameSite=Lax', $js);
        $this->assertStringContainsString('Secure', $js);
        $this->assertStringContainsString('anonymize_ip: true', $js);
        $this->assertStringContainsString('ga-disable-', $js);
        $this->assertStringNotContainsString('onclick', $js);
    }

    #[DataProvider('locales')]
    public function test_admin_pages_never_carry_the_notice_or_google(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $this->settings()->set('ga_measurement_id', self::ID);

        $check = function (string $path) use ($locale): void {
            $html = $this->get("/$locale$path")->assertOk()->getContent();

            $this->assertStringNotContainsStringIgnoringCase('googletagmanager', $html, $path);
            $this->assertStringNotContainsString('data-analytics-consent', $html, $path);
            $this->assertStringNotContainsString('yq-consent', $html, $path);
            $this->assertStringNotContainsString('analytics.js', $html, $path);
            $this->assertStringNotContainsString(self::ID, $html, $path);
        };

        $check('/admin/login');

        $this->actingAs(Admin::factory()->create(), 'admin');

        foreach (['/admin', '/admin/products', '/admin/categories'] as $path) {
            $check($path);
        }
    }

    public function test_the_settings_form_shows_the_field_with_its_hint(): void
    {
        $this->refreshApplicationWithLocale('en');
        $this->settings()->set('ga_measurement_id', self::ID);
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->get('/en/admin/settings')
            ->assertOk()
            ->assertSee('id="field-ga_measurement_id"', false)
            ->assertSee('name="ga_measurement_id"', false)
            ->assertSee('value="'.self::ID.'"', false)
            ->assertSee(__('setting::setting.fields.ga_measurement_id_help'))
            ->assertDontSee('data-analytics-consent', false);
    }

    /** @return array<string, array{string, ?string}> */
    public static function validIds(): array
    {
        return [
            'standard' => ['G-ABC123XYZ9', 'G-ABC123XYZ9'],
            'lowercase is normalised' => ['g-abc123xyz9', 'G-ABC123XYZ9'],
            'trimmed' => ['  G-ABC123XYZ9  ', 'G-ABC123XYZ9'],
            'shortest' => ['G-ABCD', 'G-ABCD'],
            'longest' => ['G-ABCDEFGHIJKL', 'G-ABCDEFGHIJKL'],
        ];
    }

    #[DataProvider('validIds')]
    public function test_a_valid_measurement_id_is_saved_normalised(string $input, string $stored): void
    {
        $this->refreshApplicationWithLocale('en');
        $this->actingAs(Admin::factory()->create(), 'admin');

        $this->putJson('/en/admin/settings', ['ga_measurement_id' => $input])->assertOk();

        $this->assertSame($stored, $this->settings()->get('ga_measurement_id'));
    }

    /** @return array<string, array{string}> */
    public static function invalidIds(): array
    {
        return [
            'universal analytics' => ['UA-12345-1'],
            'too short' => ['G-AB'],
            'too long' => ['G-ABCDEFGHIJKLM'],
            'no prefix' => ['ABC123XYZ9'],
            'symbols' => ['G-ABC_123'],
            'script' => ['G-ABC"><script>'],
            'url' => ['https://www.googletagmanager.com/gtag/js?id=G-ABC123'],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_an_invalid_measurement_id_is_rejected_in_both_languages(string $input): void
    {
        foreach (['en', 'ar'] as $locale) {
            $this->refreshApplicationWithLocale($locale);
            $this->actingAs(Admin::factory()->create(), 'admin');

            $this->putJson("/$locale/admin/settings", ['ga_measurement_id' => $input])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['ga_measurement_id'])
                ->assertJsonPath('errors.ga_measurement_id.0', __('setting::setting.validation.ga_measurement_id', [], $locale));

            $this->assertNull($this->settings()->get('ga_measurement_id'));
        }
    }

    public function test_an_empty_measurement_id_clears_the_setting_and_turns_tracking_off(): void
    {
        $this->refreshApplicationWithLocale('en');
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->settings()->set('ga_measurement_id', self::ID);

        $this->get('/en')->assertSee('data-analytics-consent', false);

        $this->putJson('/en/admin/settings', ['ga_measurement_id' => ''])->assertOk();
        $this->assertNull($this->settings()->get('ga_measurement_id'));

        $this->putJson('/en/admin/settings', ['ga_measurement_id' => '   '])->assertOk();
        $this->assertNull($this->settings()->get('ga_measurement_id'));

        $this->get('/en')->assertDontSee('data-analytics-consent', false);
    }

    #[DataProvider('locales')]
    public function test_the_privacy_page_switches_with_the_setting(string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $t = fn (string $key) => __("page::privacy.$key", [], $locale);

        // Off: plain texts, the always-on counters, no Google and no cookie settings button.
        $this->get("/$locale/privacy-policy")
            ->assertOk()
            ->assertSee($t('sections.cookies.text'))
            ->assertSee($t('sections.third_party.text'))
            ->assertSee($t('sections.counters.title'))
            ->assertSee($t('sections.counters.text'))
            ->assertDontSee($t('sections_ga.analytics.text'))
            ->assertDontSee($t('sections_ga.cookies.text'))
            ->assertDontSee('data-consent-open', false)
            ->assertDontSee('googletagmanager', false);

        $this->settings()->set('ga_measurement_id', self::ID);

        // On: the Google texts replace the "no cookies / no outside analytics" ones that would now be false.
        $this->get("/$locale/privacy-policy")
            ->assertOk()
            ->assertSee($t('sections_ga.cookies.text'))
            ->assertSee($t('sections_ga.third_party.text'))
            ->assertSee($t('sections_ga.analytics.title'))
            ->assertSee($t('sections_ga.analytics.text'))
            ->assertSee($t('sections.counters.text'))
            ->assertSee($t('cookie_settings.button'))
            ->assertDontSee($t('sections.cookies.text'))
            ->assertDontSee($t('sections.third_party.text'))
            ->assertSee('data-consent-open hidden', false);
    }

    public function test_the_counters_are_described_in_both_states_without_claiming_personal_data(): void
    {
        foreach (['en', 'ar'] as $locale) {
            $text = __('page::privacy.sections.counters.text', [], $locale);

            $this->assertStringContainsString('13', $text);
            $this->assertNotSame('page::privacy.sections.counters.text', $text);
        }
    }
}
