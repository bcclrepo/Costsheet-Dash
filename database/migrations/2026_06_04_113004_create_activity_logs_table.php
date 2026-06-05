<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_name', 100)->nullable();     // snapshot at log time
            $table->string('pis_number', 20)->nullable();
            $table->enum('action', ['CREATE', 'UPDATE', 'DELETE', 'UPLOAD', 'LOGIN', 'LOGOUT']);
            $table->string('model_type', 60)->nullable();     // e.g. CostsheetData, UploadedFile
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description')->nullable();        // human-readable summary
            $table->json('changes')->nullable();              // {field: {old, new}} for updates
            $table->string('area_name')->nullable();
            $table->string('mine_code', 20)->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('quarter', 5)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('log_file')->nullable();           // path to quarterly text file
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['year', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
