<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class MedicationRecord extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'medication_records';
    protected $primaryKey = 'id';

    protected $guarded = [];

    public static function getRecordByDate($patient_treatment_id, $current_date)
    {
        return self::where('patient_treatment_id', $patient_treatment_id)
            ->whereDate('taken_at', $current_date)
            ->first();
    }

    public static function countRecords($patient_treatment_id)
    {
        return self::where('patient_treatment_id', $patient_treatment_id)->count();
    }

    public function patientTreatment()
    {
        return $this->belongsTo(PatientTreatment::class, 'patient_treatment_id');
    }

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->user_type_id == 1) {
            return $query;
        }

        return $query->whereHas('patientTreatment.patient', function ($q) use ($user) {
            $q->accessibleBy($user);
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($user->user_type_id == 1) {
            return true;
        }

        return optional(optional($this->patientTreatment)->patient)->isAccessibleBy($user) ?? false;
    }
}
