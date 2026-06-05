<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function mines()
    {
        return $this->hasMany(Mine::class);
    }

    public function uploadedFiles()
    {
        return $this->hasMany(UploadedFile::class);
    }
}
