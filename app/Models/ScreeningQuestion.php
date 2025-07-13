<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningQuestion extends Model
{
    use HasFactory;

    protected $table = 'screening_questions';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(ScreeningCategory::class, 'screening_category_id');
    }

    public function groupParent()
    {
        return $this->belongsTo(ScreeningQuestion::class, 'group_id');
    }

    public function subQuestions()
    {
        return $this->hasMany(ScreeningQuestion::class, 'group_id');
    }
}
