<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningAnswer extends Model
{
    use HasFactory;

    protected $table = 'screening_answers';
    protected $guarded = [];

    protected $casts = [
        'is_critical' => 'boolean',
    ];

    public function screening()
    {
        return $this->belongsTo(Screening::class, 'screening_id');
    }

    public function question()
    {
        return $this->belongsTo(ScreeningQuestion::class, 'screening_question_id');
    }
}
