<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use App\Models\Subdistrict;
use App\Models\Puskesmas;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PatientController extends Controller
{
    public function index() 
    {
        //
    }

    public function edit($id)
    {
        $patient = Patient::with('user.userType')->find(base64_decode($id));

        if (!$patient) {
            return redirect()->back();
        }

        $data = [
            'title' => $patient->user->userType->name,
            'data' => $patient,
            'patients' => Patient::getPatientWithUser(),
            'subdistricts' => Subdistrict::getAllSubdistricts(),
            'puskesmas' => Puskesmas::getAllPuskesmas(),
        ];

        return view('patient-edit', $data);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $id = base64_decode($id);
    
        $fieldData = [
            'nik' => [
                'required',
                'numeric',
                'digits_between:1,16',
                'unique:patients,nik,' . $id,
            ],
            'address' => 'required|string|max:255',
            'subdistrict_id' => 'required|exists:subdistricts,id',
            'occupation' => 'nullable|string',
            'height' => 'nullable|numeric|digits_between:1,3',
            'weight' => 'nullable|numeric|digits_between:1,3',
            'blood_type' => 'nullable|in:A,B,AB,O',
            'diagnosis_date' => 'required|date_format:d/m/Y|before_or_equal:today',
            'puskesmas_id' => 'required|exists:puskesmas,id',
        ];
    
        $validatedData = $request->validate($fieldData);
    
        if (isset($validatedData['diagnosis_date'])) {
            $validatedData['diagnosis_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validatedData['diagnosis_date'])->format('Y-m-d');
        }
    
        $patient = Patient::find($id);
    
        if (!$patient) {
            return response()->json([
                'status' => false,
                'message' => 'Pasien tidak ditemukan.',
            ], 404);
        }
    
        $updateData = array_filter($validatedData, function ($key) use ($fieldData) {
            return array_key_exists($key, $fieldData);
        }, ARRAY_FILTER_USE_KEY);
    
        $patient->update($updateData);
    
        return response()->json([
            'status' => true,
            'message' => 'Data pasien berhasil diperbarui.',
        ], 200);
    }
    
}
