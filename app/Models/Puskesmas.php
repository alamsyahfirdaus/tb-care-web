<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Puskesmas extends Model
{
    use HasFactory;

    protected $table = 'puskesmas';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded = [];

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public function officers()
    {
        return $this->hasMany(Officer::class, 'puskesmas_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'puskesmas_id');
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class, 'puskesmas_id');
    }

    public static function getAllPuskesmas()
    {
        $puskesmasId = $districtId = $provinceId = null;

        if (Auth::check()) {
            $user = Auth::user();
            // Role 3 is Petugas (Officer)
            if ($user->user_type_id == 3 && $user->officer) {
                $officer = $user->officer;
                if (in_array($officer->officer_type_id, [3, 4]) && $officer->puskesmas_id) {
                    $puskesmasId = $officer->puskesmas_id;
                } elseif ($officer->officer_type_id == 2 && $officer->district_id) {
                    $districtId = $officer->district_id;
                } elseif ($officer->officer_type_id == 1 && $officer->district_id) {
                    $dist = District::find($officer->district_id);
                    $provinceId = $dist ? $dist->province_id : null;
                }
            }
        }

        $query = self::with('subdistrict.district.province');

        if ($puskesmasId) {
            $query->where('id', $puskesmasId);
        } elseif ($districtId) {
            $query->whereHas('subdistrict', function ($subQuery) use ($districtId) {
                $subQuery->where('district_id', $districtId);
            });
        } elseif ($provinceId) {
            $query->whereHas('subdistrict.district', function ($subQuery) use ($provinceId) {
                $subQuery->where('province_id', $provinceId);
            });
        }

        return $query->orderBy('name', 'asc')
            ->get()
            ->map(function ($puskesmas) {
                $subdistrict = $puskesmas->subdistrict;
                $district = $subdistrict ? $subdistrict->district : null;
                $province = $district ? $district->province : null;

                $area = $subdistrict ? 'Kec. ' . $subdistrict->name : '';
                if ($district) {
                    $area .= ' - ' . $district->name;
                    if ($province) {
                        $area .= ' - Prov. ' . $province->name;
                    }
                }

                return [
                    'id'             => $puskesmas->id,
                    'code'           => $puskesmas->code,
                    'name'           => $puskesmas->name,
                    'address'        => $puskesmas->address,
                    'subdistrict_id' => $puskesmas->subdistrict_id,
                    'area'           => $area,
                    'puskesmas'      => $puskesmas->name . ($area ? ' (' . $area . ')' : '')
                ];
            })
            ->toArray();
    }

    public static function getPuskesmasById($id)
    {
        $puskesmas = self::getAllPuskesmas();
        return collect($puskesmas)->firstWhere('id', $id);
    }
}
