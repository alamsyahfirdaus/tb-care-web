<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subdistrict;
use Illuminate\Http\Request;

class SubdistrictController extends Controller
{
    public function index()
    {
        // Ambil data subdistrict beserta relasi ke district dan province
        $query = Subdistrict::with(['district.province']);

        // Eksekusi query untuk mengambil seluruh data
        $subdistricts = $query->get();

        // Format data agar nama subdistrict tampil lengkap dengan kabupaten/kota dan provinsi
        $formatted = $subdistricts->map(function ($item) {
            return [
                'id'   => $item->id,
                'name' => $item->name . ', ' . $item->district->name . ', ' . $item->district->province->name
            ];
        });

        // Kirim response JSON ke frontend
        return response()->json([
            'message' => 'Data kecamatan berhasil diambil.',
            'data'    => $formatted
        ]);
    }
}
