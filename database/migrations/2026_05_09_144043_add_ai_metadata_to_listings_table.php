<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('ai_generated_at')->nullable()->after('published_at');
            $table->json('ai_raw_response')->nullable()->after('ai_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['ai_generated_at', 'ai_raw_response']);
        });
    }
};
