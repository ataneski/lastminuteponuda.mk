<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wa_phone_e164', 20)->nullable()->unique()->after('phone');
            $table->string('wa_pairing_code', 8)->nullable()->after('wa_phone_e164');
            $table->timestamp('wa_paired_at')->nullable()->after('wa_pairing_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['wa_phone_e164']);
            $table->dropColumn(['wa_phone_e164', 'wa_pairing_code', 'wa_paired_at']);
        });
    }
};
