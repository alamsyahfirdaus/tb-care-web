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
     * Ambil daftar desa/kelurahan (bisa difilter subdistrict_id via query, atau dicari via search/q).
     */
    public function villages(Request $request)
    {
        $subdistrictId = $request->query('subdistrict_id');
        $search = $request->query('search') ?? $request->query('q');

        $query = Village::with(['subdistrict.district.province']);

        if ($subdistrictId) {
            $query->where('subdistrict_id', $subdistrictId);
        }

        if (!empty($search)) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('subdistrict', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $villages = $query->orderBy('name', 'asc')->get();

        $formatted = $villages->map(function ($village) {
            $sub = $village->subdistrict;
            $dist = $sub ? $sub->district : null;
            $prov = $dist ? $dist->province : null;

            $subName = $sub ? $sub->name : '';
            $distName = $dist ? $dist->name : '';
            $provName = $prov ? $prov->name : '';

            $parentParts = array_filter([$subName, $distName]);
            $parentDisplay = implode(' • ', $parentParts);

            return [
                'id'             => $village->id,
                'code'           => $village->code,
                'name'           => $village->name,
                'subdistrict_id' => $sub ? $sub->id : null,
                'district_id'    => $dist ? $dist->id : null,
                'province_id'    => $prov ? $prov->id : null,
                'subdistrict'    => $sub ? [
                    'id'   => $sub->id,
                    'name' => $sub->name,
                ] : null,
                'district'       => $dist ? [
                    'id'   => $dist->id,
                    'name' => $dist->name,
                ] : null,
                'regency'        => $dist ? [
                    'id'   => $dist->id,
                    'name' => $dist->name,
                ] : null,
                'province'       => $prov ? [
                    'id'   => $prov->id,
                    'name' => $prov->name,
                ] : null,
                'parent_display' => $parentDisplay,
                'full_address'   => implode(', ', array_filter([$village->name, $subName ? "Kec. $subName" : '', $distName, $provName])),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar desa/kelurahan berhasil diambil.',
            'data'    => $formatted
        ]);
    }

    /**
     * Pencarian desa/kelurahan beserta relasi wilayah parent.
     */
    public function searchVillages(Request $request)
    {
        return $this->villages($request);
    }
}
