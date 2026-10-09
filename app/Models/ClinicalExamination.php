<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class ClinicalExamination extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'clinical_examinations';
    protected $guarded = [];

    protected $casts = [
        'examination_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function officer()
    {
        return $this->belongsTo(Officer::class, 'officer_id');
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
