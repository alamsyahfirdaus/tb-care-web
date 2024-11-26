<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Subdistrict extends Model
{
    use HasFactory;

    protected $table = 'subdistricts';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public static function getAllSubdistricts()
    {
        $districtId = $provinceId = null;

        if (session('role') == 2) {
            $healthOffice = HealthOffice::with('district.province')
                ->where('user_id', Auth::id())
                ->first();

            if ($healthOffice) {
                if ($healthOffice->office_type === 'Kabupaten/Kota') {
                    $districtId = $healthOffice->district_id;
                } elseif ($healthOffice->office_type === 'Provinsi') {
                    $provinceId = $healthOffice->district->province_id;
                }
            }
        }

        $query = self::with('district.province')->orderBy('name', 'asc');

        if ($districtId) {
            $query->where('district_id', $districtId);
        }

        if ($provinceId) {
            $query->whereHas('district', function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId);
            });
        }

        return $query->get()->mapWithKeys(function ($subdistrict) {
            $area = 'Kec. ' . $subdistrict->name . ' - ' . $subdistrict->district->name . ' - Prov. ' . $subdistrict->district->province->name;

            return [
                $subdistrict->id => $area,
            ];
        })->toArray();
    }


    public static function getSubdistrictById($id)
    {
        $subdistrict = self::with('district.province')->find($id);

        if (!$subdistrict) {
            return [
                'id' => null,
                'name' => '-',
            ];
        }

        $district = $subdistrict->district;
        $province = $district ? $district->province : null;

        $name = 'Kec. ' . $subdistrict->name;
        if ($district) {
            $name .= ' - ' . $district->name;
            if ($province) {
                $name .= ' - Prov. ' . $province->name;
            }
        }

        return [
            'id' => $subdistrict->id,
            'name' => $name,
        ];
    }
}
