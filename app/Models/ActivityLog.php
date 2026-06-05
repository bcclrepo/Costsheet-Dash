<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'pis_number',
        'action', 'model_type', 'model_id', 'description',
        'changes', 'area_name', 'mine_code', 'year', 'quarter',
        'ip_address', 'browser', 'platform', 'device', 'user_agent',
        'url', 'method', 'log_file',
    ];

    protected $casts = ['changes' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
