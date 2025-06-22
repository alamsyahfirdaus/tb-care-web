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
            // Ambil nama subdistrict, district, dan province jika tersedia, jika tidak akan bernilai null
            $subdistrictName = optional($item->subdistrict)->name;
            $districtName    = optional(optional($item->subdistrict)->district)->name;
            $provinceName    = optional(optional(optional($item->subdistrict)->district)->province)->name;

            return [
                'id'        => $item->id,
                'name'      => $item->name,
                'address'   => $item->address,

                // Gabungkan lokasi administratif menjadi satu string, pisahkan dengan koma
                // Hanya nilai yang tidak null yang akan digabung (misalnya jika district null, tidak ikut tampil)
                'subdistrict' => implode(', ', array_filter([
                    $subdistrictName,
                    $districtName,
                    $provinceName
                ])) ?: null,
            ];
        });

        // Kembalikan response dalam format JSON
        return response()->json([
            'message' => 'Daftar Puskesmas berhasil diambil.',
            'data'    => $data
        ]);
    }
}
