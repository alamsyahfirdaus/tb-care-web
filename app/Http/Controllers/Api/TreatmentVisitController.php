<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientTreatment;
use App\Models\TreatmentVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TreatmentVisitController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        // Kader and Patient strictly forbidden from creating or updating visit schedules
        if ($user && ($user->isKader() || $user->isPatient())) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk mengatur jadwal kontrol.'
            ], 403);
        }

        // Validasi input dari form request
        $validator = Validator::make($request->all(), [
            'id'                   => 'nullable|exists:treatment_visits,id',
            'patient_treatment_id' => 'required|exists:patient_treatments,id',
            'visit_date'           => 'required|date|after_or_equal:today',
            'visit_time'           => 'nullable|date_format:H:i',
            'visit_status'         => 'nullable|in:Terjadwal,Selesai,Hadir,Tidak Hadir,Dibatalkan',
            'notes'                => 'nullable|string',
        ], [
            'id.exists'                     => 'Data jadwal kontrol tidak ditemukan.',
            'patient_treatment_id.required' => 'ID pengobatan pasien wajib diisi.',
            'patient_treatment_id.exists'   => 'Data pengobatan tidak ditemukan.',
            'visit_date.required'           => 'Tanggal kontrol wajib diisi.',
            'visit_date.after_or_equal'     => 'Tanggal kontrol tidak boleh sebelum hari ini.',
            'visit_date.date'               => 'Tanggal kontrol harus berupa format tanggal yang valid.',
            'visit_time.date_format'        => 'Format waktu kontrol harus dalam format HH:ii (contoh: 08:30).',
            'visit_status.in'               => 'Status jadwal kontrol harus salah satu dari: Terjadwal, Selesai, Hadir, Tidak Hadir, atau Dibatalkan.',
            'notes.string'                  => 'Catatan harus berupa teks.',
        ]);

        // Validasi tambahan: jika sedang update, maka visit_status wajib diisi
        if ($request->filled('id') && !$request->filled('visit_status')) {
            $validator->after(function ($validator) {
                $validator->errors()->add('visit_status', 'Status jadwal kontrol wajib diisi.');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Validasi akses jika sedang melakukan update
        if ($request->filled('id')) {
            $existingVisit = TreatmentVisit::with('patientTreatment.patient')->find($request->id);
            if (!$existingVisit) {
                return response()->json(['message' => 'Data jadwal kontrol tidak ditemukan.'], 404);
            }
            if (!$existingVisit->isAccessibleBy($user)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki wewenang mengubah data jadwal kontrol ini.'
                ], 403);
            }
            $visit = $existingVisit;
        } else {
            $visit = new TreatmentVisit();
        }

        // Proteksi Otorisasi Wilayah Pasien
        $treatment = PatientTreatment::with('patient')->find($request->patient_treatment_id);
        if (!$treatment || !$treatment->patient) {
            return response()->json(['message' => 'Data pengobatan tidak ditemukan.'], 404);
        }

        if (!$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mencatat jadwal kontrol untuk pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        $visit->patient_treatment_id = $request->patient_treatment_id;
        $visit->visit_date           = $request->visit_date;
        $visit->visit_time           = $request->visit_time;
        $visit->visit_status         = $request->visit_status ?? 'Terjadwal';
        $visit->notes                = $request->notes;

        $visit->save();

        return response()->json([
            'message' => $request->filled('id')
                ? 'Data jadwal kontrol berhasil diperbarui.'
                : 'Jadwal kontrol berhasil ditambahkan.',
            'data'    => $visit
        ], $request->filled('id') ? 200 : 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        // Kader and Patient strictly forbidden from deleting visit schedules
        if ($user && ($user->isKader() || $user->isPatient())) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk menghapus data jadwal kontrol.'
            ], 403);
        }

        $visit = TreatmentVisit::with('patientTreatment.patient')->find($id);

        if (!$visit) {
            return response()->json([
                'message' => 'Data jadwal kontrol tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        if (!$visit->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang menghapus data jadwal kontrol pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        $visit->delete();

        return response()->json([
            'message' => 'Jadwal kontrol berhasil dihapus.'
        ]);
    }

    public function cancelVisit(Request $request, $id)
    {
        $user = Auth::user();

        // Kader and Patient strictly forbidden from cancelling visit schedules
        if ($user && ($user->isKader() || $user->isPatient())) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk membatalkan jadwal kontrol.'
            ], 403);
        }

        $visit = TreatmentVisit::with('patientTreatment.patient')->find($id);

        if (!$visit) {
            return response()->json([
                'message' => 'Data jadwal kontrol tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        if (!$visit->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang membatalkan jadwal kontrol pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        $visit->visit_status = 'Dibatalkan';
        if ($request->filled('notes')) {
            $existingNotes = $visit->notes ? $visit->notes . ' | ' : '';
            $visit->notes = $existingNotes . 'Dibatalkan: ' . $request->input('notes');
        }
        $visit->save();

        return response()->json([
            'message' => 'Jadwal kontrol berhasil dibatalkan.',
            'data'    => $visit
        ]);
    }

    public function patientVisits(Request $request)
    {
        $user = Auth::user();

        $patient = null;
        if ($user->user_type_id == 2) {
            $patient = $user->patient ? \App\Models\Patient::with('puskesmas.subdistrict.district')->find($user->patient->id) : null;
        } elseif ($request->filled('patient_id')) {
            $patient = \App\Models\Patient::with('puskesmas.subdistrict.district')->find($request->patient_id);
            if ($patient && !$patient->isAccessibleBy($user)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki wewenang mengakses data pasien ini.'
                ], 403);
            }
        } else {
            $patient = $user->patient ? \App\Models\Patient::with('puskesmas.subdistrict.district')->find($user->patient->id) : null;
        }

        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.',
                'data'    => []
            ], 404);
        }

        $treatment = PatientTreatment::where('patient_id', $patient->id)
            ->orderByDesc('start_date')
            ->first();

        $puskesmas = $patient->puskesmas;
        $puskesmasName = optional($puskesmas)->name ?? 'Puskesmas';
        $puskesmasLocation = $puskesmas?->full_address ?? $puskesmasName;

        if (!$treatment) {
            return response()->json([
                'message'            => 'Belum ada data pengobatan.',
                'puskesmas_name'     => $puskesmasLocation,
                'puskesmas_location' => $puskesmasLocation,
                'data'               => []
            ], 200);
        }

        $visits = TreatmentVisit::where('patient_treatment_id', $treatment->id)
            ->orderBy('visit_date', 'asc')
            ->orderBy('visit_time', 'asc')
            ->get()
            ->map(function ($visit) use ($puskesmasLocation) {
                return [
                    'id'                   => $visit->id,
                    'patient_treatment_id' => $visit->patient_treatment_id,
                    'visit_date'           => $visit->visit_date ? \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d') : null,
                    'visit_time'           => $visit->visit_time,
                    'visit_status'         => $visit->visit_status,
                    'notes'                => $visit->notes,
                    'puskesmas_name'       => $puskesmasLocation,
                    'puskesmas_location'   => $puskesmasLocation,
                    'created_at'           => $visit->created_at,
                    'updated_at'           => $visit->updated_at,
                ];
            });

        return response()->json([
            'message'            => 'Daftar jadwal kontrol berhasil diambil.',
            'puskesmas_name'     => $puskesmasLocation,
            'puskesmas_location' => $puskesmasLocation,
            'data'               => $visits
        ]);
    }

    public function show($id)
    {
        $visit = TreatmentVisit::with(['patientTreatment.patient.puskesmas.subdistrict.district'])->find($id);

        if (!$visit) {
            return response()->json([
                'message' => 'Data jadwal kontrol tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        if (!$visit->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses data jadwal kontrol ini.'
            ], 403);
        }

        $patient = $visit->patientTreatment?->patient;
        $puskesmas = $patient?->puskesmas;
        $puskesmasName = optional($puskesmas)->name ?? 'Puskesmas';
        $puskesmasLocation = $puskesmas?->full_address ?? $puskesmasName;

        return response()->json([
            'message' => 'Detail jadwal kontrol berhasil diambil.',
            'data'    => [
                'id'                   => $visit->id,
                'patient_treatment_id' => $visit->patient_treatment_id,
                'visit_date'           => $visit->visit_date ? \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d') : null,
                'visit_time'           => $visit->visit_time,
                'visit_status'         => $visit->visit_status,
                'notes'                => $visit->notes,
                'puskesmas_name'       => $puskesmasLocation,
                'puskesmas_location'   => $puskesmasLocation,
                'created_at'           => $visit->created_at,
                'updated_at'           => $visit->updated_at,
            ]
        ]);
    }
}
