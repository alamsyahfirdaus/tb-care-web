<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class PatientTreatment extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'patient_treatments';
    protected $primaryKey = 'id';

    protected $guarded = [];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function treatmentType()
    {
        return $this->belongsTo(TreatmentType::class, 'treatment_type_id');
    }

    public function visits()
    {
        return $this->hasMany(TreatmentVisit::class, 'patient_treatment_id');
    }

    public function medicationRecords()
    {
        return $this->hasMany(MedicationRecord::class, 'patient_treatment_id');
    }

    public static function getPatientTreatments($filters = [])
    {
        $query = self::with(['patient.user', 'patient.puskesmas', 'treatmentType']);

        if (isset($filters['id']) && $filters['id']) {
            $query->where('id', $filters['id']);
        }

        if (isset($filters['patient_id']) && $filters['patient_id']) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (isset($filters['treatment_status']) && $filters['treatment_status']) {
            $query->where('treatment_status', $filters['treatment_status']);
        }

        $query->orderBy('id', 'desc');

        return $query->get()->map(function ($treatment) {
            $tTypeName = $treatment->treatmentType 
                ? $treatment->treatmentType->treatment_type . ' (' . $treatment->treatmentType->treatment_duration . ' ' . ucfirst($treatment->treatmentType->duration_unit) . ')'
                : 'Standar';

            return [
                'id'                 => $treatment->id,
                'patient_id'         => $treatment->patient_id,
                'treatment_type_id'  => $treatment->treatment_type_id,
                'full_name'          => optional($treatment->patient->user)->name ?? '-',
                'user_id'            => optional($treatment->patient)->user_id,
                'username'           => optional($treatment->patient->user)->username ?? '-',
                'gender'             => optional($treatment->patient->user)->gender ?? '-',
                'phone'              => optional($treatment->patient->user)->phone ?? '-',
                'puskesmas_name'     => optional($treatment->patient->puskesmas)->name ?? '-',
                'treatment_type'     => $tTypeName,
                'diagnosis_date'     => $treatment->diagnosis_date,
                'start_date'         => $treatment->start_date,
                'end_date'           => $treatment->end_date,
                'treatment_days'     => $treatment->treatment_days,
                'medication_time'    => $treatment->medication_time,
                'prescription'       => $treatment->prescription,
                'treatment_status'   => $treatment->treatment_status,
            ];
        });
    }

    public static function getTreatmentByPatientId($patientId)
    {
        return self::getPatientTreatments(['patient_id' => $patientId]);
    }

    public static function getTreatmentById($id)
    {
        $treatment = self::getPatientTreatments(['id' => $id]);
        return collect($treatment)->firstWhere('id', $id);
    }

    public static function getTreatmentDateRange($id)
    {
        $treatment = self::find($id);

        if (!$treatment || !$treatment->start_date || !$treatment->end_date) {
            return [];
        }

        $startDate = \DateTime::createFromFormat('Y-m-d', $treatment->start_date);
        $endDate   = \DateTime::createFromFormat('Y-m-d', $treatment->end_date);

        if (!$startDate || !$endDate) {
            return [];
        }

        $dateList = [];
        while ($startDate <= $endDate) {
            $dateList[] = $startDate->format('Y-m-d');
            $startDate->modify('+1 day');
        }

        return $dateList;
    }

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->user_type_id == 1) {
            return $query;
        }

        return $query->whereHas('patient', function ($q) use ($user) {
            $q->accessibleBy($user);
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($user->user_type_id == 1) {
            return true;
        }

        return optional($this->patient)->isAccessibleBy($user) ?? false;
    }
}
