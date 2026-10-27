<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_id', 20)->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_type_id')->constrained();
            $table->foreignId('cargo_type_id')->constrained();

            $table->text('pickup_address');
            $table->text('delivery_address');

            $table->decimal('weight_kg', 10, 2);
            $table->decimal('length_cm', 8, 2);
            $table->decimal('width_cm', 8, 2);
            $table->decimal('height_cm', 8, 2);
            $table->decimal('volumetric_kg', 10, 2);
            $table->decimal('chargeable_kg', 10, 2);
            $table->decimal('distance_km', 8, 2);

            // Price snapshot: saved exactly as the customer saw it
            $table->decimal('subtotal', 12, 2);
            $table->decimal('service_multiplier', 4, 2);
            $table->decimal('cargo_surcharge', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_percent', 5, 2);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total_price', 12, 2);

            $table->string('status', 30)->default('Booked');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};