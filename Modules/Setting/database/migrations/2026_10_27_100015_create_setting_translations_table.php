<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setting_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setting_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->text('value')->nullable();
            $table->unique(['setting_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setting_translations');
    }
};
