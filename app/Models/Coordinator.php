<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coordinator extends Model
{
    use HasFactory;

    protected $table = 'coordinators';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public static function getCoordWithUser()
    {
        $coords = self::with('user')->get();
        
        $coords = $coords->sortBy(function ($coord) {
            return $coord->user->name ?? '';
        });
        
        $coordData = [];
        
        foreach ($coords as $coord) {
            if ($coord->user) {
                $coordData[$coord->id] = $coord->user->name . ' (' . $coord->user->username . ') - ' . $coord->user->email;
            }
        }
        
        return $coordData;
    }

    public static function getCoordTypes($id = null)
    {
        $coordTypes = [
            '1' => 'Penaggung Jawab TB',
            '2' => 'Kader Puskesmas',
        ];
    
        if ($id !== null && isset($coordTypes[$id])) {
            return $coordTypes[$id];
        }
        
        return $coordTypes;
    }
}
