<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->decimal('percent', 5, 2);
            $table->date('expires_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // The shipment remembers which code was used and what it gave (a snapshot, like the price)
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('discount_code_id')->nullable()->after('cargo_type_id')
                  ->constrained('discount_codes')->nullOnDelete();
            $table->string('discount_code', 30)->nullable()->after('discount_amount');
            $table->decimal('discount_percent', 5, 2)->nullable()->after('discount_code');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_code_id');
            $table->dropColumn(['discount_code', 'discount_percent']);
        });

        Schema::dropIfExists('discount_codes');
    }
};