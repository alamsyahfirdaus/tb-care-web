<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    use HasFactory;

    protected $table = 'villages';

    protected $guarded = [];

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'village_id');
    }

    public function kaderAreas()
    {
        return $this->hasMany(KaderArea::class, 'village_id');
    }
}
