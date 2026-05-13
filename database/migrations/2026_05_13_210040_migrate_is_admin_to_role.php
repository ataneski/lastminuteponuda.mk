<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Promote every legacy is_admin=true user to the new role='admin'
     * value. The is_admin column stays in the schema for now (other
     * code may still check it), but the source of truth becomes role.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('is_admin', true)
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'admin')
            ->update(['role' => 'agency']);
    }
};
