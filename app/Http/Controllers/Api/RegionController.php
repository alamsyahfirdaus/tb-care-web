<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\Village;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Ambil daftar seluruh provinsi.
     */
    public function provinces()
    {
        $provinces = Province::orderBy('name', 'asc')
            ->get(['id', 'code', 'name']);

        return response()->json([
            'success' => true,
            'message' => 'Daftar provinsi berhasil diambil.',
            'data'    => $provinces
        ]);
    }

    /**
     * Ambil daftar kabupaten/kota berdasarkan provinsi (parameter query atau route).
     */
    public function districts(Request $request)
    {
        $provinceId = $request->query('province_id');

        $query = District::query();

        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }

        $districts = $query->orderBy('name', 'asc')
            ->get(['id', 'code', 'name', 'province_id']);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kabupaten/kota berhasil diambil.',
            'data'    => $districts
        ]);
    }

    /**
     * Ambil daftar kabupaten/kota berdasarkan ID provinsi (route param).
     */
    public function districtsByProvince($provinceId)
    {
        $districts = District::where('province_id', $provinceId)
            ->orderBy('name', 'asc')
            ->get(['id', 'code', 'name', 'province_id']);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kabupaten/kota berhasil diambil.',
            'data'    => $districts
        ]);
    }

    /**
     * Ambil daftar kecamatan berdasarkan ID kabupaten/kota (route param).
     */
    public function subdistrictsByDistrict($districtId)
    {
        $subdistricts = Subdistrict::where('district_id', $districtId)
            ->orderBy('name', 'asc')
            ->get(['id', 'code', 'name', 'district_id']);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kecamatan berhasil diambil.',
            'data'    => $subdistricts
        ]);
    }

    /**
     * Ambil daftar desa/kelurahan (bisa difilter subdistrict_id via query).
     */
    public function villages(Request $request)
    {
        $subdistrictId = $request->query('subdistrict_id');

        $query = Village::query();

        if ($subdistrictId) {
            $query->where('subdistrict_id', $subdistrictId);
        }

        $villages = $query->orderBy('name', 'asc')
            ->get(['id', 'code', 'name', 'subdistrict_id']);

        return response()->json([
            'success' => true,
            'message' => 'Daftar desa/kelurahan berhasil diambil.',
            'data'    => $villages
        ]);
    }
}
