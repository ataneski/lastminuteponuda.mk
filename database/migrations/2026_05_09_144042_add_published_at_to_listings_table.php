<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('created_at');
            $table->index('published_at');
        });

        // Backfill: existing rows are considered published immediately
        // (their created_at is the publish timestamp).
        DB::statement('UPDATE listings SET published_at = created_at WHERE published_at IS NULL');
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
            $table->dropColumn('published_at');
        });
    }
};
