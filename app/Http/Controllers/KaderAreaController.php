<?php

namespace App\Http\Controllers;

use App\Models\KaderArea;
use App\Models\Officer;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KaderAreaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'officer_id'     => 'required|exists:officers,id',
            'subdistrict_id' => 'required|exists:subdistricts,id',
            'village_id'     => 'required|exists:villages,id',
            'rw'             => 'required|string|max:5',
            'rt'             => 'nullable|string|max:5',
        ], [
            'officer_id.required'     => 'Petugas Kader wajib valid.',
            'subdistrict_id.required' => 'Kecamatan wajib dipilih.',
            'village_id.required'     => 'Desa/Kelurahan wajib dipilih.',
            'rw.required'             => 'RW wajib diisi.',
            'rw.max'                  => 'RW maksimal 5 karakter.',
            'rt.max'                  => 'RT maksimal 5 karakter.',
        ]);

        $officer = Officer::find($validated['officer_id']);
        if (!$officer) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Petugas Kader tidak ditemukan.'], 404);
            }
            return redirect()->back()->with('error', 'Petugas Kader tidak ditemukan.');
        }

        $currentUser = auth()->user();
        $canManage = false;
        if ($currentUser && $currentUser->user_type_id == 1) {
            $canManage = true;
        } elseif ($currentUser && $currentUser->user_type_id == 3) {
            $pjtb = Officer::where('user_id', $currentUser->id)->first();
            if ($pjtb && $pjtb->officer_type_id == 3 && $pjtb->puskesmas_id == $officer->puskesmas_id) {
                $canManage = true;
            }
        }

        if (!$canManage) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Anda tidak memiliki hak untuk mengelola wilayah binaan Kader ini.'], 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk mengelola wilayah binaan Kader ini.');
        }

        $rt = !empty($validated['rt']) ? trim($validated['rt']) : null;
        $rw = trim($validated['rw']);

        // Cek duplikasi wilayah yang identik
        $exists = KaderArea::where('officer_id', $validated['officer_id'])
            ->where('village_id', $validated['village_id'])
            ->where('rw', $rw)
            ->where(function ($q) use ($rt) {
                if (is_null($rt)) {
                    $q->whereNull('rt');
                } else {
                    $q->where('rt', $rt);
                }
            })
            ->exists();

        if ($exists) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Wilayah binaan ini sudah terdaftar untuk Kader tersebut.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Wilayah binaan ini sudah terdaftar untuk Kader tersebut.');
        }

        KaderArea::create([
            'officer_id'     => $validated['officer_id'],
            'subdistrict_id' => $validated['subdistrict_id'],
            'village_id'     => $validated['village_id'],
            'rw'             => $rw,
            'rt'             => $rt,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => true,
                'message' => 'Wilayah binaan berhasil ditambahkan.',
            ]);
        }

        return redirect()->back()->with('success', 'Wilayah binaan berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $areaId = is_numeric($id) ? $id : base64_decode($id);
        $area = KaderArea::with('officer')->find($areaId);

        if (!$area) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Wilayah binaan tidak ditemukan.'], 404);
            }
            return redirect()->back()->with('error', 'Wilayah binaan tidak ditemukan.');
        }

        $currentUser = auth()->user();
        $canManage = false;
        if ($currentUser && $currentUser->user_type_id == 1) {
            $canManage = true;
        } elseif ($currentUser && $currentUser->user_type_id == 3) {
            $pjtb = Officer::where('user_id', $currentUser->id)->first();
            if ($pjtb && $pjtb->officer_type_id == 3 && $pjtb->puskesmas_id == $area->officer->puskesmas_id) {
                $canManage = true;
            }
        }

        if (!$canManage) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Anda tidak memiliki hak untuk menghapus wilayah binaan ini.'], 403);
            }
            return redirect()->back()->with('error', 'Anda tidak memiliki hak untuk menghapus wilayah binaan ini.');
        }

        $area->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'status'  => true,
                'message' => 'Wilayah binaan berhasil dihapus.',
            ]);
        }

        return redirect()->back()->with('success', 'Wilayah binaan berhasil dihapus.');
    }

    public function getVillages($subdistrictId): JsonResponse
    {
        $villages = Village::where('subdistrict_id', $subdistrictId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'code']);

        return response()->json($villages);
    }
}
