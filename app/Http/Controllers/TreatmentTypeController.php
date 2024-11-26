<?php

namespace App\Http\Controllers;

use App\Models\TreatmentType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class TreatmentTypeController extends Controller
{

    public function index()
    {
        $data = [
            'title'         => 'Jenis Pengobatan',
            'trtypes'       => TreatmentType::getTreatmentTypes(),
        ];

        return view('treatment-type-index', $data);
    }

    public function edit($id)
    {
        $trtypes = TreatmentType::find(base64_decode($id));

        $data = [
            'title'     => 'Jenis Pengobatan',
            'data'      => $trtypes,
            'trtypes'   => TreatmentType::all(),
        ];

        return view('treatment-type-index', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $trtypes = TreatmentType::find(base64_decode($id));
        if (!$trtypes) {
            $trtypes = new TreatmentType();
        }

        $validatedData = $request->validate([
            'treatment_type' => [
                'required',
                'string',
                Rule::unique('treatment_types', 'treatment_type')->ignore($trtypes->id)
            ],
            'treatment_duration' => ['required', 'numeric', 'digits_between:1,2'],
            'duration_unit' => ['required', 'in:minggu,bulan,tahun'],
            'description' => ['nullable', 'string'],
        ]);

        $trtypes->treatment_type = $validatedData['treatment_type'];
        $trtypes->treatment_duration = $validatedData['treatment_duration'];
        $trtypes->duration_unit = $validatedData['duration_unit'];
        $trtypes->description = $validatedData['description'];

        $trtypes->save();

        $data = array(
            'status' => true,
            'message' => 'Data Jenis Pengobatan berhasil disimpan.',
        );

        if (!$trtypes->wasRecentlyCreated) {
            $data['previous'] = true;
        }

        return response()->json($data, 200);
    }

    public function destroy($id): RedirectResponse
    {
        $trtypes = TreatmentType::find(base64_decode($id));

        if (!$trtypes) {
            return redirect()->route('trtypes')->with('error', 'Data Jenis Pengobatan tidak ditemukan.');
        }

        $trtypes->delete();

        return redirect()->route('trtypes')->with('success', 'Data Jenis Pengobatan berhasil dihapus.');
    }
}
