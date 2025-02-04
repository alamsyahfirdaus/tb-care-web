<?php

namespace App\Http\Controllers;

use App\Helpers\DateHelper;
use App\Models\MedicationRecord;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\TreatmentType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PatientTreatmentController extends Controller
{
    public function index()
    {
        $data['title'] = 'Pengobatan';

        if (session('role') == 4) {
            $patientId = Patient::where('user_id', Auth::id())->value('id');

            $patientTreatments = collect();

            $treatments = PatientTreatment::getTreatmentByPatientId($patientId);

            foreach ($treatments as $treatment) {
                $dateRanges = PatientTreatment::getTreatmentDateRange($treatment['id']);

                foreach ($dateRanges as $date) {
                    $medicationRecord = MedicationRecord::getRecordByDate($treatment['id'], $date);

                    $hour = $medicationRecord && $medicationRecord['taken_at']
                        ? Carbon::parse($medicationRecord['taken_at'])->format('H:i:s')
                        : null;

                    $photo = $medicationRecord && $medicationRecord['photo']
                        ? Storage::url($medicationRecord['photo'])
                        : null;

                    $patientTreatments->push([
                        'date'   => $date,
                        'hour'   => $hour,
                        'photo'  => $photo,
                        'status' => $medicationRecord ? true : false,
                    ]);
                }
            }

            $data['treatments'] = $patientTreatments;
        } else {

            $data['treatments'] = PatientTreatment::getPatientTreatments();
            $data['patients']   = Patient::getPatientWithUser();
            $data['trtypes']    = TreatmentType::getTreatmentTypes();
        }

        return view('patient-treatment-index', $data);
    }

    public function edit($id)
    {
        $treatment = PatientTreatment::getTreatmentById(base64_decode($id));

        if (!$treatment) {
            return redirect()->back();
        }

        $data = [
            'title'         => 'Pengobatan',
            'data'          => $treatment,
            'patients'      => Patient::getPatientWithUser(),
            'trtypes'       => TreatmentType::getTreatmentTypes(),
        ];

        return view('patient-treatment-index', $data);
    }

    public function show($id)
    {
        $treatment = PatientTreatment::getTreatmentById(base64_decode($id));

        if (!$treatment) {
            return redirect()->back();
        }

        $data = [
            'title'     => 'Pengobatan',
            'data'      => $treatment,
            'dateRange' => PatientTreatment::getTreatmentDateRange($treatment['id']),
        ];

        return view('patient-treatment-detail', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $treatment = PatientTreatment::find(base64_decode($id));

        if (!$treatment) {
            $treatment = new PatientTreatment();
        }

        $rules = [
            'patient_id' => ['required', 'exists:patients,id'],
            'treatment_type_id' => ['required', 'exists:treatment_types,id'],
            'diagnosis_date' => ['required', 'date_format:d/m/Y', 'before_or_equal:today'],
            'start_date' => ['required', 'date_format:d/m/Y'],
            'medication_time' => ['required', 'regex:/^(0?[1-9]|1[0-2]):[0-5][0-9]\s?(AM|PM)$/'],
            'prescription' => ['nullable', 'array'],
            'prescription.*' => ['nullable', 'string'],
        ];

        // $completedTreatment = MedicationRecord::countRecords($treatment->id);

        // $startDateRule = $completedTreatment > 0
        //     ? 'after_or_equal:' . DateHelper::convertDate($treatment->start_date)
        //     : 'after_or_equal:' . DateHelper::convertDate($request->input('diagnosis_date'));

        // $rules['start_date'] = ['required', 'date_format:d/m/Y', $startDateRule];

        $validatedData = $request->validate($rules);

        $treatment->patient_id = $validatedData['patient_id'];
        $treatment->treatment_type_id = $validatedData['treatment_type_id'];
        $treatment->diagnosis_date = \DateTime::createFromFormat('d/m/Y', $validatedData['diagnosis_date'])->format('Y-m-d');
        $treatment->start_date = \DateTime::createFromFormat('d/m/Y', $validatedData['start_date'])->format('Y-m-d');

        $endDate = $this->calculateEndDate($validatedData['treatment_type_id'], $treatment->start_date);
        $treatment->end_date = $endDate;

        $medicationTime12hr = $validatedData['medication_time'];
        $medicationTime24hr = \DateTime::createFromFormat('h:i A', $medicationTime12hr)->format('H:i');
        $treatment->medication_time = $medicationTime24hr;

        if (!empty($validatedData['prescription'])) {
            $filteredPrescription = array_filter($validatedData['prescription'], function ($item) {
                return !is_null($item) && trim($item) !== '';
            });

            $treatment->prescription = !empty($filteredPrescription) ? json_encode($filteredPrescription) : null;
        } else {
            $treatment->prescription = null;
        }

        $treatment->save();

        $data = array(
            'status'    => true,
            'message'   => 'Pengobatan Pasien berhasil disimpan.',
            'url'       => route('treatment.show', ['id' => base64_encode($treatment->id)])
        );

        return response()->json($data, 200);
    }

    private function calculateEndDate($treatment_type_id, $start_date)
    {
        $treatmentType = TreatmentType::find($treatment_type_id);

        if (!$treatmentType) {
            return null;
        }

        $durationUnits = [
            'minggu' => 'W',
            'bulan'  => 'M',
            'tahun'  => 'Y',
        ];

        $unit = $durationUnits[$treatmentType->duration_unit] ?? null;
        if (!$unit) {
            return null;
        }

        $startDate = \DateTime::createFromFormat('Y-m-d', $start_date);
        $startDate->add(new \DateInterval('P' . $treatmentType->treatment_duration . $unit));

        return $startDate->format('Y-m-d');
    }

    public function destroy($id): RedirectResponse
    {
        $treatment = PatientTreatment::find(base64_decode($id));

        if (!$treatment) {
            return redirect()->route('treatments')->with('error', 'Pengobatan Pasien tidak ditemukan.');
        }

        $treatment->delete();

        return redirect()->route('treatments')->with('success', 'Pengobatan Pasien berhasil dihapus.');
    }

    public function takeMedicine(Request $request)
    {
        $patientId = Patient::where('user_id', Auth::id())->value('id');

        $treatment = PatientTreatment::where('patient_id', $patientId)->orderBy('id', 'desc')->first();

        if (isset($treatment->id)) {

            if ($request->hasFile('photo')) {

                $photo = $request->file('photo');

                $validTypes = ['image/jpeg', 'image/png', 'image/gif'];

                if (in_array($photo->getMimeType(), $validTypes)) {

                    $path = $photo->store('photos', 'public');

                    $medicationRecord = MedicationRecord::getRecordByDate($treatment->id, today());

                    if ($medicationRecord) {

                        if ($medicationRecord->photo && file_exists(public_path('storage/' . $medicationRecord->photo))) {
                            unlink(public_path('storage/' . $medicationRecord->photo));
                        }

                        $medicationRecord->update([
                            'photo' => $path,
                            'taken_at' => now(),
                        ]);
                        $message = 'Foto berhasil diperbarui.';
                    } else {
                        MedicationRecord::create([
                            'patient_treatment_id' => $treatment->id,
                            'photo' => $path,
                            'taken_at' => now(),
                        ]);
                        $message = 'Foto berhasil diunggah.';
                    }

                    return redirect()->back()->with('success', $message);
                } else {
                    return redirect()->back()->with('error', 'Format foto tidak valid. Hanya JPEG, PNG, atau GIF yang diperbolehkan.');
                }
            } else {
                return redirect()->back()->with('error', 'Silakan unggah foto.');
            }
        } else {
            return redirect()->back()->with('error', 'Tidak ada catatan pengobatan yang valid ditemukan.');
        }
    }
}
