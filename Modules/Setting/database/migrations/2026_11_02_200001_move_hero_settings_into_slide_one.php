<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Models\Setting;

/**
 * Phase 5b: the homepage hero became a three-slide slider. What the owner saved for the single
 * hero (title, subtitle, button label and link) becomes slide 1's text, by renaming the rows (the
 * translations follow, they point at the row's id). The key names are written out here so the
 * migration outlives config. The old hero_image row is left alone: it is no longer registered or
 * read, and its file stays on disk.
 */
return new class extends Migration
{
    private const RENAMES = [
        'hero_title' => 'hero_1_title',
        'hero_subtitle' => 'hero_1_subtitle',
        'hero_cta_label' => 'hero_1_cta_label',
        'hero_cta_url' => 'hero_1_cta_url',
    ];

    public function up(): void
    {
        $this->rename(self::RENAMES);
    }

    public function down(): void
    {
        $this->rename(array_flip(self::RENAMES));
    }

    /**
     * @param  array<string, string>  $map  old key => new key
     */
    private function rename(array $map): void
    {
        DB::transaction(function () use ($map) {
            foreach ($map as $from => $to) {
                // Never overwrite a row that already exists under the new name.
                if (DB::table('settings')->where('key', $to)->exists()) {
                    continue;
                }

                DB::table('settings')->where('key', $from)->update(['key' => $to]);
            }
        });

        Cache::forget(Setting::CACHE_KEY);
    }
};
