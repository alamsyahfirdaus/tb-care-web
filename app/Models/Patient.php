<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

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

            // Dinkes Provinsi (officer_type_id = 1)
            if ($officer->officer_type_id == 1) {
                if ($officer->district_id) {
                    $district = District::find($officer->district_id);
                    if ($district && $district->province_id) {
                        return $query->whereHas('subdistrict.district', function ($q) use ($district) {
                            $q->where('province_id', $district->province_id);
                        });
                    }
                }
                return $query;
            }

            // Dinkes Kab/Kota (officer_type_id = 2)
            if ($officer->officer_type_id == 2) {
                return $query->whereHas('subdistrict', function ($q) use ($officer) {
                    $q->where('district_id', $officer->district_id);
                });
            }

            // PJTB Puskesmas (officer_type_id = 3): All patients in the Puskesmas
            if ($officer->officer_type_id == 3) {
                return $query->where('puskesmas_id', $officer->puskesmas_id);
            }

            // Kader Puskesmas (officer_type_id = 4): Strictly scoped to assigned kader_areas
            if ($officer->officer_type_id == 4) {
                $areas = $officer->kaderAreas;
                if ($areas->isEmpty()) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where('puskesmas_id', $officer->puskesmas_id)
                    ->where(function ($q) use ($areas) {
                        foreach ($areas as $area) {
                            $q->orWhere(function ($sub) use ($area) {
                                $sub->where('village_id', $area->village_id)
                                    ->where('rw', $area->rw);
                                if (!is_null($area->rt) && $area->rt !== '') {
                                    $sub->where('rt', $area->rt);
                                }
                            });
                        }
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
