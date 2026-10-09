<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class Patient extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'patients';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'treatment_start_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function treatments()
    {
        return $this->hasMany(PatientTreatment::class, 'patient_id');
    }

    public function medicationSchedule()
    {
        return $this->hasOne(PatientMedicationSchedule::class, 'patient_id')->where('is_active', true);
    }

    public function medicationSchedules()
    {
        return $this->hasMany(PatientMedicationSchedule::class, 'patient_id');
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class, 'patient_id');
    }

    public function examinations()
    {
        return $this->hasMany(ClinicalExamination::class, 'patient_id');
    }

    public function closeContacts()
    {
        return $this->hasMany(CloseContact::class, 'patient_id');
    }

    /**
     * Centralized patient access scope for all roles.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleBy($query, User $user)
    {
        // 1. Administrator (user_type_id = 1): Full access to all patients
        if ($user->user_type_id == 1) {
            return $query;
        }

        // 2. Pasien (user_type_id = 2): Only self
        if ($user->user_type_id == 2) {
            return $query->where('user_id', $user->id);
        }

        // 3. Petugas (user_type_id = 3)
        if ($user->user_type_id == 3) {
            $officer = $user->officer;
            if (!$officer) {
                return $query->whereRaw('1 = 0');
            }

            // Dinkes Provinsi (officer_type_id = 1): Pasien dalam provinsi faskes atau domisili
            if ($officer->officer_type_id == 1) {
                $provinceId = null;
                if ($officer->district_id) {
                    $district = District::find($officer->district_id);
                    $provinceId = $district ? $district->province_id : null;
                }

                if (!$provinceId) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where(function ($q) use ($provinceId) {
                    $q->whereHas('puskesmas.subdistrict.district', function ($pq) use ($provinceId) {
                        $pq->where('province_id', $provinceId);
                    })
                    ->orWhereHas('subdistrict.district', function ($sq) use ($provinceId) {
                        $sq->where('province_id', $provinceId);
                    })
                    ->orWhereHas('village.subdistrict.district', function ($vq) use ($provinceId) {
                        $vq->where('province_id', $provinceId);
                    });
                });
            }

            // Dinkes Kab/Kota (officer_type_id = 2): Pasien dalam kab/kota faskes atau domisili
            if ($officer->officer_type_id == 2) {
                $districtId = $officer->district_id;
                if (!$districtId) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where(function ($q) use ($districtId) {
                    $q->whereHas('puskesmas.subdistrict', function ($pq) use ($districtId) {
                        $pq->where('district_id', $districtId);
                    })
                    ->orWhereHas('subdistrict', function ($sq) use ($districtId) {
                        $sq->where('district_id', $districtId);
                    })
                    ->orWhereHas('village.subdistrict', function ($vq) use ($districtId) {
                        $vq->where('district_id', $districtId);
                    });
                });
            }

            // PJTB Puskesmas (officer_type_id = 3): All patients in the Puskesmas
            if ($officer->officer_type_id == 3) {
                if (!$officer->puskesmas_id) {
                    return $query->whereRaw('1 = 0');
                }
                return $query->where('puskesmas_id', $officer->puskesmas_id);
            }

            // Kader Puskesmas (officer_type_id = 4): Strictly scoped to assigned kader_areas within Puskesmas
            if ($officer->officer_type_id == 4) {
                $areas = $officer->kaderAreas;
                if ($areas->isEmpty()) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where(function ($q) use ($officer, $areas) {
                    if ($officer->puskesmas_id) {
                        $q->where('puskesmas_id', $officer->puskesmas_id);
                    }
                    $q->where(function ($aq) use ($areas) {
                        foreach ($areas as $area) {
                            $aq->orWhere(function ($sub) use ($area) {
                                if ($area->village_id) {
                                    $sub->where('village_id', $area->village_id);
                                } elseif ($area->subdistrict_id) {
                                    $sub->where('subdistrict_id', $area->subdistrict_id);
                                }

                                if (!is_null($area->rw) && $area->rw !== '') {
                                    $rwStr = (string)$area->rw;
                                    $rwClean = ltrim($rwStr, '0');
                                    $sub->where(function ($rwQ) use ($rwStr, $rwClean) {
                                        $rwQ->where('rw', $rwStr)
                                            ->orWhere('rw', $rwClean)
                                            ->orWhere('rw', sprintf('%02d', (int)$rwStr))
                                            ->orWhere('rw', sprintf('%03d', (int)$rwStr));
                                    });
                                }

                                if (!is_null($area->rt) && $area->rt !== '') {
                                    $rtStr = (string)$area->rt;
                                    $rtClean = ltrim($rtStr, '0');
                                    $sub->where(function ($rtQ) use ($rtStr, $rtClean) {
                                        $rtQ->where('rt', $rtStr)
                                            ->orWhere('rt', $rtClean)
                                            ->orWhere('rt', sprintf('%02d', (int)$rtStr))
                                            ->orWhere('rt', sprintf('%03d', (int)$rtStr));
                                    });
                                }
                            });
                        }
                    });
                });
            }
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Check whether this patient can be accessed by the given user.
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function isAccessibleBy(User $user): bool
    {
        return self::where('id', $this->id)->accessibleBy($user)->exists();
    }

    public static function getPatientWithUser()
    {
        $patients = self::with('user')->get();

        $patients = $patients->sortBy(fn($patient) => $patient->user->name ?? '');

        $patientData = $patients->mapWithKeys(function ($patient) {
            if ($patient->user) {
                return [
                    $patient->id => $patient->user->name . ' (' . $patient->user->username . ')',
                ];
            }
            return [];
        });

        return $patientData->toArray();
    }

    public static function getAllPatients()
    {
        $puskesmas           = Puskesmas::getAllPuskesmas();
        $puskesmasCollection = collect($puskesmas);

        $puskesmasIds        = $puskesmasCollection->pluck('id')->toArray();

        $query = self::with(['user', 'puskesmas']);

        if (!empty($puskesmasIds)) {
            $query->whereIn('puskesmas_id', $puskesmasIds);
        }

        return $query
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($patient) {
                return [
                    'id'             => $patient->id,
                    'user_id'        => $patient->user_id,
                    'full_name'      => $patient->user->name,
                    'gender'         => $patient->user->gender,
                    'phone'      => $patient->user->phone ?? '-',
                    'username'       => $patient->user->username ?? '-',
                    'email'          => $patient->user->email ?? '-',
                    'puskesmas_name' => $patient->puskesmas->name ?? '-',
                ];
            })
            ->toArray();
    }

    public static function getByUserId($userId)
    {
        return self::where('user_id', $userId)->first();
    }
}
