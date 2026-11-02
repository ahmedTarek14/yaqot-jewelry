<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A migration that only the test registers (SystemMigrateTest): it stands for "an upload that brought a migration".
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yq_probe', function (Blueprint $table) {
            $table->id();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yq_probe');
    }
};
