<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task', 40);
            $table->string('level', 10)->default('info');   // info or warning
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message', 500);
            $table->timestamps();

            $table->index(['task', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};