<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class Screening extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'screenings';
    protected $guarded = [];

    protected $casts = [
        'screened_at' => 'datetime',
        'has_critical_symptom' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function category()
    {
        return $this->belongsTo(ScreeningCategory::class, 'screening_category_id');
    }

    public function answers()
    {
        return $this->hasMany(ScreeningAnswer::class, 'screening_id');
    }

    public function getNameAttribute()
    {
        return $this->person_name ?? (optional($this->user)->name ?? 'Peserta Skrining');
    }

    public function getScreeningDateAttribute()
    {
        return $this->screened_at ?? $this->created_at;
    }
}
