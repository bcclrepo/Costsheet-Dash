<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas')->onDelete('cascade');
            $table->string('mine_code', 20);
            $table->unique(['area_id', 'mine_code']);
            $table->string('mine_name');
            $table->enum('mine_type', ['OCM', 'UG', 'WASHERY', 'OTHER'])->default('OCM');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mines');
    }
};
