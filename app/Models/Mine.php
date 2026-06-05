<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mine extends Model
{
    use HasFactory;

    protected $fillable = ['area_id', 'mine_code', 'mine_name', 'mine_type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function costsheetData()
    {
        return $this->hasMany(CostsheetData::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->mine_code . ' - ' . $this->mine_name;
    }
}
