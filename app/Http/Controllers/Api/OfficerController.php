<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KaderArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficerController extends Controller
{
    public function getKaderAreas(Request $request)
    {
        $user = Auth::user();

        if ($user->user_type_id != 3 || !$user->officer) {
            return response()->json([
                'message' => 'Hanya petugas yang memiliki wilayah binaan.',
                'data'    => []
            ], 403);
        }

        $areas = KaderArea::with(['village', 'subdistrict'])
            ->where('officer_id', $user->officer->id)
            ->get()
            ->map(function ($area) {
                return [
                    'id'               => $area->id,
                    'subdistrict_id'   => $area->subdistrict_id,
                    'subdistrict_name' => optional($area->subdistrict)->name,
                    'village_id'       => $area->village_id,
                    'village_name'     => optional($area->village)->name,
                    'rw'               => $area->rw,
                    'rt'               => $area->rt,
                ];
            });

        return response()->json([
            'message' => 'Daftar wilayah binaan kader berhasil diambil.',
            'data'    => $areas
        ]);
    }
}
