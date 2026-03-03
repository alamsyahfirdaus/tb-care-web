<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Puskesmas;
use Illuminate\Http\Request;

class PuskesmasController extends Controller
{
    public function index()
    {
        // Ambil semua data Puskesmas beserta relasi ke subdistrict, district, dan province
        $puskesmas = Puskesmas::with('subdistrict.district.province')
            ->select('id', 'name', 'address', 'subdistrict_id')
            ->get();

        // Format data untuk response JSON
        $data = $puskesmas->map(function ($item) {
            $subdistrictName = optional($item->subdistrict)->name;
            $districtName    = optional(optional($item->subdistrict)->district)->name;
            $provinceName    = optional(optional(optional($item->subdistrict)->district)->province)->name;

            return [
                'id'      => $item->id,

                // Nama Puskesmas + (Subdistrict)
                'name'    => $subdistrictName
                    ? $item->name . ' (' . $subdistrictName . ', ' . $districtName . ')'
                    : $item->name,

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
