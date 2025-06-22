<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentType extends Model
{
    use HasFactory;

    protected $table = 'treatment_types';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public static function getTreatmentTypes()
    {
        return self::all()
            ->map(function ($treatmentType) {
                return [
                    'id'             => $treatmentType->id,
                    'treatment_type' => $treatmentType->treatment_type,
                    'duration'       => $treatmentType->treatment_duration . ' ' . ucfirst($treatmentType->duration_unit),
                    'description'    => $treatmentType->description,
                ];
            })
            ->toArray();
    }
}
