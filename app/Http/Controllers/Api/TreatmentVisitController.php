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
        // Validasi input dari form request
        $validator = Validator::make($request->all(), [
            'id'                   => 'nullable|exists:treatment_visits,id',
            'patient_treatment_id' => 'required|exists:patient_treatments,id',
            'visit_date'           => 'required|date|after_or_equal:today',
            'visit_time'           => 'nullable|date_format:H:i',
            'visit_status'         => 'in:Terjadwal,Hadir,Tidak Hadir',
            'notes'                => 'nullable|string',
        ], [
            'id.exists'                     => 'Data kunjungan tidak ditemukan.',
            'patient_treatment_id.required' => 'ID pengobatan pasien wajib diisi.',
            'patient_treatment_id.exists'   => 'Data pengobatan tidak ditemukan.',
            'visit_date.required'           => 'Tanggal kunjungan wajib diisi.',
            'visit_date.after_or_equal'     => 'Tanggal kunjungan tidak boleh sebelum hari ini.',
            'visit_date.date'               => 'Tanggal kunjungan harus berupa format tanggal yang valid.',
            'visit_time.date_format'        => 'Format waktu kunjungan harus dalam format HH:ii (contoh: 08:30).',
            'visit_status.in'               => 'Status kunjungan harus salah satu dari: Terjadwal, Hadir, atau Tidak Hadir.',
            'notes.string'                  => 'Catatan harus berupa teks.',
        ]);

        // Validasi tambahan: jika sedang update, maka visit_status wajib diisi
        if ($request->filled('id') && !$request->filled('visit_status')) {
            $validator->after(function ($validator) {
                $validator->errors()->add('visit_status', 'Status kunjungan wajib diisi.');
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Proteksi Otorisasi Wilayah Pasien
        $treatment = PatientTreatment::with('patient')->find($request->patient_treatment_id);
        if (!$treatment || !$treatment->patient) {
            return response()->json(['message' => 'Data pengobatan tidak ditemukan.'], 404);
        }

        $user = Auth::user();
        if (!$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mencatat kunjungan untuk pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        // Proses simpan (create baru atau update)
        $visit = $request->filled('id')
            ? TreatmentVisit::findOrFail($request->id)
            : new TreatmentVisit();

        $visit->patient_treatment_id = $request->patient_treatment_id;
        $visit->visit_date           = $request->visit_date;
        $visit->visit_time           = $request->visit_time;
        $visit->visit_status         = $request->visit_status ?? 'Terjadwal';
        $visit->notes                = $request->notes;

        $visit->save();

        return response()->json([
            'message' => $request->filled('id')
                ? 'Data kunjungan berhasil diperbarui.'
                : 'Kunjungan berhasil ditambahkan.',
            'data'    => $visit
        ], $request->filled('id') ? 200 : 201);
    }

    public function destroy($id)
    {
        $visit = TreatmentVisit::with('patientTreatment.patient')->find($id);

        if (!$visit) {
            return response()->json([
                'message' => 'Data kunjungan tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        $patient = $visit->patientTreatment?->patient;
        if ($patient && !$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang menghapus data kunjungan pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        $visit->delete();

        return response()->json([
            'message' => 'Kunjungan berhasil dihapus.'
        ]);
    }

    public function patientVisits(Request $request)
    {
        $user = Auth::user();

        $patient = null;
        if ($user->user_type_id == 2) {
            $patient = $user->patient;
        } elseif ($request->filled('patient_id')) {
            $patient = \App\Models\Patient::with('puskesmas')->find($request->patient_id);
            if ($patient && !$patient->isAccessibleBy($user)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki wewenang mengakses data pasien ini.'
                ], 403);
            }
        } else {
            $patient = $user->patient;
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

        $puskesmasName = optional($patient->puskesmas)->name ?? 'Puskesmas';

        if (!$treatment) {
            return response()->json([
                'message'        => 'Belum ada data pengobatan.',
                'puskesmas_name' => $puskesmasName,
                'data'           => []
            ], 200);
        }

        $visits = TreatmentVisit::where('patient_treatment_id', $treatment->id)
            ->orderBy('visit_date', 'asc')
            ->orderBy('visit_time', 'asc')
            ->get()
            ->map(function ($visit) use ($puskesmasName) {
                return [
                    'id'                   => $visit->id,
                    'patient_treatment_id' => $visit->patient_treatment_id,
                    'visit_date'           => $visit->visit_date ? \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d') : null,
                    'visit_time'           => $visit->visit_time,
                    'visit_status'         => $visit->visit_status,
                    'notes'                => $visit->notes,
                    'puskesmas_name'       => $puskesmasName,
                    'created_at'           => $visit->created_at,
                    'updated_at'           => $visit->updated_at,
                ];
            });

        return response()->json([
            'message'        => 'Daftar jadwal kunjungan berhasil diambil.',
            'puskesmas_name' => $puskesmasName,
            'data'           => $visits
        ]);
    }

    public function show($id)
    {
        $visit = TreatmentVisit::with(['patientTreatment.patient.puskesmas'])->find($id);

        if (!$visit) {
            return response()->json([
                'message' => 'Data kunjungan tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        $patient = $visit->patientTreatment?->patient;
        if ($patient && !$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses data kunjungan ini.'
            ], 403);
        }

        $puskesmasName = optional($patient?->puskesmas)->name ?? 'Puskesmas';

        return response()->json([
            'message' => 'Detail jadwal kunjungan berhasil diambil.',
            'data'    => [
                'id'                   => $visit->id,
                'patient_treatment_id' => $visit->patient_treatment_id,
                'visit_date'           => $visit->visit_date ? \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d') : null,
                'visit_time'           => $visit->visit_time,
                'visit_status'         => $visit->visit_status,
                'notes'                => $visit->notes,
                'puskesmas_name'       => $puskesmasName,
                'created_at'           => $visit->created_at,
                'updated_at'           => $visit->updated_at,
            ]
        ]);
    }
}
