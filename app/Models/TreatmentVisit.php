<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentVisit extends Model
{
    use HasFactory;

    protected $table = 'treatment_visits';
    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'visit_date' => 'date:Y-m-d',
    ];

    public function patientTreatment()
    {
        return $this->belongsTo(PatientTreatment::class, 'patient_treatment_id');
    }

    public function patient()
    {
        return $this->hasOneThrough(
            Patient::class,
            PatientTreatment::class,
            'id', // Foreign key on patient_treatments table...
            'id', // Foreign key on patients table...
            'patient_treatment_id', // Local key on treatment_visits table...
            'patient_id' // Local key on patient_treatments table...
        );
    }
}
