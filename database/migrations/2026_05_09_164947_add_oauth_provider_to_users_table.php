<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('password');
            $table->string('provider_id', 100)->nullable()->after('provider');
            $table->string('provider_avatar')->nullable()->after('provider_id');

            // (provider, provider_id) is unique so the same Google
            // account can't be linked to two different users. Email
            // already has a unique index from the original users table,
            // which prevents email collisions.
            $table->unique(['provider', 'provider_id']);
        });

        // Make password nullable — OAuth-only users don't have one. Doctrine
        // DBAL is required for change() in some drivers; on SQLite the
        // platform reissues the column without checking driver-specifics.
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id', 'provider_avatar']);
        });
    }
};
