<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationRecord extends Model
{
    use HasFactory;

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
}
