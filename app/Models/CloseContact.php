<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class CloseContact extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'close_contacts';
    protected $guarded = [];

    protected $casts = [
        'screening_date' => 'date',
        'date_of_birth'  => 'date',
    ];

    protected $appends = [
        'gender_label',
    ];

    public function getGenderLabelAttribute()
    {
        return $this->gender === 'P' ? 'Perempuan' : 'Laki-laki';
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class, 'close_contact_id')->orderByDesc('id');
    }

    public function latestScreening()
    {
        return $this->hasOne(Screening::class, 'close_contact_id')->latestOfMany();
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
