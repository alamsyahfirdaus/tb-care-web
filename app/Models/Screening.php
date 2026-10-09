<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class Screening extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'screenings';
    protected $guarded = [];

    protected $casts = [
        'screened_at' => 'datetime',
        'has_critical_symptom' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function closeContact()
    {
        return $this->belongsTo(CloseContact::class, 'close_contact_id');
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function category()
    {
        return $this->belongsTo(ScreeningCategory::class, 'screening_category_id');
    }

    public function answers()
    {
        return $this->hasMany(ScreeningAnswer::class, 'screening_id');
    }

    public function getNameAttribute()
    {
        return $this->person_name ?? (optional($this->user)->name ?? 'Peserta Skrining');
    }

    public function getScreeningDateAttribute()
    {
        return $this->screened_at ?? $this->created_at;
    }

    public function scopeAccessibleBy($query, User $user)
    {
        // 1. Admin: full access
        if ($user->user_type_id == 1) {
            return $query;
        }

        // 2. Pasien: only self or linked patient
        if ($user->user_type_id == 2) {
            $patientId = optional($user->patient)->id;
            return $query->where(function ($q) use ($user, $patientId) {
                $q->where('user_id', $user->id);
                if ($patientId) {
                    $q->orWhere('patient_id', $patientId);
                }
            });
        }

        // 3. Petugas
        if ($user->user_type_id == 3) {
            $officer = $user->officer;
            if (!$officer) {
                return $query->whereRaw('1 = 0');
            }

            // Dinkes Provinsi
            if ($officer->officer_type_id == 1) {
                $dist = District::find($officer->district_id);
                $provId = $dist ? $dist->province_id : null;
                if (!$provId) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where(function ($q) use ($provId, $user) {
                    $q->whereHas('puskesmas.subdistrict.district', fn($pq) => $pq->where('province_id', $provId))
                      ->orWhereHas('subdistrict.district', fn($sq) => $sq->where('province_id', $provId))
                      ->orWhereHas('village.subdistrict.district', fn($vq) => $vq->where('province_id', $provId))
                      ->orWhereHas('patient', fn($pq) => $pq->accessibleBy($user));
                });
            }

            // Dinkes Kab/Kota
            if ($officer->officer_type_id == 2) {
                $districtId = $officer->district_id;
                if (!$districtId) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where(function ($q) use ($districtId, $user) {
                    $q->whereHas('puskesmas.subdistrict', fn($pq) => $pq->where('district_id', $districtId))
                      ->orWhereHas('subdistrict', fn($sq) => $sq->where('district_id', $districtId))
                      ->orWhereHas('village.subdistrict', fn($vq) => $vq->where('district_id', $districtId))
                      ->orWhereHas('patient', fn($pq) => $pq->accessibleBy($user));
                });
            }

            // PJTB Puskesmas
            if ($officer->officer_type_id == 3) {
                if (!$officer->puskesmas_id) {
                    return $query->whereRaw('1 = 0');
                }
                return $query->where(function ($q) use ($officer, $user) {
                    $q->where('puskesmas_id', $officer->puskesmas_id)
                      ->orWhereHas('patient', fn($pq) => $pq->where('puskesmas_id', $officer->puskesmas_id));
                });
            }

            // Kader Puskesmas
            if ($officer->officer_type_id == 4) {
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('patient', fn($pq) => $pq->accessibleBy($user));
                });
            }
        }

        return $query->whereRaw('1 = 0');
    }

    public function isAccessibleBy(User $user): bool
    {
        return self::where('id', $this->id)->accessibleBy($user)->exists();
    }
}
