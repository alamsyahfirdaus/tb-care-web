<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationalMaterialRead extends Model
{
    use HasFactory;

    protected $table = 'educational_material_reads';

    protected $fillable = [
        'user_id',
        'educational_material_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function material()
    {
        return $this->belongsTo(EducationalMaterial::class, 'educational_material_id');
    }
}
