<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| One table for the owner-managed product attributes (gold colours, stone types),
| told apart by `group`. They share every column and every rule (translated name,
| slug, active flag, order, guarded delete), so two tables would only duplicate them.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->string('group', 20);
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group', 'is_active', 'sort_order']);
        });

        Schema::create('product_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->unique(['product_option_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_translations');
        Schema::dropIfExists('product_options');
    }
};
