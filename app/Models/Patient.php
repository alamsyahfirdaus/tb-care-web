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

    public static function getPatientWithUser()
    {
        $patients = self::with('user')->get();
        
        $patients = $patients->sortBy(function ($patient) {
            return $patient->user->name ?? '';
        });
        
        $patientData = [];
        
        foreach ($patients as $patient) {
            if ($patient->user) {
                $patientData[$patient->id] = $patient->user->name . ' (' . $patient->user->username . ') - ' . $patient->user->email;
            }
        }
        
        return $patientData;
    }
    
}
