<?php

namespace App\Http\Controllers;

use App\Models\EducationalMaterial;
use App\Models\MedicationRecord;
use App\Models\Patient;
use App\Models\PatientTreatment;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $patientTreatment = $this->countTreatment();

        $data = array(
            'title'              => 'Beranda',
            'medication_record'  => $patientTreatment['medication_record'],
            'patient_treatments' => $patientTreatment['patient_treatments']
        );

        return view('home', $data);
    }

    public function dashboard()
    {
        $data = array(
            'title'     => 'Sistem Pengobatan Tuberkulosis',
            'materials' => EducationalMaterial::getAllMaterials(),
        );
        return view('dashboard', $data);
    }

    public function portal()
    {
        return view('portal');
    }

    private function countTreatment()
    {
        $patientId = Patient::where('user_id', Auth::id())->value('id');
        $treatment = PatientTreatment::where('patient_id', $patientId)
            ->orderBy('id', 'desc')
            ->first();

        $treatmentId = optional($treatment)->id;

        if (!$treatmentId) {
            return [
                'patient_treatments' => [
                    'Jumlah Hari'    => 0,
                    'Dosis Diminum'  => 0,
                    'Dosis Terlewat' => 0,
                    'Sisa Dosis'     => 0,
                ],
                'medication_record'  => [],
            ];
        }

        $treatmentDates = PatientTreatment::getTreatmentDateRange($treatmentId);
        $medicationRecordsToday = MedicationRecord::getRecordByDate($treatmentId, today());

        $allMedicationRecords = MedicationRecord::where('patient_treatment_id', $treatmentId)->count();

        $dosisTerlewat = collect($treatmentDates)->filter(fn($date) => $date < today())
            ->reject(fn($date) => MedicationRecord::getRecordByDate($treatmentId, $date))
            ->count();

        $totalHari = count($treatmentDates);
        $sisaDosis = max(0, $totalHari - $allMedicationRecords);

        return [
            'patient_treatments' => [
                'Hari Perawatan' => $totalHari,
                'Dosis Diminum'  => $allMedicationRecords,
                'Dosis Terlewat' => $dosisTerlewat,
                'Sisa Dosis'     => $sisaDosis,
            ],
            'medication_record'  => $medicationRecordsToday,
        ];
    }
}
