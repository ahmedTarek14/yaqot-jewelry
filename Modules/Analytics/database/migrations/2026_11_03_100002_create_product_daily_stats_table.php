<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per product per day: aggregate counters only, no IPs, no visitor data.
        Schema::create('product_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('whatsapp_clicks')->default(0);

            $table->unique(['product_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_daily_stats');
    }
};
