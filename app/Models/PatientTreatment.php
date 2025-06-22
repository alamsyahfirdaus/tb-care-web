<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientTreatment extends Model
{
    use HasFactory;

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

    public static function getPatientTreatments($filters = [])
    {
        $query = self::with(['patient.user', 'treatmentType']);

        if (isset($filters['id']) && $filters['id']) {
            $query->where('id', $filters['id']);
        }

        if (isset($filters['patient_id']) && $filters['patient_id']) {
            $query->where('patient_id', $filters['patient_id']);
        }

        $query->orderBy('id', 'desc');

        return $query->get()->map(function ($treatment) {
            return [
                'id'                 => $treatment->id,
                'patient_id'         => $treatment->patient_id,
                'treatment_type_id'  => $treatment->treatment_type_id,
                'full_name'          => $treatment->patient->user->name,
                'user_id'            => $treatment->patient->user_id,
                'username'           => $treatment->patient->user->username,
                'gender'             => $treatment->patient->user->gender,
                'phone'              => $treatment->patient->user->phone ?? '-',
                'treatment_type'     => $treatment->treatmentType->treatment_type . ' (' . $treatment->treatmentType->treatment_duration . ' ' . ucfirst($treatment->treatmentType->duration_unit) . ')',
                'diagnosis_date'     => $treatment->diagnosis_date,
                'start_date'         => $treatment->start_date,
                'end_date'           => $treatment->end_date,
                'medication_time'    => $treatment->medication_time,
                'prescription'       => $treatment->prescription,
                'treatment_status'   => $treatment->status,
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

        $dateList = [];
        while ($startDate <= $endDate) {
            // $formattedDate = $startDate->format('Y-m-d');

            $dateList[] = $startDate->format('Y-m-d');

            // $dateList[] = [
            //     $formattedDate => \App\Helpers\DateHelper::convertDate($formattedDate)
            // ];
            $startDate->modify('+1 day');
        }

        return $dateList;
    }
}
