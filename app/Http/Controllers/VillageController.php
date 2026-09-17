<?php

namespace App\Http\Controllers;

use App\Models\Subdistrict;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VillageController extends Controller
{
    public function index()
    {
        $data = [
            'title'        => 'Desa/Kelurahan',
            'villages'     => Village::with('subdistrict.district')->orderBy('id', 'desc')->get(),
            'subdistricts' => Subdistrict::getAllSubdistricts(),
        ];

        return view('village-index', $data);
    }

    public function edit($id)
    {
        $id = base64_decode($id);
        $village = Village::with('subdistrict')->find($id);

        if (!$village) {
            return redirect()->route('village');
        }

        $data = [
            'title'        => 'Desa/Kelurahan',
            'data'         => $village,
            'villages'     => Village::with('subdistrict.district')->orderBy('id', 'desc')->get(),
            'subdistricts' => Subdistrict::getAllSubdistricts(),
        ];

        return view('village-index', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $id = $id ? base64_decode($id) : null;
        $village = $id ? Village::find($id) : new Village();

        if (!$village) {
            $village = new Village();
        }

        $validatedData = $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('villages', 'code')->ignore($village->id),
            ],
            'name'           => ['required', 'string', 'max:255'],
            'subdistrict_id' => ['required', 'exists:subdistricts,id'],
        ], [
            'name.required'           => 'Nama Desa/Kelurahan wajib diisi.',
            'subdistrict_id.required' => 'Kecamatan wajib dipilih.',
            'subdistrict_id.exists'   => 'Kecamatan tidak valid.',
        ]);

        $village->code           = $validatedData['code'];
        $village->name           = $validatedData['name'];
        $village->subdistrict_id = $validatedData['subdistrict_id'];
        $village->save();

        return response()->json([
            'status'  => true,
            'message' => 'Desa/Kelurahan berhasil disimpan.',
        ]);
    }

    public function destroy($id)
    {
        $village = Village::find(base64_decode($id));

        if ($village) {
            $village->delete();
        }

        return redirect()->route('village')->with('success', 'Desa/Kelurahan berhasil dihapus.');
    }
}
