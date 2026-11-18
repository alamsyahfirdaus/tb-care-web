<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Traits\HasEncryptedId;

class TreatmentType extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'treatment_types';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function patientTreatments()
    {
        return $this->hasMany(PatientTreatment::class, 'treatment_type_id');
    }

    public static function getTreatmentTypes()
    {
        return self::all()
            ->map(function ($treatmentType) {
                return [
                    'id'                 => $treatmentType->id,
                    'treatment_type'     => $treatmentType->treatment_type,
                    'treatment_duration' => (int)$treatmentType->treatment_duration,
                    'duration_unit'      => $treatmentType->duration_unit,
                    'duration'           => $treatmentType->treatment_duration . ' ' . ucfirst($treatmentType->duration_unit),
                    'description'        => $treatmentType->description,
                ];
            })
            ->toArray();
    }
}
