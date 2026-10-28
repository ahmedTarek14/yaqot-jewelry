<?php

namespace Tests\Feature\Setting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Auth\Models\Admin;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The grouped Settings page: optional fields, normalized phone numbers, https-only
 * links, both-languages-or-none translated texts, and the hero image.
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
            'hero_title_ar' => 'عنوان تجريبي',
            'hero_title_en' => 'Demo title',
            'hero_subtitle_ar' => 'عنوان فرعي تجريبي',
            'hero_subtitle_en' => 'Demo subtitle',
            'hero_cta_label_ar' => 'تصفح',
            'hero_cta_label_en' => 'Browse',
            'hero_cta_url' => 'https://example.com/catalog',
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

        $this->settings()->setMany(['phone' => '0223456789', 'hero_title' => ['en' => 'Only English']]);

        $values = $this->settings()->formValues();
        $this->assertSame('0223456789', $values['phone']);
        $this->assertSame('Only English', $values['hero_title_en']);
        // get() would fall back to English; the form must show the Arabic field empty.
        $this->assertNull($values['hero_title_ar']);
        $this->assertSame('Only English', $this->settings()->get('hero_title'));
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
                __('setting::setting.sections.hero.title', [], $locale),
                __('setting::setting.sections.about.title', [], $locale),
                __('setting::setting.sections.footer.title', [], $locale),
                __('setting::setting.sections.seo.title', [], $locale),
                __('setting::setting.sections.contact.title', [], $locale),
                __('setting::setting.sections.social.title', [], $locale),
            ])
            ->assertSeeInOrder(['data-bs-target="#hero-translations-ar"', 'data-bs-target="#hero-translations-en"'], false)
            ->assertSee('data-bs-target="#about-translations-en"', false)
            ->assertSee('name="hero_image"', false)
            ->assertDontSee('name="remove_hero_image"', false);

        foreach (array_keys($this->payload()) as $name) {
            $response->assertSee('name="'.$name.'"', false);
        }

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/inputmode="tel" dir="ltr"[^>]*type="tel" id="field-whatsapp_number"/', $content);
        $this->assertMatchesRegularExpression('/inputmode="url" dir="ltr"[^>]*type="url" id="field-facebook_url"/', $content);
    }

    #[DataProvider('locales')]
    public function test_the_page_shows_the_stored_values_of_both_locales(string $locale): void
    {
        $this->signIn($locale);
        Storage::disk('public')->put('settings/hero.webp', 'x');
        $this->settings()->setMany([
            'phone' => '0223456789',
            'whatsapp_number' => '201000000001',
            'facebook_url' => 'https://www.facebook.com/example',
            'hero_image' => 'settings/hero.webp',
            'hero_title' => ['ar' => 'عنوان تجريبي', 'en' => 'Demo title'],
        ]);

        $this->get("/$locale/admin/settings")
            ->assertOk()
            ->assertSee('value="0223456789"', false)
            ->assertSee('value="201000000001"', false)
            ->assertSee('value="https://www.facebook.com/example"', false)
            ->assertSee('value="عنوان تجريبي"', false)
            ->assertSee('value="Demo title"', false)
            ->assertSee(Storage::disk('public')->url('settings/hero.webp'))
            ->assertSee('name="remove_hero_image"', false);
    }

    public function test_admin_text_is_never_rendered_as_html(): void
    {
        $this->signIn();
        $this->settings()->setMany([
            'about_content' => ['ar' => '<script>alert(1)</script>', 'en' => '<b>bold</b><script>alert(2)</script>'],
            'hero_title' => ['ar' => '"><img src=x>', 'en' => 'Demo'],
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
        $this->assertSame('https://example.com/catalog', $settings->get('hero_cta_url'));
        $this->assertNull($settings->get('hero_image'));

        $values = $settings->formValues();
        $this->assertSame('عنوان تجريبي', $values['hero_title_ar']);
        $this->assertSame('Demo title', $values['hero_title_en']);
        $this->assertSame("First paragraph.\n\nSecond paragraph.", $values['about_content_en']);
        $this->assertSame('وصف تجريبي', $values['meta_description_ar']);

        app()->setLocale('ar');
        $this->assertSame('تصفح', $settings->get('hero_cta_label'));
        app()->setLocale('en');
        $this->assertSame('Browse', $settings->get('hero_cta_label'));

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
            'hero_cta_url' => 'http://example.com',
            'meta_description_en' => str_repeat('a', 301),
            'about_content_ar' => str_repeat('a', 10001),
            'footer_text_en' => ['not', 'a', 'string'],
        ]));

        $response->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors([
                'whatsapp_number', 'phone', 'email', 'facebook_url', 'instagram_url', 'tiktok_url',
                'hero_cta_url', 'meta_description_en', 'about_content_ar', 'footer_text_en',
            ])
            ->assertJsonMissingValidationErrors(['hero_title_ar', 'hero_title_en', 'hero_image']);

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
            'title, arabic only' => [['hero_title_ar' => 'عنوان'], ['hero_title_en']],
            'about, english only' => [['about_content_en' => 'About'], ['about_content_ar']],
            'footer, english only' => [['footer_text_en' => 'Footer'], ['footer_text_ar']],
            'meta, arabic only' => [['meta_description_ar' => 'وصف'], ['meta_description_en']],
            'subtitle, english only' => [['hero_subtitle_en' => 'Subtitle'], ['hero_subtitle_ar']],
            'cta label without link' => [['hero_cta_label_ar' => 'تصفح', 'hero_cta_label_en' => 'Browse'], ['hero_cta_url']],
            'cta link without label' => [['hero_cta_url' => 'https://example.com'], ['hero_cta_label_ar', 'hero_cta_label_en']],
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

        $response = $this->putJson('/ar/admin/settings', [...$this->emptyPayload(), 'hero_title_ar' => 'عنوان']);

        $label = fn (string $lang) => __('setting::setting.fields.hero_title', [], 'ar').' ('.__("admin.form.language_names.$lang", [], 'ar').')';
        $this->assertSame(
            __('validation.required_with', ['attribute' => $label('en'), 'values' => $label('ar')], 'ar'),
            $response->json('errors.hero_title_en.0'),
        );
    }

    /* -------------------------------------------------------- Hero image */

    public function test_a_hero_image_upload_is_stored_as_webp(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['hero_image' => UploadedFile::fake()->image('hero.jpg', 2400, 1200)]))
            ->assertOk();

        $path = $this->settings()->get('hero_image');
        $this->assertMatchesRegularExpression('#^settings/[0-9a-f-]{36}\.webp$#', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame([1600, 800], array_slice(getimagesizefromstring(Storage::disk('public')->get($path)), 0, 2));
    }

    public function test_replacing_the_hero_image_deletes_the_old_file(): void
    {
        $this->signIn();
        Storage::disk('public')->put('settings/old.webp', 'x');
        $this->settings()->set('hero_image', 'settings/old.webp');

        $this->putJson('/en/admin/settings', $this->payload(['hero_image' => UploadedFile::fake()->image('new.png', 900, 900)]))
            ->assertOk();

        $new = $this->settings()->get('hero_image');
        $this->assertNotSame('settings/old.webp', $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing('settings/old.webp');
    }

    public function test_removing_the_hero_image_clears_it_and_deletes_the_file(): void
    {
        $this->signIn();
        Storage::disk('public')->put('settings/old.webp', 'x');
        $this->settings()->set('hero_image', 'settings/old.webp');

        $this->putJson('/en/admin/settings', $this->payload(['remove_hero_image' => '1']))->assertOk();

        $this->assertNull($this->settings()->get('hero_image'));
        Storage::disk('public')->assertMissing('settings/old.webp');
    }

    public function test_saving_without_a_new_image_keeps_the_current_one(): void
    {
        $this->signIn();
        Storage::disk('public')->put('settings/keep.webp', 'x');
        $this->settings()->set('hero_image', 'settings/keep.webp');

        $this->putJson('/en/admin/settings', $this->payload())->assertOk();

        $this->assertSame('settings/keep.webp', $this->settings()->get('hero_image'));
        Storage::disk('public')->assertExists('settings/keep.webp');
    }

    public function test_a_failed_validation_keeps_the_current_image(): void
    {
        $this->signIn();
        Storage::disk('public')->put('settings/keep.webp', 'x');
        $this->settings()->set('hero_image', 'settings/keep.webp');

        $this->putJson('/en/admin/settings', $this->payload(['remove_hero_image' => '1', 'email' => 'nope']))
            ->assertUnprocessable();

        $this->assertSame('settings/keep.webp', $this->settings()->get('hero_image'));
        Storage::disk('public')->assertExists('settings/keep.webp');
    }

    /** @return array<string, array{\Closure(): UploadedFile}> */
    public static function invalidImages(): array
    {
        return [
            'gif' => [fn () => UploadedFile::fake()->image('anim.gif', 900, 900)],
            'pdf' => [fn () => UploadedFile::fake()->create('brochure.pdf', 100, 'application/pdf')],
            'text renamed to jpg' => [fn () => UploadedFile::fake()->createWithContent('fake.jpg', 'this is not an image')],
            'over 5 MB' => [fn () => UploadedFile::fake()->image('huge.jpg', 900, 900)->size(5121)],
            'under 800 px' => [fn () => UploadedFile::fake()->image('small.png', 799, 900)],
            'too wide' => [fn () => UploadedFile::fake()->image('panorama.png', 6001, 900)],
        ];
    }

    #[DataProvider('invalidImages')]
    public function test_invalid_hero_images_are_rejected_and_nothing_is_stored(\Closure $file): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['hero_image' => $file()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hero_image']);

        $this->assertSame(0, Setting::count());
        $this->assertSame([], Storage::disk('public')->allFiles('settings'));
    }

    public function test_an_800_px_hero_image_is_accepted(): void
    {
        $this->signIn();

        $this->putJson('/en/admin/settings', $this->payload(['hero_image' => UploadedFile::fake()->image('min.png', 800, 800)]))
            ->assertOk();

        $this->assertCount(1, Storage::disk('public')->allFiles('settings'));
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
