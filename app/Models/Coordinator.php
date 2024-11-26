<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

        $coords = $coords->sortBy(fn($coord) => $coord->user->name ?? '');

        $coordData = $coords->mapWithKeys(function ($coord) {
            if ($coord->user) {
                return [
                    $coord->id => $coord->user->name . ' (' . $coord->user->username . ')',
                ];
            }
            return [];
        });

        return $coordData->toArray();
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

    public static function getCoordByUserId($user_id = null)
    {
        $userId = $user_id ? $user_id : Auth::id();
        
        return self::with('user')->where('user_id', $userId)->first();
    }

    
}
