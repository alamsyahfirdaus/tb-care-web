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
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
