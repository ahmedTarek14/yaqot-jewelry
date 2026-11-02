<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Phase 4c, step 3 of 3: the old string columns (and their indexes) go once the data move has run.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['gold_color']);
            $table->dropIndex(['stone_type']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gold_color', 'stone_type']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('gold_color', 20)->nullable()->index();
            $table->string('stone_type', 20)->nullable()->index();
        });
    }
};
