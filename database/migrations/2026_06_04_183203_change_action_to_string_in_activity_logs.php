<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert ENUM to VARCHAR so new action types (LOGIN_FAILED, etc.) are allowed
        DB::statement("ALTER TABLE activity_logs MODIFY action VARCHAR(30) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE activity_logs MODIFY action ENUM('CREATE','UPDATE','DELETE','UPLOAD','LOGIN','LOGOUT') NOT NULL");
    }
};
