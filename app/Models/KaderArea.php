<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KaderArea extends Model
{
    use HasFactory;

    protected $table = 'kader_areas';

    protected $guarded = [];

    public function officer()
    {
        return $this->belongsTo(Officer::class, 'officer_id');
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }
}
