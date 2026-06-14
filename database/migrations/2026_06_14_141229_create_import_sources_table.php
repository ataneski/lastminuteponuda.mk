<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120)->nullable();
            $table->string('url', 500);
            $table->string('schedule', 20)->default('manual'); // manual|hourly|6h|daily
            $table->boolean('active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_status', 20)->nullable(); // success|failed|partial
            $table->text('last_error')->nullable();
            $table->unsignedInteger('last_extracted_count')->default(0);
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'active']);
            $table->index('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_sources');
    }
};
