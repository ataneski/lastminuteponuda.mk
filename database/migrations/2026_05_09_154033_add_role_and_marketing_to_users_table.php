<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('agency')->after('email');
            $table->string('first_name', 60)->nullable()->after('role');
            $table->string('last_name', 60)->nullable()->after('first_name');
            $table->boolean('marketing_consent')->default(false)->after('last_name');
            $table->timestamp('marketing_consent_at')->nullable()->after('marketing_consent');
            $table->index('role');
        });

        // All existing users at this point are agencies (the platform was
        // agency-only before this migration). Set explicitly so future
        // role checks don't depend on the column default.
        DB::table('users')->update(['role' => 'agency']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'first_name', 'last_name', 'marketing_consent', 'marketing_consent_at']);
        });
    }
};
