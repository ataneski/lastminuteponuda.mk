<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agency_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name');
            $table->string('sender_email');
            $table->string('sender_phone', 50)->nullable();
            $table->text('body');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'created_at']);
            $table->index(['agency_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
