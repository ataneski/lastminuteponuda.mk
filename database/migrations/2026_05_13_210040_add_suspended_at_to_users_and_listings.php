<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('subscription_until');
            $table->string('suspension_reason', 255)->nullable()->after('suspended_at');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('published_at');
            $table->string('suspension_reason', 255)->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'suspension_reason']);
        });
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'suspension_reason']);
        });
    }
};
