<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // stone_type is a catalog filter (Phase 4), like karat and gold_color.
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('stone_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['stone_type']);
        });
    }
};
