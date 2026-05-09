<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->string('agency_name');
            $table->string('agency_contact');
            $table->string('title');
            $table->string('destination');
            $table->string('country');
            $table->string('hotel_name');
            $table->unsignedTinyInteger('hotel_stars');
            $table->string('board_type');
            $table->string('transport');
            $table->date('departure_date');
            $table->date('return_date');
            $table->unsignedSmallInteger('nights');
            $table->unsignedInteger('price_per_person');
            $table->string('currency', 3);
            $table->unsignedSmallInteger('available_seats');
            $table->text('description');
            $table->json('features');
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->index('destination');
            $table->index('departure_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
