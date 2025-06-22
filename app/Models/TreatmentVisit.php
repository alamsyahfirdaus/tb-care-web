<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentVisit extends Model
{
    use HasFactory;

    protected $table = 'treatment_visits';
    protected $primaryKey = 'id';

    protected $guarded = [];
}
