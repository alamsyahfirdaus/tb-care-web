<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicationRecord;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\TreatmentType;
use App\Models\TreatmentVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TreatmentController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi masukan dari pengguna
        $validator = Validator::make($request->all(), [
            'id'                => 'nullable|exists:patient_treatments,id',
            'patient_id'        => 'required|exists:patients,id',
            'treatment_type_id' => 'required|exists:treatment_types,id',
            'diagnosis_date'    => 'nullable|date',
            'start_date'        => 'nullable|date|after_or_equal:diagnosis_date',
            'medication_time'   => 'required|date_format:H:i',
            'prescription'      => 'nullable|array',
            'treatment_status'  => 'required|in:Berjalan,Selesai,Gagal,Meninggal',
        ], [
            'id.exists'                   => 'Data pengobatan tidak ditemukan.',
            'patient_id.required'         => 'Pasien wajib dipilih.',
            'patient_id.exists'           => 'Pasien tidak ditemukan dalam sistem.',
            'treatment_type_id.required'  => 'Jenis pengobatan wajib dipilih.',
            'treatment_type_id.exists'    => 'Jenis pengobatan tidak valid.',
            'diagnosis_date.date'         => 'Tanggal diagnosis harus berupa tanggal yang valid.',
            'start_date.date'             => 'Tanggal mulai harus berupa tanggal yang valid.',
            'start_date.after_or_equal'   => 'Tanggal mulai tidak boleh sebelum tanggal diagnosis.',
            'medication_time.required'    => 'Waktu minum obat wajib diisi.',
            'medication_time.date_format' => 'Format waktu minum obat harus dalam format HH:ii (contoh: 07:00).',
            'prescription.array'          => 'Resep harus dalam format array.',
            'treatment_status.required'   => 'Status pengobatan wajib diisi.',
            'treatment_status.in'         => 'Status pengobatan harus salah satu dari: Berjalan, Selesai, Gagal, Meninggal.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // 2. Proteksi Otorisasi Wilayah Pasien
        $patient = Patient::find($request->patient_id);
        if (!$patient) {
            return response()->json(['message' => 'Pasien tidak ditemukan dalam sistem.'], 404);
        }

        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang membuat pengobatan untuk pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        // 3. Tentukan apakah ini proses update atau create
        $isUpdate = $request->filled('id');
        if ($isUpdate) {
            $treatment = PatientTreatment::with('patient')->find($request->id);
            if (!$treatment) {
                return response()->json(['message' => 'Data pengobatan tidak ditemukan.'], 404);
            }
            if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
                return response()->json(['message' => 'Anda tidak memiliki wewenang mengubah pengobatan pasien ini.'], 403);
            }
        } else {
            $treatment = new PatientTreatment();
        }

        // 4. Tetapkan tanggal diagnosis dan tanggal mulai
        $diagnosisDate = $request->diagnosis_date ?? now()->toDateString();
        $startDate     = $request->start_date ?? $diagnosisDate;

        // 5. Hitung tanggal selesai pengobatan
        $endDate = null;
        if (!$isUpdate || $request->hasAny(['treatment_type_id', 'start_date'])) {
            $treatmentType = TreatmentType::find($request->treatment_type_id);

            if ($treatmentType && $treatmentType->treatment_duration && $treatmentType->duration_unit) {
                $startDateCarbon = Carbon::parse($startDate);
                $unit = strtolower($treatmentType->duration_unit);

                $endDate = match ($unit) {
                    'day', 'days'     => $startDateCarbon->copy()->addDays($treatmentType->treatment_duration),
                    'week', 'weeks'   => $startDateCarbon->copy()->addWeeks($treatmentType->treatment_duration),
                    'month', 'months' => $startDateCarbon->copy()->addMonths($treatmentType->treatment_duration),
                    'year', 'years'   => $startDateCarbon->copy()->addYears($treatmentType->treatment_duration),
                    default           => null,
                };
            }
        }

        // 6. Simpan data ke dalam database
        $treatment->patient_id        = $request->patient_id;
        $treatment->treatment_type_id = $request->treatment_type_id;
        $treatment->diagnosis_date    = $diagnosisDate;
        $treatment->start_date        = $startDate;
        $treatment->end_date          = $endDate;
        $treatment->treatment_days    = $endDate ? Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) : 0;
        $treatment->medication_time   = $request->medication_time;
        $treatment->prescription      = $request->prescription ? json_encode($request->prescription) : null;
        $treatment->treatment_status  = $request->treatment_status;
        $treatment->save();

        return response()->json([
            'message' => $isUpdate
                ? 'Data pengobatan berhasil diperbarui.'
                : 'Data pengobatan berhasil ditambahkan.',
            'data'    => $treatment
        ], $isUpdate ? 200 : 201);
    }

    public function show($id)
    {
        $treatment = PatientTreatment::with(['patient.user', 'treatmentType', 'visits'])->find($id);

        if (!$treatment) {
            return response()->json([
                'message' => 'Data pengobatan tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses pengobatan pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        return response()->json([
            'message' => 'Detail data pengobatan berhasil diambil.',
            'data'    => $treatment
        ]);
    }

    public function destroy($id)
    {
        $treatment = PatientTreatment::with('patient')->find($id);

        if (!$treatment) {
            return response()->json([
                'message' => 'Data pengobatan tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang menghapus pengobatan pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        $treatment->delete();

        return response()->json([
            'message' => 'Data pengobatan berhasil dihapus.'
        ], 200);
    }

    public function treatmentTypeOption()
    {
        return response()->json([
            'message' => 'Daftar jenis pengobatan berhasil diambil.',
            'data'    => TreatmentType::getTreatmentTypes()
        ]);
    }

    public function updateTreatmentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'               => 'required|exists:patient_treatments,id',
            'treatment_status' => 'required|in:Berjalan,Selesai,Gagal,Meninggal',
        ], [
            'id.required'               => 'ID pengobatan wajib diisi.',
            'id.exists'                 => 'Data pengobatan tidak ditemukan.',
            'treatment_status.required' => 'Status pengobatan wajib diisi.',
            'treatment_status.in'       => 'Status pengobatan harus salah satu dari: Berjalan, Selesai, Gagal, Meninggal.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $treatment = PatientTreatment::with('patient')->findOrFail($request->id);

        $user = Auth::user();
        if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengubah status pengobatan pasien ini.'
            ], 403);
        }

        $treatment->treatment_status = $request->treatment_status;
        $treatment->save();

        return response()->json([
            'message' => 'Status pengobatan berhasil diperbarui.',
            'data'    => $treatment
        ], 200);
    }

    public function submitMedicationProof(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_treatment_id' => 'required|exists:patient_treatments,id',
            'photo'                => 'required|image|max:2048',
            'notes'                => 'nullable|string',
        ], [
            'patient_treatment_id.required' => 'ID pengobatan wajib diisi.',
            'patient_treatment_id.exists'   => 'Data pengobatan tidak ditemukan.',
            'photo.required'                => 'Foto bukti minum obat wajib diunggah.',
            'photo.image'                   => 'File bukti harus berupa gambar.',
            'photo.max'                     => 'Ukuran gambar tidak boleh melebihi 2MB.',
            'notes.string'                  => 'Catatan harus berupa teks.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $treatment = PatientTreatment::with('patient')->findOrFail($request->patient_treatment_id);

        $user = Auth::user();
        if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengirim bukti minum obat untuk pasien ini.'
            ], 403);
        }

        $now = Carbon::now();
        $medicationTime = Carbon::today()->setTimeFromTimeString($treatment->medication_time);
        $lateLimit = $medicationTime->copy()->addHour();
        $isLate = $now->greaterThan($lateLimit);

        $fileName = null;
        if ($request->hasFile('photo')) {
            $destinationPath = public_path('images');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $fileName = Str::random(20) . '.' . $request->file('photo')->getClientOriginalExtension();
            $request->file('photo')->move($destinationPath, $fileName);
        }

        $record = MedicationRecord::create([
            'patient_treatment_id' => $request->patient_treatment_id,
            'photo'                => $fileName,
            'is_verified'          => true,
            'late'                 => $isLate,
            'notes'                => $request->notes,
        ]);

        return response()->json([
            'message' => 'Bukti minum obat berhasil disimpan.',
            'data'    => $record
        ], 201);
    }

    public function medicationHistory($patientId)
    {
        $patient = Patient::find($patientId);
        if (!$patient) {
            return response()->json([
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses riwayat pengobatan pasien ini.'
            ], 403);
        }

        $treatments = PatientTreatment::where('patient_id', $patientId)->pluck('id');

        if ($treatments->isEmpty()) {
            return response()->json([
                'message' => 'Data pengobatan pasien tidak ditemukan.'
            ], 404);
        }

        $records = MedicationRecord::whereIn('patient_treatment_id', $treatments)
            ->orderBy('created_at', 'desc')
            ->get();

        $history = $records->map(function ($record) {
            return [
                'id'                   => $record->id,
                'patient_treatment_id' => $record->patient_treatment_id,
                'photo'                => $record->photo ?: null,
                'is_verified'          => $record->is_verified,
                'late'                 => $record->late,
                'notes'                => $record->notes,
                'submitted_at'         => $record->created_at->format('Y-m-d H:i:s'),
                'submitted_relative'   => $record->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'message' => 'Riwayat minum obat pasien berhasil diambil.',
            'data'    => $history
        ]);
    }

    public function getVisitsByTreatment($treatmentId)
    {
        $treatment = PatientTreatment::with('patient.puskesmas')->find($treatmentId);

        if (!$treatment) {
            return response()->json(['message' => 'Data pengobatan tidak ditemukan.'], 404);
        }

        $user = Auth::user();
        if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses data kunjungan pengobatan ini.'
            ], 403);
        }

        $puskesmasName = optional($treatment->patient?->puskesmas)->name ?? 'Puskesmas';

        $visits = TreatmentVisit::where('patient_treatment_id', $treatmentId)
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
            'message'        => 'Daftar kunjungan berhasil diambil.',
            'puskesmas_name' => $puskesmasName,
            'data'           => $visits
        ]);
    }

    public function verifyMedicationProof(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'                   => 'required|exists:medication_records,id',
            'patient_treatment_id' => 'nullable|exists:patient_treatments,id',
            'notes'                => 'nullable|string|max:500',
        ], [
            'id.required'                  => 'ID bukti minum obat wajib diisi.',
            'id.exists'                    => 'Data bukti minum obat tidak ditemukan.',
            'patient_treatment_id.exists'  => 'Data pengobatan tidak ditemukan.',
            'notes.string'                 => 'Catatan harus berupa teks.',
            'notes.max'                    => 'Catatan maksimal 500 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $record = MedicationRecord::with('patientTreatment.patient')
            ->where('id', $request->id)
            ->when($request->filled('patient_treatment_id'), function ($query) use ($request) {
                $query->where('patient_treatment_id', $request->patient_treatment_id);
            })
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Data bukti minum obat tidak ditemukan.'
            ], 404);
        }

        $user = Auth::user();
        $patient = $record->patientTreatment?->patient;
        if ($patient && !$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang memverifikasi bukti minum obat pasien ini.'
            ], 403);
        }

        if (!$record->is_verified) {
            $record->is_verified = true;
        }

        $record->notes = $request->notes ?? $record->notes;
        $record->save();

        return response()->json([
            'message' => 'Bukti minum obat berhasil diverifikasi.',
            'data'    => [
                'id'                   => $record->id,
                'patient_treatment_id' => $record->patient_treatment_id,
                'is_verified'          => $record->is_verified,
                'notes'                => $record->notes,
                'updated_at'           => $record->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * GET /api/treatments/medications
     * Mengambil daftar obat untuk pengobatan pasien terautentikasi.
     * Terproteksi otorisasi penuh: Pasien hanya dapat melihat obatnya sendiri.
     */
    public function patientMedications(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi tidak valid. Silakan login kembali.'
            ], 401);
        }

        $patient = null;
        // 1. Jika pengguna adalah Pasien (user_type_id == 2)
        if ($user->user_type_id == 2) {
            $patient = Patient::where('user_id', $user->id)->first();
        } else {
            // 2. Petugas / Admin: cek patient_id dari parameter query dengan proteksi otorisasi
            $patientId = $request->query('patient_id');
            if ($patientId) {
                $patient = Patient::find($patientId);
                if ($patient && method_exists($patient, 'isAccessibleBy') && !$patient->isAccessibleBy($user)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak memiliki wewenang mengakses data pasien ini.'
                    ], 403);
                }
            }
        }

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        // Ambil pengobatan aktif pasien: utamakan yang berstatus 'Berjalan', atau pengobatan terbaru
        $treatment = PatientTreatment::with('treatmentType')
            ->where('patient_id', $patient->id)
            ->where('treatment_status', 'Berjalan')
            ->orderByDesc('start_date')
            ->first();

        if (!$treatment) {
            $treatment = PatientTreatment::with('treatmentType')
                ->where('patient_id', $patient->id)
                ->orderByDesc('id')
                ->first();
        }

        if (!$treatment) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada data pengobatan yang terdaftar.',
                'data'    => [
                    'treatment_id'     => null,
                    'treatment_type'   => null,
                    'treatment_status' => null,
                    'medication_time'  => null,
                    'count'            => 0,
                    'medications'      => [],
                ]
            ], 200);
        }

        // Resolusi waktu minum obat
        $medicationTime = $treatment->medication_time;
        if (Schema::hasTable('patient_medication_schedules')) {
            $schedule = $patient->medicationSchedule;
            if ($schedule && $schedule->reminder_time) {
                $medicationTime = $schedule->reminder_time;
            }
        }
        if ($medicationTime && strlen($medicationTime) >= 5) {
            $medicationTime = substr($medicationTime, 0, 5);
        }

        // Parse resep obat (JSON string, array, atau comma-separated)
        $prescription = $treatment->prescription;
        if (is_string($prescription)) {
            $decoded = json_decode($prescription, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $prescription = $decoded;
            } else {
                $prescription = array_values(array_filter(array_map('trim', explode(',', $prescription))));
            }
        }

        if (!is_array($prescription)) {
            $prescription = [];
        }

        $medications = [];
        foreach ($prescription as $item) {
            if ($item === null || $item === '') continue;

            if (is_string($item)) {
                $splitParts = preg_split('/[\r\n,]+/', $item);
                foreach ($splitParts as $part) {
                    $itemStr = trim($part);
                    if ($itemStr === '' || $itemStr === '-') continue;

                    $medications[] = [
                        'name'          => $itemStr,
                        'dosage'        => null,
                        'frequency'     => null,
                        'rules'         => null,
                        'schedule_time' => $medicationTime,
                    ];
                }
            } elseif (is_array($item)) {
                $medName = $item['name'] ?? $item['nama_obat'] ?? $item['nama'] ?? $item['drug_name'] ?? '-';
                $medications[] = [
                    'name'          => $medName,
                    'dosage'        => $item['dosage'] ?? $item['dosis'] ?? null,
                    'frequency'     => $item['frequency'] ?? $item['frekuensi'] ?? $item['aturan_pakai'] ?? null,
                    'rules'         => $item['rules'] ?? $item['instruksi'] ?? $item['catatan'] ?? null,
                    'schedule_time' => $item['time'] ?? $item['jadwal'] ?? $medicationTime,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar obat berhasil diambil.',
            'data'    => [
                'treatment_id'     => $treatment->id,
                'treatment_type'   => optional($treatment->treatmentType)->treatment_type ?? 'TB Care',
                'treatment_status' => $treatment->treatment_status,
                'start_date'       => $treatment->start_date,
                'end_date'         => $treatment->end_date,
                'medication_time'  => $medicationTime,
                'count'            => count($medications),
                'medications'      => $medications,
            ]
        ], 200);
    }
}
