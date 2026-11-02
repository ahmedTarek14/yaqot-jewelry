<?php

namespace Tests\Feature\Setting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Models\Setting;
use Modules\Setting\Services\SettingService;
use Tests\TestCase;

/**
 * Phase 5b: what the owner saved for the single hero becomes slide 1's text, and nothing
 * (the old hero_image row included) is lost. The migration is rolled back, the legacy rows
 * are written, then it runs again, all on the in-memory SQLite database.
 */
class HeroSettingsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '2026_11_02_200001_move_hero_settings_into_slide_one.php';

    private function path(): array
    {
        return [base_path('Modules/Setting/database/migrations/'.self::MIGRATION)];
    }

    private function migrate(): void
    {
        Artisan::call('migrate', ['--path' => $this->path(), '--realpath' => true]);
    }

    private function rollBack(): void
    {
        Artisan::call('migrate:rollback', ['--path' => $this->path(), '--realpath' => true]);
    }

    /**
     * A row exactly as the single hero stored it (translatable keys have per-locale values).
     *
     * @param  array<string, string>|string  $value
     */
    private function legacy(string $key, array|string $value): void
    {
        $id = DB::table('settings')->insertGetId(['key' => $key, 'value' => is_array($value) ? null : $value, 'created_at' => now(), 'updated_at' => now()]);

        foreach (is_array($value) ? $value : [] as $locale => $text) {
            DB::table('setting_translations')->insert(['setting_id' => $id, 'locale' => $locale, 'value' => $text]);
        }
    }

    public function test_the_saved_hero_text_and_button_move_into_slide_one(): void
    {
        $this->rollBack();
        $this->legacy('hero_title', ['ar' => 'عنوان محفوظ', 'en' => 'Saved title']);
        $this->legacy('hero_subtitle', ['ar' => 'فرعي محفوظ', 'en' => 'Saved subtitle']);
        $this->legacy('hero_cta_label', ['ar' => 'تصفح', 'en' => 'Browse']);
        $this->legacy('hero_cta_url', 'https://example.com/landing');
        $this->legacy('hero_image', 'settings/old-hero.webp');
        $this->legacy('phone', '0223456789');

        $this->migrate();

        $settings = app(SettingService::class);
        $values = $settings->formValues();

        $this->assertSame('عنوان محفوظ', $values['hero_1_title_ar']);
        $this->assertSame('Saved title', $values['hero_1_title_en']);
        $this->assertSame('فرعي محفوظ', $values['hero_1_subtitle_ar']);
        $this->assertSame('Saved subtitle', $values['hero_1_subtitle_en']);
        $this->assertSame('تصفح', $values['hero_1_cta_label_ar']);
        $this->assertSame('Browse', $values['hero_1_cta_label_en']);
        $this->assertSame('https://example.com/landing', $values['hero_1_cta_url']);
        $this->assertSame('0223456789', $values['phone']);

        // Slides 2 and 3 start empty, and every slide is still on.
        $this->assertNull($values['hero_2_title_en']);
        $this->assertNull($values['hero_2_enabled']);
        $this->assertNull($values['hero_1_enabled']);

        foreach (['hero_title', 'hero_subtitle', 'hero_cta_label', 'hero_cta_url'] as $old) {
            $this->assertFalse(Setting::query()->where('key', $old)->exists(), "$old should have been renamed");
        }

        // The old image setting is untouched: unregistered, unread, still in the table.
        $this->assertSame('settings/old-hero.webp', Setting::query()->where('key', 'hero_image')->value('value'));
    }

    public function test_the_homepage_shows_the_moved_text_on_the_first_slide(): void
    {
        $this->refreshApplicationWithLocale('en');
        $this->rollBack();
        $this->legacy('hero_title', ['ar' => 'عنوان محفوظ', 'en' => 'Saved title']);
        $this->legacy('hero_cta_url', 'https://example.com/landing');
        $this->migrate();

        $this->get('/en')
            ->assertOk()
            ->assertSee('<h1 class="yq-hero__title" data-enter>Saved title</h1>', false)
            ->assertSee('href="https://example.com/landing" class="btn btn-gold yq-hero__cta">Discover Our Collection</a>', false);
    }

    public function test_an_existing_slide_one_row_is_never_overwritten(): void
    {
        $this->rollBack();
        $this->legacy('hero_title', ['en' => 'Old title']);
        $this->legacy('hero_1_title', ['en' => 'Already slide one']);

        $this->migrate();

        $this->assertSame('Already slide one', app(SettingService::class)->formValues()['hero_1_title_en']);
        $this->assertTrue(Setting::query()->where('key', 'hero_title')->exists());
    }

    public function test_rolling_back_restores_the_old_names(): void
    {
        $this->rollBack();
        $this->legacy('hero_title', ['en' => 'Saved title']);
        $this->migrate();

        $this->rollBack();

        $this->assertTrue(Setting::query()->where('key', 'hero_title')->exists());
        $this->assertFalse(Setting::query()->where('key', 'hero_1_title')->exists());
    }

    public function test_an_empty_table_migrates_cleanly(): void
    {
        $this->rollBack();
        $this->migrate();

        $this->assertSame(0, Setting::count());
    }
}
