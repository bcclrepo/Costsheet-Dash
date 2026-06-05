<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('browser', 60)->nullable()->after('ip_address');
            $table->string('platform', 60)->nullable()->after('browser');
            $table->string('device', 30)->nullable()->after('platform');
            $table->text('user_agent')->nullable()->after('device');
            $table->string('url', 255)->nullable()->after('user_agent');
            $table->string('method', 10)->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['browser', 'platform', 'device', 'user_agent', 'url', 'method']);
        });
    }
};
