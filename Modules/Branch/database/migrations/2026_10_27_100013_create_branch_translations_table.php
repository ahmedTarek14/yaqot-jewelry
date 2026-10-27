<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('name');
            $table->string('address', 500);
            $table->string('working_hours', 500)->nullable();
            $table->unique(['branch_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_translations');
    }
};
