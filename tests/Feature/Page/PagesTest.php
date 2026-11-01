<?php

namespace Tests\Feature\Page;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Modules\Branch\Models\Branch;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * /{locale}/about, /contact and /privacy-policy: content only from Settings and Branches,
 * hidden when not configured, never a 404 or an error on an empty site.
 */
class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv(LaravelLocalization::ENV_ROUTE_KEY) === false) {
            $this->refreshApplicationWithLocale('en');
        }
    }

    /** @return array<string, array{string, string, string}> */
    public static function pagesAndLocales(): array
    {
        $cases = [];
        foreach (['about' => 'about', 'contact' => 'contact', 'privacy' => 'privacy-policy'] as $page => $path) {
            $cases["$page arabic"] = [$page, $path, 'ar'];
            $cases["$page english"] = [$page, $path, 'en'];
        }

        return $cases;
    }

    #[DataProvider('pagesAndLocales')]
    public function test_every_page_renders_on_an_empty_site_in_both_locales(string $page, string $path, string $locale): void
    {
        $this->refreshApplicationWithLocale($locale);
        $dir = $locale === 'ar' ? 'rtl' : 'ltr';
        $title = __("page::$page.title", [], $locale);

        $response = $this->get("/$locale/$path")
            ->assertOk()
            ->assertSee("<html lang=\"$locale\" dir=\"$dir\"", false)
            ->assertSee("<h1 class=\"yq-page-header__title\">$title</h1>", false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('tel:', false)
            ->assertDontSee('mailto:', false)
            ->assertDontSee('wa.me', false);

        // A missing translation key would be printed as the key itself.
        $this->assertStringNotContainsString('page::', $response->getContent());
        $this->assertNotSame("page::$page.title", $title);
    }

    public function test_empty_about_and_contact_pages_show_a_calm_empty_state(): void
    {
        $this->get('/en/about')
            ->assertOk()
            ->assertSee(__('page::about.empty_title'))
            ->assertDontSee('yq-cta', false);

        $this->get('/en/contact')
            ->assertOk()
            ->assertSee(__('page::contact.empty_title'))
            ->assertDontSee('yq-branch-grid', false)
            ->assertDontSee('yq-contact__direct', false);
    }

    public function test_the_about_text_comes_from_settings_as_escaped_paragraphs(): void
    {
        app(SettingService::class)->setMany([
            'about_content' => ['ar' => 'نص عربي', 'en' => "First line\nsecond line\n\nSecond <b>paragraph</b> & more"],
            'whatsapp_number' => '01012345678',
            'phone' => '19123',
        ]);
        $this->assertSame('About us', __('page::about.title'));

        $this->get('/en/about')
            ->assertOk()
            ->assertDontSee(__('page::about.empty_title'))
            ->assertSee('<p class="yq-prose__text yq-text" data-reveal>First line'."\n".'second line</p>', false)
            ->assertSee('Second &lt;b&gt;paragraph&lt;/b&gt; &amp; more', false)
            ->assertDontSee('<b>paragraph</b>', false)
            ->assertSee('href="https://wa.me/201012345678?text=', false)
            ->assertSee('href="tel:19123"', false)
            ->assertSee('href="http://localhost/en/contact"', false);
    }

    public function test_the_arabic_about_text_comes_from_the_arabic_setting(): void
    {
        $this->refreshApplicationWithLocale('ar');
        app(SettingService::class)->setMany(['about_content' => ['ar' => 'نص عربي', 'en' => 'English text']]);

        $this->get('/ar/about')->assertOk()->assertSee('نص عربي')->assertDontSee('English text');
    }

    public function test_the_about_call_to_action_needs_a_whatsapp_number_or_phone(): void
    {
        app(SettingService::class)->setMany(['about_content' => ['ar' => 'نص', 'en' => 'Some text']]);

        $this->get('/en/about')->assertOk()->assertSee('Some text')->assertDontSee('yq-cta', false);
    }

    public function test_the_contact_page_shows_configured_details_and_active_branches(): void
    {
        app(SettingService::class)->setMany([
            'whatsapp_number' => '+201012345678',
            'phone' => '0223456789',
            'email' => 'hello@example.com',
            'facebook_url' => 'https://facebook.com/example',
            'instagram_url' => 'http://insecure.example.com/x',
        ]);
        Branch::factory()->create([
            'phone' => '01012345678',
            'whatsapp' => '01112345678',
            'map_url' => 'https://maps.example.com/shop',
            'en' => ['name' => 'Demo Branch', 'address' => 'Demo street', 'working_hours' => "Sat to Thu\n10 to 10"],
        ]);
        Branch::factory()->inactive()->create(['en' => ['name' => 'Closed Branch', 'address' => 'Closed street']]);

        $response = $this->get('/en/contact')->assertOk();

        $response
            ->assertDontSee(__('page::contact.empty_title'))
            ->assertSee('href="https://wa.me/201012345678?text=', false)
            ->assertSee('href="tel:+20223456789"', false)
            ->assertSee('href="mailto:hello@example.com"', false)
            ->assertSee('href="https://facebook.com/example"', false)
            ->assertDontSee('insecure.example.com')
            ->assertSee('Demo Branch')
            ->assertSee('Demo street')
            ->assertSee('Sat to Thu'."\n".'10 to 10', false)
            ->assertSee('href="tel:+201012345678"', false)
            ->assertSee('<span class="yq-underline" dir="ltr">01012345678</span>', false)
            ->assertSee('href="https://wa.me/201112345678?text=', false)
            ->assertSee('href="https://maps.example.com/shop" class="yq-contact__link" target="_blank" rel="noopener"', false)
            ->assertSee(__('front.contact.opens_new_tab'))
            ->assertDontSee('Closed Branch');
    }

    public function test_branch_parts_are_hidden_when_missing_or_invalid(): void
    {
        Branch::factory()->withoutOptional()->create([
            'phone' => '12',
            'en' => ['name' => 'Bare Branch', 'address' => 'Bare street'],
        ]);

        $this->get('/en/contact')
            ->assertOk()
            ->assertSee('Bare Branch')
            ->assertSee('Bare street')
            ->assertDontSee(__('page::contact.working_hours'))
            ->assertDontSee('yq-branch__actions', false)
            ->assertDontSee('tel:', false)
            ->assertDontSee('wa.me', false)
            ->assertDontSee(__('page::contact.map'));
    }

    public function test_branch_text_is_escaped_and_a_non_https_map_link_is_never_rendered(): void
    {
        $branch = Branch::factory()->create([
            'map_url' => 'https://maps.example.com/ok?a=1&b=<x>',
            'en' => ['name' => '<script>alert(1)</script>', 'address' => 'A <i>street</i>', 'working_hours' => '<b>always</b>'],
        ]);

        $this->get('/en/contact')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<i>street</i>', false)
            ->assertDontSee('<b>always</b>', false)
            ->assertSee('href="https://maps.example.com/ok?a=1&amp;b=&lt;x&gt;"', false);

        // The model refuses an http map link, so it can never reach the page.
        $this->expectException(\InvalidArgumentException::class);
        $branch->update(['map_url' => 'http://maps.example.com/insecure']);
    }

    public function test_the_contact_page_has_no_n_plus_one_queries(): void
    {
        app(SettingService::class)->setMany(['whatsapp_number' => '01012345678', 'email' => 'hello@example.com']);
        Branch::factory()->count(2)->create();
        $this->get('/en/contact')->assertOk(); // warm the settings cache

        $few = $this->countQueries(fn () => $this->get('/en/contact')->assertOk());

        Branch::factory()->count(15)->create();
        $many = $this->countQueries(fn () => $this->get('/en/contact')->assertOk());

        $this->assertSame($few, $many);
    }

    public function test_the_privacy_policy_lists_every_section_and_the_questions_email_only_when_configured(): void
    {
        $response = $this->get('/en/privacy-policy')->assertOk();

        foreach (array_keys(__('page::privacy.sections')) as $section) {
            $response->assertSee(__("page::privacy.sections.$section.title"));
        }
        $response->assertDontSee(__('page::privacy.questions_title'))->assertDontSee('mailto:', false);

        app(SettingService::class)->setMany(['email' => 'privacy@example.com']);

        $this->get('/en/privacy-policy')
            ->assertOk()
            ->assertSee(__('page::privacy.questions_title'))
            ->assertSee('href="mailto:privacy@example.com"', false);
    }

    public function test_the_footer_links_to_the_privacy_policy(): void
    {
        $this->get('/en')->assertOk()->assertSee('href="http://localhost/en/privacy-policy"', false);
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
