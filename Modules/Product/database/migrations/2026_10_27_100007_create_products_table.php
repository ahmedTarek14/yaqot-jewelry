<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 64)->unique();
            $table->string('slug')->unique();
            // A brand with products cannot be deleted (Brand model guard); null = house or unbranded.
            $table->foreignId('brand_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('karat')->nullable()->index();
            $table->string('gold_color', 20)->nullable()->index();
            $table->string('stone_type', 20)->nullable();
            $table->decimal('weight_grams', 8, 3)->nullable()->index();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'published_at']);
            $table->index(['is_published', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
