<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_tier', 20)->default('free')->after('accent_color');
            $table->timestamp('subscription_until')->nullable()->after('subscription_tier');
            $table->index('subscription_tier');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['subscription_tier']);
            $table->dropColumn(['subscription_tier', 'subscription_until']);
        });
    }
};
