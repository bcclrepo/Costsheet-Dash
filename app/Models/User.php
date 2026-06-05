<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'pis_number', 'email', 'mobile_no',
        'password', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    /** Areas this user is assigned to (for area_admin and viewer roles). */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_user');
    }

    public function uploadedFiles()
    {
        return $this->hasMany(UploadedFile::class, 'uploaded_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /** Returns true if the user can access the given area_id. */
    public function canAccessArea(int $areaId): bool
    {
        if ($this->hasRole(['super_admin', 'admin'])) return true;
        return $this->areas->contains('id', $areaId);
    }

    /**
     * Returns an Eloquent query scope of areas this user is allowed to see.
     * super_admin/admin → all active areas.
     * area_admin/viewer → only their assigned areas.
     */
    public function accessibleAreas()
    {
        $query = Area::where('is_active', true)->orderBy('name');
        if (!$this->hasRole(['super_admin', 'admin'])) {
            $query->whereIn('id', $this->areas->pluck('id'));
        }
        return $query;
    }

    public function isSuperAdmin(): bool { return $this->hasRole('super_admin'); }
    public function isAdmin(): bool      { return $this->hasRole('admin'); }
    public function isAreaAdmin(): bool  { return $this->hasRole('area_admin'); }
    public function isViewer(): bool     { return $this->hasRole('viewer'); }
}
