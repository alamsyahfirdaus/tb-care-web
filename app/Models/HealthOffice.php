<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthOffice extends Model
{
    use HasFactory;

    protected $table = 'health_offices';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public static function getHoWithUser()
    {
        $healthOffices = self::with('user')->get();
        
        $healthOffices = $healthOffices->sortBy(function ($ho) {
            return $ho->user->name ?? '';
        });
        
        $hoData = [];
        
        foreach ($healthOffices as $ho) {
            if ($ho->user) {
                $hoData[$ho->id] = $ho->user->name . ' (' . $ho->user->username . ') - ' . $ho->user->email;
            }
        }
        
        return $hoData;
    }

    public static function getOfficeTypes($id = null)
    {
        $officeTypes = [
            '1' => 'Provinsi',
            '2' => 'Kabupaten/Kota',
        ];
    
        if ($id !== null && isset($officeTypes[$id])) {
            return $officeTypes[$id];
        }
        
        return $officeTypes;
    }
    
}
