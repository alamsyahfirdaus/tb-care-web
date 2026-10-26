<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Traits\HasEncryptedId;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasEncryptedId;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Define relationship with UserType model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userType()
    {
        return $this->belongsTo(UserType::class, 'user_type_id');
    }

    public function role()
    {
        return $this->belongsTo(UserType::class, 'user_type_id');
    }

        public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function officer()
    {
        return $this->hasOne(Officer::class);
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class, 'user_id');
    }

    public function educationalMaterials()
    {
        return $this->hasMany(EducationalMaterial::class, 'created_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    public function getPhoneNumberAttribute()
    {
        return $this->attributes['phone'] ?? null;
    }

    public function setPhoneNumberAttribute($value)
    {
        $this->attributes['phone'] = $value;
    }

    /**
     * Role Verification Helpers
     */
    public function isSuperAdmin(): bool
    {
        return (int)$this->user_type_id === 1;
    }

    public function isPatient(): bool
    {
        return (int)$this->user_type_id === 2;
    }

    public function isOfficer(): bool
    {
        return (int)$this->user_type_id === 3;
    }

    public function isDinkesProvinsi(): bool
    {
        return $this->isOfficer() && optional($this->officer)->isDinkesProvinsi();
    }

    public function isDinkesKabKota(): bool
    {
        return $this->isOfficer() && optional($this->officer)->isDinkesKabKota();
    }

    public function isPJTB(): bool
    {
        return $this->isOfficer() && optional($this->officer)->isPJTB();
    }

    public function isKader(): bool
    {
        return $this->isOfficer() && optional($this->officer)->isKader();
    }

    public function canAccessWebAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isOfficer()) {
            // Kader is excluded from Web Admin, redirected to mobile
            return !$this->isKader();
        }

        return false;
    }

    public function getRoleDisplayNameAttribute(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Super Administrator';
        }
        if ($this->isPatient()) {
            return 'Pasien TB';
        }
        if ($this->isOfficer() && $this->officer) {
            return $this->officer->officer_type_name;
        }
        return optional($this->role)->name ?? 'Pengguna';
    }
}
