<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatusPengobatan extends Model
{
    use HasFactory;

    protected $table = 'status_pengobatan';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
