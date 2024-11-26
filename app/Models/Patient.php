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
                    'telephone'      => $patient->user->telephone ?? '-',
                    'username'       => $patient->user->username ?? '-',
                    'email'          => $patient->user->email ?? '-',
                    'puskesmas_name' => $patient->puskesmas->name ?? '-',
                ];
            })
            ->toArray();
    }
}
