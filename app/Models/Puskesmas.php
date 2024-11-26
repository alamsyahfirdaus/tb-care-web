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

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public static function getAllPuskesmas()
    {
        $puskesmasId = $districtId = $provinceId = null;

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
        } elseif (session('role') == 3) {
            $coord = Coordinator::getCoordByUserId();

            if ($coord) {
                $puskesmasId = $coord->puskesmas_id;
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

        return $query->orderBy('id', 'desc')
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
