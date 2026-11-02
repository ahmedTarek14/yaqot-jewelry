<?php

namespace Tests\Feature\Setting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Collection\Models\Collection;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The grouped Settings page: optional fields, normalized phone numbers, https-only
 * links, both-languages-or-none translated texts, and the three hero slides.
 */
class SettingsAdminTest extends TestCase
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

    private function settings(): SettingService
    {
        return app(SettingService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'whatsapp_number' => '+20 100 000 0001',
            'phone' => '02-2345-6789',
            'email' => 'hello@example.com',
            'facebook_url' => 'https://www.facebook.com/example',
            'instagram_url' => 'https://www.instagram.com/example',
            'tiktok_url' => 'https://www.tiktok.com/@example',
            'hero_1_enabled' => '1',
            'hero_1_title_ar' => 'عنوان تجريبي',
            'hero_1_title_en' => 'Demo title',
            'hero_1_subtitle_ar' => 'عنوان فرعي تجريبي',
            'hero_1_subtitle_en' => 'Demo subtitle',
            'hero_1_cta_label_ar' => 'تصفح',
            'hero_1_cta_label_en' => 'Browse',
            'hero_1_cta_url' => 'https://example.com/catalog',
            'hero_2_enabled' => '0',
            'hero_2_title_ar' => 'عنوان الثانية',
            'hero_2_title_en' => 'Second title',
            'hero_2_subtitle_ar' => null,
            'hero_2_subtitle_en' => null,
            'hero_2_cta_label_ar' => null,
            'hero_2_cta_label_en' => null,
            'hero_2_cta_url' => '/catalog',
            'hero_3_enabled' => '1',
            'hero_3_title_ar' => null,
            'hero_3_title_en' => null,
            'hero_3_subtitle_ar' => null,
            'hero_3_subtitle_en' => null,
            'hero_3_cta_label_ar' => null,
            'hero_3_cta_label_en' => null,
            'hero_3_cta_url' => null,
            'about_content_ar' => "فقرة أولى.\n\nفقرة ثانية.",
            'about_content_en' => "First paragraph.\n\nSecond paragraph.",
            'footer_text_ar' => 'نص تذييل تجريبي',
            'footer_text_en' => 'Demo footer text',
            'meta_description_ar' => 'وصف تجريبي',
            'meta_description_en' => 'Demo description',
        ], $overrides);
    }

    /** @return array<string, null> */
    private function emptyPayload(): array
    {
        return array_map(fn () => null, $this->payload());
    }

    /* ------------------------------------------------------ formValues() */

    public function test_form_values_cover_every_key_and_locale_without_fallbacks(): void
    {
        $this->refreshApplicationWithLocale('ar');
        $expected = [];
        foreach (config('setting.keys') as $key => $definition) {
            if ($definition['translatable']) {
                $expected["{$key}_ar"] = null;
                $expected["{$key}_en"] = null;
            } else {
                $expected[$key] = null;
            }
        }

        $this->assertSame($expected, $this->settings()->formValues());

        $this->settings()->setMany(['phone' => '0223456789', 'hero_1_title' => ['en' => 'Only English']]);

        $values = $this->settings()->formValues();
        $this->assertSame('0223456789', $values['phone']);
        $this->assertSame('Only English', $values['hero_1_title_en']);
        // get() would fall back to English; the form must show the Arabic field empty.
        $this->assertNull($values['hero_1_title_ar']);
        $this->assertSame('Only English', $this->settings()->get('hero_1_title'));
    }

    public function test_form_values_see_a_save_immediately(): void
    {
        $this->refreshApplicationWithLocale('en');
        $this->settings()->set('email', 'old@example.com');
        $this->assertSame('old@example.com', $this->settings()->formValues()['email']);

        $this->settings()->set('email', 'new@example.com');
        $this->assertSame('new@example.com', $this->settings()->formValues()['email']);
    }

    /* -------------------------------------------------------------- Page */

    #[DataProvider('locales')]
    public function test_the_page_renders_with_an_empty_settings_table(string $locale): void
    {
        $this->signIn($locale);
        $this->assertSame(0, Setting::count());

        $response = $this->get("/$locale/admin/settings");

        $response->assertOk()
            ->assertSee('<html lang="'.$locale.'"', false)
            ->assertSee(__('setting::setting.title', [], $locale))
            ->assertSee('class="ajax-form"', false)
            ->assertSee('action="'.route('admin.settings.update').'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSeeInOrder([
                __('setting::setting.sections.hero.slide', ['number' => 1, 'subject' => __('setting::setting.sections.hero.subjects.1', [], $locale)], $locale),
                __('setting::setting.sections.hero.slide', ['number' => 2, 'subject' => __('setting::setting.sections.hero.subjects.2', [], $locale)], $locale),
                __('setting::setting.sections.hero.slide', ['number' => 3, 'subject' => __('setting::setting.sections.hero.subjects.3', [], $locale)], $locale),
                __('setting::setting.sections.about.title', [], $locale),
                __('setting::setting.sections.footer.title', [], $locale),
                __('setting::setting.sections.seo.title', [], $locale),
                __('setting::setting.sections.contact.title', [], $locale),
                __('setting::setting.sections.social.title', [], $locale),
            ])
            ->assertSeeInOrder(['data-bs-target="#hero-1-translations-ar"', 'data-bs-target="#hero-1-translations-en"'], false)
            ->assertSeeInOrder(['id="hero-1-translations-ar"', 'name="hero_2_enabled"', 'data-bs-target="#hero-2-translations-ar"', 'data-bs-target="#hero-3-translations-ar"'], false)
            ->assertSee('data-bs-target="#about-translations-en"', false)
            // The hero images are fixed files now: no upload.
            ->assertDontSee('name="hero_image"', false)
            ->assertDontSee('name="remove_hero_image"', false);

        foreach (array_keys($this->payload()) as $name) {
            $response->assertSee('name="'.$name.'"', false);
        }

        $content = $response->getContent();

        // A slide is on until the owner switches it off.
        foreach ([1, 2, 3] as $slide) {
            $this->assertTrue($this->slideSwitchIsOn($content, $slide), "Slide $slide should be on by default.");
        }

        $this->assertMatchesRegularExpression('/inputmode="tel" dir="ltr"[^>]*type="tel" id="field-whatsapp_number"/', $content);
        $this->assertMatchesRegularExpression('/inputmode="url" dir="ltr"[^>]*type="url" id="field-facebook_url"/', $content);
    }

    #[DataProvider('locales')]
    public function test_the_page_shows_the_stored_values_of_both_locales(string $locale): void
    {
        $this->signIn($locale);
        $this->settings()->setMany([
            'phone' => '0223456789',
            'whatsapp_number' => '201000000001',
            'facebook_url' => 'https://www.facebook.com/example',
            'hero_1_title' => ['ar' => 'عنوان تجريبي', 'en' => 'Demo title'],
            'hero_2_enabled' => '0',
            'hero_2_cta_url' => '/catalog',
        ]);

        $response = $this->get("/$locale/admin/settings")
            ->assertOk()
            ->assertSee('value="0223456789"', false)
            ->assertSee('value="201000000001"', false)
            ->assertSee('value="https://www.facebook.com/example"', false)
            ->assertSee('value="عنوان تجريبي"', false)
            ->assertSee('value="Demo title"', false)
            ->assertSee('value="/catalog"', false);

        $this->assertTrue($this->slideSwitchIsOn($response->getContent(), 1));
        $this->assertFalse($this->slideSwitchIsOn($response->getContent(), 2));
        $this->assertTrue($this->slideSwitchIsOn($response->getContent(), 3));
    }

    /**
     * Whether a slide's "show this slide" switch is rendered checked.
     */
    private function slideSwitchIsOn(string $html, int $slide): bool
    {
        $found = preg_match('/<input[^>]*id="field-hero_'.$slide.'_enabled"[^>]*>/s', $html, $input);
        $this->assertSame(1, $found, "Slide $slide has no switch.");

        return preg_match('/\schecked[\s>]/', $input[0]) === 1;
    }

    public function test_admin_text_is_never_rendered_as_html(): void
    {
        $this->signIn();
        $this->settings()->setMany([
            'about_content' => ['ar' => '<script>alert(1)</script>', 'en' => '<b>bold</b><script>alert(2)</script>'],
            'hero_1_title' => ['ar' => '"><img src=x>', 'en' => 'Demo'],
        ]);

        $this->get('/en/admin/settings')
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('&lt;b&gt;bold&lt;/b&gt;', false)
            ->assertDontSee('<script>alert(', false)
            ->assertDontSee('<img src=x>', false);
    }

    /* -------------------------------------------------------------- Save */

    #[DataProvider('locales')]
    public function test_saving_writes_every_value_normalized_and_the_next_read_sees_it(string $locale): void
    {
        $this->signIn($locale);
        // Prime the cache, so a stale read would show up.
        $this->assertNull($this->settings()->get('phone'));

        $this->putJson("/$locale/admin/settings", $this->payload())
            ->assertOk()
            ->assertExactJson([
                'message' => __('admin.messages.saved', [], $locale),
                'redirect' => "http://localhost/$locale/admin/settings",
            ]);

        $settings = $this->settings();
        $this->assertSame('201000000001', $settings->get('whatsapp_number'));
        $this->assertSame('0223456789', $settings->get('phone'));
        $this->assertSame('hello@example.com', $settings->get('email'));
        $this->assertSame('https://www.tiktok.com/@example', $settings->get('tiktok_url'));
        $this->assertSame('https://example.com/catalog', $settings->get('hero_1_cta_url'));
        $this->assertSame('/catalog', $settings->get('hero_2_cta_url'));
        $this->assertNull($settings->get('hero_3_cta_url'));
        $this->assertSame('1', $settings->get('hero_1_enabled'));
        $this->assertSame('0', $settings->get('hero_2_enabled'));

        $values = $settings->formValues();
        $this->assertSame('عنوان تجريبي', $values['hero_1_title_ar']);
        $this->assertSame('Demo title', $values['hero_1_title_en']);
        $this->assertSame('Second title', $values['hero_2_title_en']);
        $this->assertSame("First paragraph.\n\nSecond paragraph.", $values['about_content_en']);
        $this->assertSame('وصف تجريبي', $values['meta_description_ar']);

        app()->setLocale('ar');
        $this->assertSame('تصفح', $settings->get('hero_1_cta_label'));
        app()->setLocale('en');
        $this->assertSame('Browse', $settings->get('hero_1_cta_label'));

        $this->get("/$locale/admin/settings")->assertSee('value="201000000001"', false);
    }

    public function test_an_empty_form_is_valid_and_clears_the_values(): void
    {
        $this->signIn();
        $this->settings()->setMany(['phone' => '0223456789', 'footer_text' => ['ar' => 'نص', 'en' => 'Text']]);

        $this->putJson('/en/admin/settings', $this->emptyPayload())->assertOk();

        $this->assertNull($this->settings()->get('phone'));
        $this->assertNull($this->settings()->get('footer_text'));
        $this->assertSame([], array_filter($this->settings()->formValues(), fn ($value) => $value !== null));
    }

    public function test_the_prominent_collection_offers_active_collections_only(): void
    {
        $this->signIn();
        $active = Collection::factory()->create(['en' => ['name' => 'Demo Shabka']]);
        Collection::factory()->create(['is_active' => false, 'en' => ['name' => 'Hidden Collection']]);
        $this->settings()->set('prominent_collection', (string) $active->id);

        $this->get('/en/admin/settings')
            ->assertOk()
            ->assertSee('<option value="">'.__('setting::setting.fields.prominent_collection_none').'</option>', false)
            ->assertSee('<option value="'.$active->id.'" selected>Demo Shabka</option>', false)
            ->assertDontSee('Hidden Collection');
    }

    public function test_the_prominent_collection_is_saved_and_can_be_cleared(): void
    {
        $this->signIn();
        $collection = Collection::factory()->create();

        $this->putJson('/en/admin/settings', $this->payload(['prominent_collection' => (string) $collection->id]))->assertOk();
        $this->assertSame((string) $collection->id, $this->settings()->get('prominent_collection'));

        $this->putJson('/en/admin/settings', $this->payload(['prominent_collection' => '']))->assertOk();
        $this->assertNull($this->settings()->get('prominent_collection'));
    }

    public function test_an_inactive_or_unknown_prominent_collection_is_rejected(): void
    {
        $this->signIn();
        $inactive = Collection::factory()->create(['is_active' => false]);

        foreach ([(string) $inactive->id, '999999', 'shabka'] as $value) {
            $this->putJson('/en/admin/settings', $this->payload(['prominent_collection' => $value]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('prominent_collection');
        }

        $this->assertNull($this->settings()->get('prominent_collection'));
    }

    public function test_a_landline_or_mobile_phone_is_accepted(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['phone' => '+20 10 1234 5678']))->assertOk();
        $this->assertSame('01012345678', $this->settings()->get('phone'));

        $this->putJson('/en/admin/settings', $this->payload(['phone' => '03 456 7890']))->assertOk();
        $this->assertSame('034567890', $this->settings()->get('phone'));

        $this->putJson('/en/admin/settings', $this->payload(['phone' => '19 123']))->assertOk();
        $this->assertSame('19123', $this->settings()->get('phone'));
    }

    public function test_a_hotline_is_never_accepted_as_the_whatsapp_number(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['whatsapp_number' => '19123']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['whatsapp_number'])
            ->assertJsonMissingValidationErrors(['phone']);
    }

    /* -------------------------------------------------------- Validation */

    #[DataProvider('locales')]
    public function test_validation_returns_422_json_with_every_error(string $locale): void
    {
        $this->signIn($locale);

        $response = $this->putJson("/$locale/admin/settings", $this->payload([
            'whatsapp_number' => '0223456789',
            'phone' => '1234', // 12345 would now be a valid hotline
            'email' => 'not-an-email',
            'facebook_url' => 'http://www.facebook.com/example',
            'instagram_url' => 'javascript:alert(1)',
            'tiktok_url' => 'https://'.str_repeat('a', 2040).'.com',
            'hero_1_cta_url' => 'http://example.com',
            'meta_description_en' => str_repeat('a', 301),
            'about_content_ar' => str_repeat('a', 10001),
            'footer_text_en' => ['not', 'a', 'string'],
        ]));

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors([
                'whatsapp_number', 'phone', 'email', 'facebook_url', 'instagram_url', 'tiktok_url',
                'hero_1_cta_url', 'meta_description_en', 'about_content_ar', 'footer_text_en',
            ])
            ->assertJsonMissingValidationErrors(['hero_1_title_ar', 'hero_1_title_en', 'hero_2_cta_url']);

        $this->assertSame(
            __('admin.validation.egyptian_mobile', ['attribute' => __('setting::setting.fields.whatsapp_number', [], $locale)], $locale),
            $response->json('errors.whatsapp_number.0'),
        );

        $this->assertSame(0, Setting::count());
    }

    /** @return array<string, array{array<string, string>, array<int, string>}> */
    public static function oneLocaleOnly(): array
    {
        return [
            'title, arabic only' => [['hero_1_title_ar' => 'عنوان'], ['hero_1_title_en']],
            'about, english only' => [['about_content_en' => 'About'], ['about_content_ar']],
            'footer, english only' => [['footer_text_en' => 'Footer'], ['footer_text_ar']],
            'meta, arabic only' => [['meta_description_ar' => 'وصف'], ['meta_description_en']],
            'subtitle, english only' => [['hero_1_subtitle_en' => 'Subtitle'], ['hero_1_subtitle_ar']],
            'slide 2 title, english only' => [['hero_2_title_en' => 'Title'], ['hero_2_title_ar']],
            'slide 3 button label, arabic only' => [['hero_3_cta_label_ar' => 'تصفح'], ['hero_3_cta_label_en']],
            // A disabled slide is still validated: its text is kept for later.
            'disabled slide, english only' => [['hero_3_enabled' => '0', 'hero_3_subtitle_en' => 'Subtitle'], ['hero_3_subtitle_ar']],
        ];
    }

    /**
     * @param  array<string, string>  $filled
     * @param  array<int, string>  $missing
     */
    #[DataProvider('oneLocaleOnly')]
    public function test_a_text_in_one_language_only_is_rejected(array $filled, array $missing): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', [...$this->emptyPayload(), ...$filled])
            ->assertUnprocessable()
            ->assertJsonValidationErrors($missing);

        $this->assertSame(0, Setting::count());
    }

    public function test_the_language_error_names_the_other_tab(): void
    {
        $this->signIn('ar');

        $response = $this->putJson('/ar/admin/settings', [...$this->emptyPayload(), 'hero_2_title_ar' => 'عنوان']);

        $label = fn (string $lang) => __('setting::setting.hero_slide_field', ['slide' => 2, 'field' => __('setting::setting.fields.hero_title', [], 'ar')], 'ar')
            .' ('.__("admin.form.language_names.$lang", [], 'ar').')';
        $this->assertSame(
            __('validation.required_with', ['attribute' => $label('en'), 'values' => $label('ar')], 'ar'),
            $response->json('errors.hero_2_title_en.0'),
        );
    }

    /* ----------------------------------------------- Hero image (removed) */

    public function test_the_old_hero_image_setting_is_gone_and_its_file_is_left_alone(): void
    {
        $this->signIn();
        Storage::disk('public')->put('settings/old.webp', 'x');
        Setting::query()->create(['key' => 'hero_image', 'value' => 'settings/old.webp']);

        $this->assertArrayNotHasKey('hero_image', config('setting.keys'));
        $this->assertArrayNotHasKey('hero_image', $this->settings()->formValues());

        // An upload is ignored (not validated, not stored); saving never touches the old row or file.
        $this->putJson('/en/admin/settings', $this->payload(['hero_image' => UploadedFile::fake()->image('new.png', 900, 900)]))
            ->assertOk();

        $this->assertSame('settings/old.webp', Setting::query()->where('key', 'hero_image')->value('value'));
        Storage::disk('public')->assertExists('settings/old.webp');
        $this->assertSame(['settings/old.webp'], Storage::disk('public')->allFiles('settings'));
    }

    /* -------------------------------------------------------- Hero slides */

    public function test_a_slide_is_switched_off_and_on_again(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['hero_2_enabled' => '0', 'hero_3_enabled' => '0']))->assertOk();
        $this->assertSame('0', $this->settings()->get('hero_2_enabled'));
        $this->assertSame('0', $this->settings()->get('hero_3_enabled'));

        $this->putJson('/en/admin/settings', $this->payload(['hero_2_enabled' => '1']))->assertOk();
        $this->assertSame('1', $this->settings()->get('hero_2_enabled'));
    }

    public function test_a_disabled_slide_keeps_its_text(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload())->assertOk();

        $this->assertSame('Second title', $this->settings()->formValues()['hero_2_title_en']);
        $this->assertSame('0', $this->settings()->get('hero_2_enabled'));
    }

    /** @return array<string, array{string}> */
    public static function acceptedLinks(): array
    {
        return [
            'https' => ['https://example.com/landing?a=1#top'],
            'site path' => ['/catalog'],
            'site path with query' => ['/catalog?karat=21'],
            'home' => ['/'],
            'locale already in the path' => ['/en/bullion'],
        ];
    }

    #[DataProvider('acceptedLinks')]
    public function test_a_slide_button_link_can_be_an_https_url_or_a_site_path(string $link): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['hero_3_cta_url' => $link]))->assertOk();

        $this->assertSame($link, $this->settings()->get('hero_3_cta_url'));
    }

    /** @return array<string, array{string}> */
    public static function rejectedLinks(): array
    {
        return [
            'http' => ['http://example.com'],
            'javascript' => ['javascript:alert(1)'],
            'protocol-relative' => ['//evil.example.com'],
            'bare path' => ['catalog'],
            'backslash' => ['/\\evil.example.com'],
            'with a space' => ['/cata log'],
            'data url' => ['data:text/html,x'],
            'mailto' => ['mailto:a@example.com'],
            'too long' => ['/'.str_repeat('a', 2048)],
        ];
    }

    #[DataProvider('rejectedLinks')]
    public function test_an_unsafe_slide_button_link_is_rejected(string $link): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['hero_2_cta_url' => $link]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('hero_2_cta_url');

        $this->assertSame(0, Setting::count());
    }

    public function test_the_link_error_names_the_slide(): void
    {
        $this->signIn('en');

        $response = $this->putJson('/en/admin/settings', $this->payload(['hero_2_cta_url' => 'http://example.com']));

        $this->assertSame(__('setting::setting.validation.site_link'), $response->json('errors.hero_2_cta_url.0'));
    }

    public function test_a_non_boolean_switch_is_rejected(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['hero_1_enabled' => 'maybe']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('hero_1_enabled');
    }

    public function test_the_text_limits_apply_to_every_slide(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload([
            'hero_3_title_ar' => str_repeat('a', 256),
            'hero_3_title_en' => str_repeat('a', 256),
            'hero_3_subtitle_en' => str_repeat('a', 501),
            'hero_3_subtitle_ar' => 'x',
            'hero_3_cta_label_ar' => str_repeat('a', 101),
            'hero_3_cta_label_en' => 'x',
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['hero_3_title_ar', 'hero_3_title_en', 'hero_3_subtitle_en', 'hero_3_cta_label_ar']);
    }

    /* ------------------------------------------------------------ Access */

    /** @return array<string, array{string}> */
    public static function methods(): array
    {
        return ['edit' => ['GET'], 'update' => ['PUT']];
    }

    #[DataProvider('methods')]
    public function test_guests_are_redirected_to_the_localized_login(string $method): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->refreshApplicationWithLocale($locale);

            $this->call($method, "/$locale/admin/settings", $this->payload())
                ->assertRedirect("http://localhost/$locale/admin/login");
        }

        $this->assertSame(0, Setting::count());
    }

    #[DataProvider('methods')]
    public function test_users_of_the_web_guard_cannot_reach_the_settings(string $method): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->actingAs(User::factory()->create())
            ->call($method, '/en/admin/settings', $this->payload())
            ->assertRedirect('http://localhost/en/admin/login');

        $this->assertSame(0, Setting::count());
    }

    public function test_guests_asking_for_json_get_401(): void
    {
        $this->refreshApplicationWithLocale('en');

        $this->putJson('/en/admin/settings', $this->payload())->assertUnauthorized();
        $this->assertSame(0, Setting::count());
    }
}
