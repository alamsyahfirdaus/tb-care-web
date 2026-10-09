<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Puskesmas;
use Illuminate\Http\Request;

class PuskesmasController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();
        $query = Puskesmas::with('subdistrict.district.province')
            ->select('id', 'name', 'address', 'subdistrict_id');

        if ($user && $user->user_type_id == 3 && $user->officer) {
            $query->accessibleBy($user);
        }

        $puskesmas = $query->orderBy('name', 'asc')->get();

        // Format data untuk response JSON
        $data = $puskesmas->map(function ($item) {
            $subdistrictName = optional($item->subdistrict)->name;
            $districtName    = optional(optional($item->subdistrict)->district)->name;
            $provinceName    = optional(optional(optional($item->subdistrict)->district)->province)->name;

            return [
                'id'   => $item->id,

                // Nama Puskesmas + (Kecamatan, Kabupaten/Kota)
                'name' => $subdistrictName
                    ? $item->name . ' (' . $subdistrictName . ', ' . $districtName . ')'
                    : $item->name,

                'raw_name'         => $item->name,
                'subdistrict_name' => $subdistrictName,
                'district_name'    => $districtName,
                'location'         => ($subdistrictName && $districtName)
                    ? ($subdistrictName . ' - ' . $districtName)
                    : ($subdistrictName ?: $districtName),

                'address' => $item->address,

                // Lokasi administratif lengkap
                'subdistrict' => implode(', ', array_filter([
                    $subdistrictName,
                    $districtName,
                    $provinceName
                ])) ?: null,
            ];
        });

        return response()->json([
            'message' => 'Daftar Puskesmas berhasil diambil.',
            'data'    => $data
        ]);
    }
}
