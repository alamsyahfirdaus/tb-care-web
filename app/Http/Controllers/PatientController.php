<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use App\Models\Subdistrict;
use App\Models\Puskesmas;
use App\Models\Village;
use App\Models\Officer;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class PatientController extends Controller
{
    public function index() 
    {
        $query = Patient::with(['user', 'puskesmas', 'village', 'subdistrict']);
        if (auth()->check()) {
            $query->accessibleBy(auth()->user());
        }

        $patients = $query->orderBy('id', 'desc')->get()->map(function ($patient) {
            return [
                'id'             => $patient->id,
                'user_id'        => $patient->user_id,
                'full_name'      => $patient->user->name ?? '-',
                'gender'         => $patient->user->gender ?? '-',
                'phone'          => $patient->user->phone ?? '-',
                'username'       => $patient->user->username ?? '-',
                'email'          => $patient->user->email ?? '-',
                'puskesmas_name' => $patient->puskesmas->name ?? '-',
                'village_name'   => $patient->village->name ?? '-',
                'rw'             => $patient->rw ?? '-',
                'rt'             => $patient->rt ?? '-',
            ];
        })->toArray();

        $data = [
            'title'       => 'Pasien',
            'patients'    => $patients
        ];

        return view('patient-index', $data);
    }

    public function edit($id)
    {
        $patient = Patient::with(['user.userType', 'village'])->find(base64_decode($id));

        if (!$patient) {
            return redirect()->back();
        }

        if (auth()->check() && !$patient->isAccessibleBy(auth()->user())) {
            abort(403, 'Anda tidak memiliki akses ke pasien ini.');
        }

        $villages = $patient->subdistrict_id 
            ? Village::where('subdistrict_id', $patient->subdistrict_id)->orderBy('name', 'asc')->get() 
            : collect();

        // Patients list for the selector dropdown
        $scopedPatients = Patient::with('user');
        if (auth()->check()) {
            $scopedPatients->accessibleBy(auth()->user());
        }
        $patientsList = $scopedPatients->get()->sortBy(fn($p) => $p->user->name ?? '')->mapWithKeys(function ($p) {
            if ($p->user) {
                return [$p->id => $p->user->name . ' (' . $p->user->username . ')'];
            }
            return [];
        })->toArray();

        $data = [
            'title'         => $patient->user->userType->name ?? 'Pasien',
            'data'          => $patient,
            'patients'      => $patientsList,
            'subdistricts'  => Subdistrict::getAllSubdistricts(),
            'villages'      => $villages,
            'puskesmas'     => Puskesmas::getAllPuskesmas(),
        ];

        return view('patient-edit', $data);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $id = base64_decode($id);
        $patient = Patient::find($id);

        if (!$patient) {
            return response()->json([
                'status' => false,
                'message' => 'Pasien tidak ditemukan.',
            ], 404);
        }

        if (auth()->check() && !$patient->isAccessibleBy(auth()->user())) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki akses ke data pasien ini.',
            ], 403);
        }

        $fieldData = [
            'nik' => [
                'required',
                'numeric',
                'digits_between:1,16',
                'unique:patients,nik,' . $id,
            ],
            'address' => 'required|string|max:255',
            'subdistrict_id' => 'required|exists:subdistricts,id',
            'village_id' => 'nullable|exists:villages,id',
            'rw' => 'nullable|string|max:5',
            'rt' => 'nullable|string|max:5',
            'occupation' => 'nullable|string',
            'height' => 'nullable|numeric|digits_between:1,3',
            'weight' => 'nullable|numeric|digits_between:1,3',
            'blood_type' => 'nullable|in:A,B,AB,O',
            // 'diagnosis_date' => 'required|date_format:d/m/Y|before_or_equal:today',
            'treatment_start_date' => 'nullable|date|before_or_equal:today',
            'puskesmas_id' => 'required|exists:puskesmas,id',
        ];

        $validatedData = $request->validate($fieldData);

        if (isset($validatedData['diagnosis_date'])) {
            $validatedData['diagnosis_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validatedData['diagnosis_date'])->format('Y-m-d');
        }

        if (!empty($validatedData['treatment_start_date'])) {
            if (str_contains($validatedData['treatment_start_date'], '/')) {
                $validatedData['treatment_start_date'] = \Carbon\Carbon::createFromFormat('d/m/Y', $validatedData['treatment_start_date'])->format('Y-m-d');
            }
        }

        // If current user is a Kader, verify territory restriction for update
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->user_type_id == 3) {
                $officer = Officer::where('user_id', $user->id)->first();
                if ($officer && $officer->isKader()) {
                    $validatedData['puskesmas_id'] = $officer->puskesmas_id;

                    $villageId = $validatedData['village_id'] ?? null;
                    $rw = $validatedData['rw'] ?? null;
                    $rt = !empty($validatedData['rt']) ? trim($validatedData['rt']) : null;

                    if (empty($villageId) || empty($rw)) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Desa/Kelurahan dan RW wajib diisi sesuai wilayah binaan Anda.',
                        ], 422);
                    }

                    $inArea = $officer->kaderAreas()
                        ->where('village_id', $villageId)
                        ->where('rw', $rw)
                        ->where(function ($q) use ($rt) {
                            $q->whereNull('rt');
                            if (!is_null($rt)) {
                                $q->orWhere('rt', $rt);
                            }
                        })
                        ->exists();

                    if (!$inArea) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Wilayah baru pasien berada di luar wilayah binaan Anda.',
                        ], 403);
                    }
                }
            }
        }

        $updateData = array_filter($validatedData, function ($key) use ($fieldData) {
            return array_key_exists($key, $fieldData);
        }, ARRAY_FILTER_USE_KEY);

        $patient->update($updateData);

        return response()->json([
            'status' => true,
            'message' => 'Data Pasien berhasil diperbarui.',
        ], 200);
    }
    
}
