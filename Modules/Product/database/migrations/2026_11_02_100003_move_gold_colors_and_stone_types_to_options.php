<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
| Phase 4c, step 2 of 3: the data move. The owner's current lists (the old config/catalog.php
| allowlists and their lang labels) are written out here, so this migration keeps working after
| config and lang no longer have them. Each becomes an option in its group, and every product
| (trashed ones included, hence the query builder) is linked to the option with the same slug.
| A stored value outside the lists still gets an option, named after the raw value, so no product
| loses its colour or stones.
|
| down() copies each linked option's slug back into the old string columns (recreated by the next
| migration's down()). Options the owner added later have no old value: slugs longer than the old
| 20-character column become null.
*/
return new class extends Migration
{
    /** Old column => [group, current allowlist with its [en, ar] labels]. */
    private const LEGACY = [
        'gold_color' => [
            'column' => 'gold_color_id',
            'options' => [
                'yellow' => ['Yellow gold', 'ذهب أصفر'],
                'white' => ['White gold', 'ذهب أبيض'],
                'rose' => ['Rose gold', 'ذهب روز'],
                'multi' => ['Multi-tone', 'متعدد الألوان'],
            ],
        ],
        'stone_type' => [
            'column' => 'stone_type_id',
            'options' => [
                'none' => ['No stones', 'بدون فصوص'],
                'diamond' => ['Diamond', 'ألماس'],
                'zircon' => ['Zircon', 'زركون'],
                'gemstone' => ['Gemstones', 'أحجار كريمة'],
                'pearl' => ['Pearl', 'لؤلؤ'],
            ],
        ],
    ];

    public function up(): void
    {
        foreach (self::LEGACY as $group => $legacy) {
            $options = $legacy['options'];

            // Values in use that the list does not know about.
            $unknown = DB::table('products')->whereNotNull($group)->distinct()->pluck($group)
                ->reject(fn ($value) => isset($options[$value]));

            foreach ($unknown as $value) {
                $options[$value] = [$value, $value];
            }

            $sort = 0;

            foreach ($options as $slug => [$en, $ar]) {
                // Slugs are unique across both groups: a stored value that is already the other
                // group's slug (only possible in hand-edited rows) gets the group as a prefix.
                $optionSlug = DB::table('product_options')->where('slug', $slug)->where('group', '!=', $group)->exists()
                    ? "$group-$slug"
                    : $slug;

                $id = DB::table('product_options')->where('group', $group)->where('slug', $optionSlug)->value('id');

                if ($id === null) {
                    $id = DB::table('product_options')->insertGetId([
                        'group' => $group,
                        'slug' => $optionSlug,
                        'is_active' => true,
                        'sort_order' => $sort,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('product_option_translations')->insert([
                        ['product_option_id' => $id, 'locale' => 'en', 'name' => $en],
                        ['product_option_id' => $id, 'locale' => 'ar', 'name' => $ar],
                    ]);
                }

                DB::table('products')->where($group, $slug)->update([$legacy['column'] => $id]);

                $sort++;
            }
        }
    }

    public function down(): void
    {
        foreach (self::LEGACY as $group => $legacy) {
            $options = DB::table('product_options')->where('group', $group)->pluck('slug', 'id');

            foreach ($options as $id => $slug) {
                DB::table('products')->where($legacy['column'], $id)->update([
                    $group => strlen($slug) <= 20 ? $slug : null,
                    $legacy['column'] => null,
                ]);
            }
        }
    }
};
