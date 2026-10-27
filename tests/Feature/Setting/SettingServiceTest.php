<?php

namespace Tests\Feature\Setting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Setting\Models\Setting;
use Modules\Setting\Models\SettingTranslation;
use Modules\Setting\Services\SettingService;
use RuntimeException;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingService $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = app(SettingService::class);
    }

    public function test_it_is_registered_as_a_singleton(): void
    {
        $this->assertSame($this->settings, app(SettingService::class));
    }

    public function test_an_empty_table_is_safe(): void
    {
        $this->assertNull($this->settings->get('phone'));
        $this->assertSame('fallback', $this->settings->get('phone', 'fallback'));
        $this->assertSame('fallback', $this->settings->get('hero_title', 'fallback'));

        $all = $this->settings->all();

        $this->assertSame(array_keys(config('setting.keys')), array_keys($all));
        $this->assertSame([], array_filter($all, fn ($value) => $value !== null));
    }

    public function test_it_sets_and_gets_a_non_translatable_value(): void
    {
        $this->settings->set('phone', '+201000000000');

        $this->assertSame('+201000000000', $this->settings->get('phone'));
        $this->assertDatabaseHas('settings', ['key' => 'phone', 'value' => '+201000000000']);

        $this->settings->set('phone', '+201111111111');

        $this->assertSame('+201111111111', $this->settings->get('phone'));
        $this->assertSame(1, Setting::query()->where('key', 'phone')->count());
    }

    public function test_an_empty_string_returns_the_default(): void
    {
        $this->settings->set('email', '');

        $this->assertSame('none', $this->settings->get('email', 'none'));
    }

    public function test_it_sets_and_gets_a_translatable_value_per_locale(): void
    {
        $this->settings->set('hero_title', ['ar' => 'مجوهرات ياقوت', 'en' => 'Yaqot Jewelry']);

        app()->setLocale('ar');
        $this->assertSame('مجوهرات ياقوت', $this->settings->get('hero_title'));
        $this->assertSame('مجوهرات ياقوت', $this->settings->all()['hero_title']);

        app()->setLocale('en');
        $this->assertSame('Yaqot Jewelry', $this->settings->get('hero_title'));
        $this->assertSame('Yaqot Jewelry', $this->settings->all()['hero_title']);
    }

    public function test_a_partial_translatable_update_keeps_the_other_locale(): void
    {
        $this->settings->set('footer_text', ['ar' => 'نص', 'en' => 'Text']);
        $this->settings->set('footer_text', ['en' => 'New text']);

        app()->setLocale('ar');
        $this->assertSame('نص', $this->settings->get('footer_text'));

        app()->setLocale('en');
        $this->assertSame('New text', $this->settings->get('footer_text'));
    }

    public function test_it_falls_back_to_english_when_arabic_is_empty(): void
    {
        $this->settings->set('hero_subtitle', ['ar' => '', 'en' => 'Fine gold']);
        $this->settings->set('about_content', ['ar' => null, 'en' => 'About us']);
        $this->settings->set('meta_description', ['en' => 'Only English']);

        app()->setLocale('ar');

        $this->assertSame('Fine gold', $this->settings->get('hero_subtitle'));
        $this->assertSame('About us', $this->settings->get('about_content'));
        $this->assertSame('Only English', $this->settings->get('meta_description'));
    }

    public function test_it_returns_the_default_when_no_locale_has_a_value(): void
    {
        $this->settings->set('hero_cta_label', ['ar' => '', 'en' => '']);

        app()->setLocale('ar');

        $this->assertSame('default', $this->settings->get('hero_cta_label', 'default'));
    }

    public function test_an_unknown_key_throws_on_get(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->settings->get('stripe_secret');
    }

    public function test_an_unknown_key_throws_on_set(): void
    {
        try {
            $this->settings->set('stripe_secret', 'sk_live');
            $this->fail('Expected an InvalidArgumentException.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('settings', 0);
        }
    }

    public function test_an_unsupported_locale_throws(): void
    {
        try {
            $this->settings->set('hero_title', ['en' => 'Title', 'fr' => 'Titre']);
            $this->fail('Expected an InvalidArgumentException.');
        } catch (InvalidArgumentException) {
            $this->assertDatabaseCount('settings', 0);
            $this->assertDatabaseCount('setting_translations', 0);
        }
    }

    public function test_value_shapes_are_validated(): void
    {
        $this->assertThrows(fn () => $this->settings->set('hero_title', 'not an array'), InvalidArgumentException::class);
        $this->assertThrows(fn () => $this->settings->set('phone', ['en' => '123']), InvalidArgumentException::class);
    }

    public function test_the_cache_is_invalidated_when_saving_through_the_service(): void
    {
        $this->settings->set('phone', '111');
        $this->settings->set('hero_title', ['ar' => 'قديم', 'en' => 'Old']);

        $this->assertSame('111', $this->settings->get('phone'));
        $this->assertSame('Old', $this->settings->get('hero_title'));

        $this->settings->set('phone', '222');
        $this->settings->set('hero_title', ['en' => 'New']);

        $this->assertSame('222', $this->settings->get('phone'));
        $this->assertSame('New', $this->settings->get('hero_title'));
    }

    public function test_the_cache_is_invalidated_when_saving_through_the_models(): void
    {
        $this->settings->set('phone', '111');
        $this->settings->set('hero_title', ['ar' => 'قديم', 'en' => 'Old']);

        $this->assertSame('111', $this->settings->get('phone'));
        $this->assertSame('Old', $this->settings->get('hero_title'));

        $setting = Setting::query()->where('key', 'phone')->firstOrFail();
        $setting->value = '333';
        $setting->save();

        $this->assertSame('333', $this->settings->get('phone'));

        $translation = SettingTranslation::query()->where('locale', 'en')->firstOrFail();
        $translation->value = 'Edited';
        $translation->save();

        $this->assertSame('Edited', $this->settings->get('hero_title'));

        $translation->delete();
        app()->setLocale('ar');
        $this->assertSame('قديم', $this->settings->get('hero_title'));

        Setting::query()->where('key', 'phone')->firstOrFail()->delete();
        $this->assertNull($this->settings->get('phone'));
    }

    public function test_a_cached_read_does_not_query_the_database(): void
    {
        $this->settings->set('phone', '111');
        $this->settings->set('hero_title', ['ar' => 'عنوان', 'en' => 'Title']);

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->settings->get('phone');
        $this->assertLessThanOrEqual(2, count(DB::getQueryLog()));

        DB::flushQueryLog();

        $this->assertSame('111', $this->settings->get('phone'));
        $this->assertSame('Title', $this->settings->get('hero_title'));
        $this->settings->all();

        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_an_empty_table_is_cached_too(): void
    {
        $this->settings->get('phone');

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->settings->all();

        $this->assertCount(0, DB::getQueryLog());
    }

    public function test_set_many_writes_several_keys(): void
    {
        $this->settings->setMany([
            'phone' => '111',
            'email' => 'info@example.com',
            'hero_title' => ['ar' => 'عنوان', 'en' => 'Title'],
        ]);

        $this->assertSame('111', $this->settings->get('phone'));
        $this->assertSame('info@example.com', $this->settings->get('email'));
        $this->assertSame('Title', $this->settings->get('hero_title'));
    }

    public function test_set_many_is_atomic(): void
    {
        $this->settings->set('phone', '111');

        try {
            $this->settings->setMany([
                'phone' => '222',
                'hero_title' => ['ar' => 'عنوان', 'en' => 'Title'],
                'unknown_key' => 'x',
            ]);
            $this->fail('Expected an InvalidArgumentException.');
        } catch (InvalidArgumentException) {
        }

        try {
            $this->settings->setMany([
                'phone' => '333',
                'hero_title' => ['de' => 'Titel'],
            ]);
            $this->fail('Expected an InvalidArgumentException.');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame('111', $this->settings->get('phone'));
        $this->assertNull($this->settings->get('hero_title'));
        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseCount('setting_translations', 0);
    }

    public function test_set_many_rolls_back_when_a_write_fails(): void
    {
        Setting::saving(function (Setting $setting) {
            if ($setting->key === 'email') {
                throw new RuntimeException('Write failed.');
            }
        });

        $this->assertThrows(fn () => $this->settings->setMany([
            'phone' => '111',
            'hero_title' => ['en' => 'Title'],
            'email' => 'info@example.com',
        ]), RuntimeException::class);

        $this->assertDatabaseCount('settings', 0);
        $this->assertDatabaseCount('setting_translations', 0);
        $this->assertNull($this->settings->get('phone'));
    }

    public function test_a_read_inside_a_rolled_back_transaction_does_not_leave_stale_values_cached(): void
    {
        $this->settings->set('phone', '111');

        try {
            DB::transaction(function () {
                $this->settings->set('phone', '999');
                $this->assertSame('999', $this->settings->get('phone'));
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame('111', $this->settings->get('phone'));
    }

    public function test_factories_create_rows(): void
    {
        $translation = SettingTranslation::factory()->create();

        $this->assertInstanceOf(Setting::class, $translation->setting);
        $this->assertSame(1, $translation->setting->translations()->count());
    }
}
