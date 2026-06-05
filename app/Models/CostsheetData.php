<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CostsheetData extends Model
{
    use HasFactory;

    protected $table = 'costsheet_data';

    protected $fillable = [
        'mine_id', 'year', 'quarter',
        'production_qty', 'dispatch_qty', 'obr_qty', 'stripping_ratio',
        'net_sales', 'spt', 'total_relevant_cost', 'cpt',
        'costing_profit', 'profit_per_tonne',
        'uploaded_by', 'updated_by',
    ];

    protected $casts = [
        'production_qty' => 'decimal:2',
        'dispatch_qty' => 'decimal:2',
        'obr_qty' => 'decimal:2',
        'stripping_ratio' => 'decimal:4',
        'net_sales' => 'decimal:2',
        'spt' => 'decimal:2',
        'total_relevant_cost' => 'decimal:2',
        'cpt' => 'decimal:2',
        'costing_profit' => 'decimal:2',
        'profit_per_tonne' => 'decimal:2',
    ];

    public function mine()
    {
        return $this->belongsTo(Mine::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
