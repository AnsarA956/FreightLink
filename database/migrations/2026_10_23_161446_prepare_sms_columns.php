<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Make sure the notification channel can hold "sms" as well as "email"
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('channel', 20)->change();
        });

        // 2. Add a phone number to users, only if there is none yet
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        // nothing to undo
    }
};