<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('base_charge', 10, 2)->default(200);
            $table->decimal('rate_per_kg', 10, 2)->default(50);
            $table->decimal('rate_per_km', 10, 2)->default(10);
            $table->decimal('tax_percent', 5, 2)->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_settings');
    }
};