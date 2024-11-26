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

    // public static function getTreatmentDuration($treatment_type_id)
    // {
    //     $query = self::find($treatment_type_id);

    //     if (!$query) {
    //         return null;
    //     }

    //     switch ($query->duration_unit) {
    //         case 'minggu':
    //             return $query->treatment_duration . ' week';
    //         case 'bulan':
    //             return $query->treatment_duration . ' month';
    //         case 'tahun':
    //             return $query->treatment_duration . ' year';
    //         default:
    //             return null;
    //     }
    // }
}
