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
    protected $guarded = [];

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function villages()
    {
        return $this->hasMany(Village::class, 'subdistrict_id');
    }

    public function puskesmas()
    {
        return $this->hasMany(Puskesmas::class, 'subdistrict_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'subdistrict_id');
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class, 'subdistrict_id');
    }

    public static function getAllSubdistricts()
    {
        $districtId = $provinceId = null;

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->user_type_id == 3 && $user->officer) {
                $officer = $user->officer;
                if ($officer->district_id) {
                    if ($officer->officer_type_id == 2) {
                        $districtId = $officer->district_id;
                    } elseif ($officer->officer_type_id == 1) {
                        $dist = District::find($officer->district_id);
                        $provinceId = $dist ? $dist->province_id : null;
                    }
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
            $dName = $subdistrict->district ? $subdistrict->district->name : '-';
            $pName = ($subdistrict->district && $subdistrict->district->province) ? $subdistrict->district->province->name : '-';
            $area = 'Kec. ' . $subdistrict->name . ' - ' . $dName . ' - Prov. ' . $pName;

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
