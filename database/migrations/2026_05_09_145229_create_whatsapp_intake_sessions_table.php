<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_intake_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('wa_phone_e164', 20);
            $table->string('state', 20)->default('collecting');
            $table->json('media_paths')->nullable();
            $table->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_message_at');
            $table->timestamps();

            $table->index(['user_id', 'state']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_intake_sessions');
    }
};
