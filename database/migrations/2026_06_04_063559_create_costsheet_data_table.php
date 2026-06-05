<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costsheet_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mine_id')->constrained('mines')->onDelete('cascade');
            $table->year('year');
            $table->enum('quarter', ['Q1', 'Q2', 'Q3', 'Q4']);
            $table->decimal('production_qty', 15, 2)->nullable()->comment('Formula 1 - Production Qty (TE)');
            $table->decimal('dispatch_qty', 15, 2)->nullable()->comment('Formula 2 - Dispatch Qty (TE) excl ST');
            $table->decimal('obr_qty', 15, 2)->nullable()->comment('Formula 3 - OBR Qty (M3)');
            $table->decimal('stripping_ratio', 10, 4)->nullable()->comment('Formula 4 = 3/1');
            $table->decimal('net_sales', 15, 2)->nullable()->comment('Formula 5 - Net Sales (Lakhs)');
            $table->decimal('spt', 10, 2)->nullable()->comment('Formula 6 = 5/2 - SPT (Rs/Tonne)');
            $table->decimal('total_relevant_cost', 15, 2)->nullable()->comment('Formula 7 - Total Relevant Cost (Lakhs)');
            $table->decimal('cpt', 10, 2)->nullable()->comment('Formula 8 = 7/2 - CPT (Rs/Tonne)');
            $table->decimal('costing_profit', 15, 2)->nullable()->comment('Formula 9 = 5-7 - Costing Profit (Lakhs)');
            $table->decimal('profit_per_tonne', 10, 2)->nullable()->comment('Formula 10 = 9/2 - Profit (Rs/Tonne)');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->unique(['mine_id', 'year', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costsheet_data');
    }
};
