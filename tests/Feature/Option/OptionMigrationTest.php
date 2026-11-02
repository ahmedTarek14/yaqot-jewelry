<?php

namespace Tests\Feature\Option;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 4c: gold_color / stone_type strings become product_options rows linked by id.
 * The three product migrations are rolled back to the old schema, filled with legacy rows,
 * then run again (and rolled back once more), all on the in-memory SQLite database.
 */
class OptionMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** In run order: columns, data move, drop of the old columns. */
    private const MIGRATIONS = [
        '2026_11_02_100002_add_type_and_option_columns_to_products_table.php',
        '2026_11_02_100003_move_gold_colors_and_stone_types_to_options.php',
        '2026_11_02_100004_drop_gold_color_and_stone_type_from_products_table.php',
    ];

    /**
     * @return array<int, string>
     */
    private function paths(): array
    {
        return array_map(fn (string $file) => base_path("Modules/Product/database/migrations/$file"), self::MIGRATIONS);
    }

    private function rollBack(): void
    {
        Artisan::call('migrate:rollback', ['--path' => $this->paths(), '--realpath' => true]);
    }

    private function migrate(): void
    {
        Artisan::call('migrate', ['--path' => $this->paths(), '--realpath' => true]);
    }

    private function legacyProduct(string $sku, ?string $color, ?string $stone, bool $trashed = false): void
    {
        DB::table('products')->insert([
            'sku' => $sku,
            'slug' => strtolower($sku),
            'karat' => 21,
            'gold_color' => $color,
            'stone_type' => $stone,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $trashed ? now() : null,
        ]);
    }

    public function test_a_fresh_install_has_the_owners_current_lists(): void
    {
        $this->assertSame(
            ['yellow', 'white', 'rose', 'multi'],
            DB::table('product_options')->where('group', 'gold_color')->orderBy('sort_order')->pluck('slug')->all(),
        );
        $this->assertSame(
            ['none', 'diamond', 'zircon', 'gemstone', 'pearl'],
            DB::table('product_options')->where('group', 'stone_type')->orderBy('sort_order')->pluck('slug')->all(),
        );
        $this->assertSame(0, DB::table('product_options')->where('is_active', false)->count());

        $names = DB::table('product_option_translations')
            ->join('product_options', 'product_options.id', '=', 'product_option_id')
            ->where('slug', 'multi')->pluck('name', 'locale')->all();
        $this->assertSame(['ar' => 'متعدد الألوان', 'en' => 'Multi-tone'], $names);
        $this->assertSame(0, DB::table('product_option_translations')->whereNull('name')->orWhere('name', '')->count());
    }

    public function test_existing_products_are_linked_to_their_options_and_become_jewelry(): void
    {
        $this->rollBack();
        DB::table('product_options')->delete();

        $this->assertTrue(Schema::hasColumns('products', ['gold_color', 'stone_type']));
        $this->assertFalse(Schema::hasColumns('products', ['type', 'gold_color_id', 'stone_type_id']));

        $this->legacyProduct('LEGACY-1', 'rose', 'pearl');
        $this->legacyProduct('LEGACY-2', 'white', 'none', trashed: true);
        $this->legacyProduct('LEGACY-3', null, null);
        $this->legacyProduct('LEGACY-4', 'green', 'ruby');
        // A hand-edited stone value equal to a gold colour's slug must not link to the gold colour.
        $this->legacyProduct('LEGACY-5', null, 'yellow');

        $this->migrate();

        $this->assertFalse(Schema::hasColumn('products', 'gold_color'));
        $this->assertFalse(Schema::hasColumn('products', 'stone_type'));

        $option = fn (?int $id) => $id === null ? null : DB::table('product_options')->where('id', $id)->value('slug');
        $row = fn (string $sku) => DB::table('products')->where('sku', $sku)->first();

        $this->assertSame(['rose', 'pearl'], [$option($row('LEGACY-1')->gold_color_id), $option($row('LEGACY-1')->stone_type_id)]);
        // A trashed product keeps its colour and stones too.
        $this->assertSame(['white', 'none'], [$option($row('LEGACY-2')->gold_color_id), $option($row('LEGACY-2')->stone_type_id)]);
        $this->assertNull($row('LEGACY-3')->gold_color_id);
        $this->assertNull($row('LEGACY-3')->stone_type_id);
        // A value outside the old lists still gets an option, named after the raw value.
        $this->assertSame(['green', 'ruby'], [$option($row('LEGACY-4')->gold_color_id), $option($row('LEGACY-4')->stone_type_id)]);

        $clash = DB::table('product_options')->where('id', $row('LEGACY-5')->stone_type_id)->first();
        $this->assertSame(['stone_type', 'stone_type-yellow'], [$clash->group, $clash->slug]);

        $this->assertSame(['jewelry'], DB::table('products')->distinct()->pluck('type')->all());
        $this->assertSame(4 + 1, DB::table('product_options')->where('group', 'gold_color')->count());
        $this->assertSame(5 + 2, DB::table('product_options')->where('group', 'stone_type')->count());
        $this->assertSame(
            ['ar' => 'green', 'en' => 'green'],
            DB::table('product_option_translations')->join('product_options', 'product_options.id', '=', 'product_option_id')
                ->where('slug', 'green')->pluck('name', 'locale')->all(),
        );
        $this->assertSame(
            ['ar' => 'لؤلؤ', 'en' => 'Pearl'],
            DB::table('product_option_translations')->join('product_options', 'product_options.id', '=', 'product_option_id')
                ->where('slug', 'pearl')->pluck('name', 'locale')->all(),
        );
    }

    public function test_the_migrations_roll_back_to_the_old_strings_and_run_again(): void
    {
        $this->rollBack();
        DB::table('product_options')->delete();
        $this->legacyProduct('LEGACY-1', 'rose', 'pearl');
        $this->legacyProduct('LEGACY-2', null, 'diamond');
        $this->migrate();

        $this->rollBack();

        $this->assertTrue(Schema::hasColumns('products', ['gold_color', 'stone_type']));
        $this->assertFalse(Schema::hasColumns('products', ['type', 'gold_color_id', 'stone_type_id']));
        $this->assertSame(
            ['LEGACY-1' => ['rose', 'pearl'], 'LEGACY-2' => [null, 'diamond']],
            DB::table('products')->orderBy('id')->get()->mapWithKeys(fn ($p) => [$p->sku => [$p->gold_color, $p->stone_type]])->all(),
        );

        $this->migrate();

        $this->assertTrue(Schema::hasColumns('products', ['type', 'gold_color_id', 'stone_type_id']));
        $this->assertFalse(Schema::hasColumn('products', 'gold_color'));
        $this->assertSame(1, DB::table('products')->whereNotNull('gold_color_id')->count());
        $this->assertSame(2, DB::table('products')->whereNotNull('stone_type_id')->count());
    }

    public function test_a_used_option_cannot_be_removed_by_the_database_either(): void
    {
        $rose = DB::table('product_options')->where('slug', 'rose')->value('id');
        DB::table('products')->insert([
            'sku' => 'FK-1', 'slug' => 'fk-1', 'gold_color_id' => $rose, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('product_options')->where('id', $rose)->delete();
    }
}
