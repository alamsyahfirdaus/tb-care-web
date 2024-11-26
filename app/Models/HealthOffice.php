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

    public static function getHealthOfficeWithUser()
    {
        $healthOffices = self::with('user')->get();

        $sortedHealthOffices = $healthOffices->sortBy(fn($ho) => $ho->user->name);

        return $sortedHealthOffices->reduce(function ($hoData, $ho) {
            if ($ho->user) {
                $hoData[$ho->id] = sprintf(
                    '%s (%s)',
                    $ho->user->name,
                    $ho->user->username
                );
            }
            return $hoData;
        }, []);
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

    public static function getHealthOfficeByUserId($user_id)
    {
        $healthOffice = self::where('user_id', $user_id)->first();

        if ($healthOffice) {
            return [
                'office_type'     => $healthOffice->office_type_id ? self::getOfficeTypes($healthOffice->office_type_id) : null,
                'office_address'  => $healthOffice->office_address,
                'office_district' => $healthOffice->district ? District::getDistrictById($healthOffice->district_id)['province'] : null,
            ];
        }

        return null;
    }
}
