<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Phase 4c, step 1 of 3 (columns, data, then dropping the old string columns).
| `type`: jewelry | bar | coin (allowlist in config/catalog.php); every existing product is jewelry.
| gold_color_id / stone_type_id replace the old gold_color / stone_type strings. An option that
| products use cannot be removed (restrictOnDelete is the backstop for the model guard).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 10)->default('jewelry');
            // Catalog filters, like karat: indexed explicitly (MySQL would only index the FK itself).
            $table->foreignId('gold_color_id')->nullable()->index()->constrained('product_options')->restrictOnDelete();
            $table->foreignId('stone_type_id')->nullable()->index()->constrained('product_options')->restrictOnDelete();

            // The public pages always filter by type first (jewelry vs bullion), then by publishing.
            $table->index(['type', 'is_published']);
        });
    }

    public function down(): void
    {
        // Constraint, then index, then column: MySQL refuses to drop an index a constraint still
        // needs, and SQLite refuses to drop an indexed column.
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['gold_color_id']);
            $table->dropForeign(['stone_type_id']);
            $table->dropIndex(['gold_color_id']);
            $table->dropIndex(['stone_type_id']);
            $table->dropIndex(['type', 'is_published']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gold_color_id', 'stone_type_id', 'type']);
        });
    }
};
