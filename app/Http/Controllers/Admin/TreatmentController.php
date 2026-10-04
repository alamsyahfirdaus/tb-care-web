<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PatientTreatment;
use App\Models\MedicationRecord;
use App\Models\TreatmentVisit;
use App\Models\TreatmentType;
use App\Models\Patient;
use App\Models\Puskesmas;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TreatmentController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'Berjalan');
        $regimenFilter = $request->query('regimen');
        $puskesmasFilter = $request->query('puskesmas_id');
        $keyword = $request->query('q');

        $query = PatientTreatment::with([
            'patient.user',
            'patient.puskesmas',
            'treatmentType',
            'medicationRecords',
            'visits'
        ]);

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('treatment_status', $statusFilter);
        }

        if ($regimenFilter) {
            $query->where('treatment_type_id', $regimenFilter);
        }

        if ($puskesmasFilter) {
            $query->whereHas('patient', function($q) use ($puskesmasFilter) {
                $q->where('puskesmas_id', $puskesmasFilter);
            });
        }

        if ($keyword) {
            $query->whereHas('patient.user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            })->orWhereHas('patient', function ($q) use ($keyword) {
                $q->where('nik', 'like', "%{$keyword}%");
            });
        }

        $treatments = $query->orderByDesc('id')->get();
        $treatmentTypes = TreatmentType::all();
        $puskesmasList = Puskesmas::orderBy('name')->get();

        $pageTitle = 'Pasien Dalam Pengobatan';
        if ($statusFilter == 'Selesai') $pageTitle = 'Hasil Pengobatan TB (Selesai / Sembuh)';
        elseif ($statusFilter == 'all') $pageTitle = 'Semua Riwayat Pengobatan TB';

        return view('admin.treatments.index', compact(
            'treatments',
            'treatmentTypes',
            'puskesmasList',
            'statusFilter',
            'regimenFilter',
            'puskesmasFilter',
            'keyword',
            'pageTitle'
        ))->with([
            'title'        => $pageTitle,
            'pageSubtitle' => 'Pengawasan terapi obat anti tuberkulosis (OAT), jadwal minum obat harian, dan evaluasi hasil pengobatan.'
        ]);
    }

    public function monitoring(Request $request)
    {
        $dateFilter = $request->query('date', Carbon::today()->format('Y-m-d'));
        $verifiedFilter = $request->query('verified');
        $puskesmasFilter = $request->query('puskesmas_id');

        $query = MedicationRecord::with([
            'patientTreatment.patient.user',
            'patientTreatment.patient.puskesmas',
            'patientTreatment.treatmentType'
        ]);

        if ($dateFilter) {
            $query->whereDate('created_at', $dateFilter);
        }

        if ($verifiedFilter !== null && $verifiedFilter !== '') {
            $query->where('is_verified', $verifiedFilter);
        }

        if ($puskesmasFilter) {
            $query->whereHas('patientTreatment.patient', function($q) use ($puskesmasFilter) {
                $q->where('puskesmas_id', $puskesmasFilter);
            });
        }

        $records = $query->orderByDesc('created_at')->get();
        $puskesmasList = Puskesmas::orderBy('name')->get();

        // Daily statistics
        $totalHariIni = MedicationRecord::whereDate('created_at', $dateFilter)->count();
        $verifiedHariIni = MedicationRecord::whereDate('created_at', $dateFilter)->where('is_verified', 1)->count();
        $tepatWaktuHariIni = MedicationRecord::whereDate('created_at', $dateFilter)->where('late', 0)->count();

        return view('admin.treatments.monitoring', compact(
            'records',
            'puskesmasList',
            'dateFilter',
            'verifiedFilter',
            'puskesmasFilter',
            'totalHariIni',
            'verifiedHariIni',
            'tepatWaktuHariIni'
        ))->with([
            'title'        => 'Monitoring Pengobatan Harian',
            'pageTitle'    => 'Monitoring Kepatuhan Minum Obat',
            'pageSubtitle' => 'Verifikasi bukti foto minum obat harian dan pemantauan kepatuhan minum obat pasien.'
        ]);
    }

    public function verifyMedication($id)
    {
        $id = decrypt_id($id);
        $record = MedicationRecord::findOrFail($id);
        $record->update([
            'is_verified' => 1
        ]);

        ActivityLog::log('Verifikasi Minum Obat', 'Pengobatan', "Memverifikasi bukti foto minum obat.");

        return redirect()->back()->with('success', 'Bukti minum obat berhasil diverifikasi.');
    }

    public function updateStatus(Request $request, $id)
    {
        $id = decrypt_id($id);
        $request->validate([
            'treatment_status' => 'required|in:Berjalan,Selesai,Gagal,Meninggal',
            'prescription'     => 'nullable|string',
        ]);

        $treatment = PatientTreatment::with('patient.user')->findOrFail($id);
        $treatment->update([
            'treatment_status' => $request->treatment_status,
            'prescription'     => $request->prescription ?? $treatment->prescription,
        ]);

        $patientName = optional($treatment->patient->user)->name ?? 'Pasien';
        ActivityLog::log('Update Status Pengobatan', 'Pengobatan', "Mengubah status pengobatan {$patientName} menjadi {$request->treatment_status}.");

        return redirect()->back()->with('success', "Status pengobatan berhasil diperbarui menjadi {$request->treatment_status}.");
    }

    public function storeVisit(Request $request)
    {
        $request->validate([
            'patient_treatment_id' => 'required|exists:patient_treatments,id',
            'visit_date'           => 'required|date',
            'visit_time'           => 'nullable',
            'visit_status'         => 'required|in:Terjadwal,Hadir,Tidak Hadir',
            'notes'                => 'nullable|string',
        ]);

        TreatmentVisit::create($request->all());

        return redirect()->back()->with('success', 'Jadwal kunjungan kontrol berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $treatment = PatientTreatment::with('patient.user')->findOrFail($id);
        $patientName = optional($treatment->patient->user)->name ?? 'Pasien';

        \Illuminate\Support\Facades\DB::transaction(function () use ($treatment) {
            $treatment->medicationRecords()->delete();
            $treatment->visits()->delete();
            $treatment->delete();
        });

        ActivityLog::log('Hapus Pengobatan', 'Pengobatan', "Menghapus data riwayat pengobatan untuk {$patientName}.");

        return redirect()->route('admin.treatments.index')->with('success', "Data pengobatan {$patientName} berhasil dihapus.");
    }
}
