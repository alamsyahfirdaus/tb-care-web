<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\HasEncryptedId;

class Subdistrict extends Model
{
    use HasFactory, HasEncryptedId;

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

    public function scopeAccessibleBy($query, ?User $user = null)
    {
        $user = $user ?? Auth::user();
        if (!$user || $user->user_type_id == 1 || $user->user_type_id == 2) {
            return $query;
        }

        if ($user->user_type_id == 3 && $user->officer) {
            $officer = $user->officer;
            if (in_array($officer->officer_type_id, [3, 4]) && $officer->puskesmas_id) {
                $pusk = Puskesmas::find($officer->puskesmas_id);
                if ($pusk && $pusk->subdistrict_id) {
                    return $query->where('id', $pusk->subdistrict_id);
                }
            }

            if ($officer->officer_type_id == 2 && $officer->district_id) {
                return $query->where('district_id', $officer->district_id);
            }

            if ($officer->officer_type_id == 1 && $officer->district_id) {
                $dist = District::find($officer->district_id);
                $provId = $dist ? $dist->province_id : null;
                if ($provId) {
                    return $query->whereHas('district', function ($q) use ($provId) {
                        $q->where('province_id', $provId);
                    });
                }
            }
        }

        return $query;
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
