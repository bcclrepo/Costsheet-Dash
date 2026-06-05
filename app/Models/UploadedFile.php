<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UploadedFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'area_id', 'year', 'quarter',
        'original_filename', 'stored_filename', 'file_path',
        'rows_imported', 'rows_skipped', 'status', 'notes',
        'area_totals',
        'uploaded_by',
    ];

    protected $casts = [
        'area_totals' => 'array',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
