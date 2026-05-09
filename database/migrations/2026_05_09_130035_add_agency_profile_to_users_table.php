<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('email');
            $table->string('display_name')->nullable()->after('slug');
            $table->string('tagline', 200)->nullable()->after('display_name');
            $table->text('description')->nullable()->after('tagline');
            $table->string('logo_url')->nullable()->after('description');
            $table->string('cover_url')->nullable()->after('logo_url');
            $table->string('website')->nullable()->after('cover_url');
            $table->string('phone', 50)->nullable()->after('website');
            $table->string('address', 200)->nullable()->after('phone');
            $table->string('accent_color', 7)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'display_name', 'tagline', 'description',
                'logo_url', 'cover_url', 'website', 'phone',
                'address', 'accent_color',
            ]);
        });
    }
};
