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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TreatmentController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        // Kader and Patient strictly forbidden from creating or modifying treatments
        if ($user && ($user->isKader() || $user->isPatient())) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk menambah atau mengubah data pengobatan.'
            ], 403);
        }

        // 1. Validasi masukan dari pengguna
        $validator = Validator::make($request->all(), [
            'id'                => 'nullable|exists:patient_treatments,id',
            'patient_id'        => 'required|exists:patients,id',
            'treatment_type_id' => 'required|exists:treatment_types,id',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
            'diagnosis_date'    => 'nullable|date',
            'medication_time'   => 'required|date_format:H:i',
            'prescription'      => 'nullable|array',
            'treatment_status'  => 'required|in:Berjalan,Selesai,Sembuh,Pengobatan Lengkap,Gagal,Meninggal,Putus Berobat,Tidak Dievaluasi,Pindah,perlu_tindak_lanjut,Perlu Tindak Lanjut',
        ], [
            'id.exists'                   => 'Data pengobatan tidak ditemukan.',
            'patient_id.required'         => 'Pasien wajib dipilih.',
            'patient_id.exists'           => 'Pasien tidak ditemukan dalam sistem.',
            'treatment_type_id.required'  => 'Jenis pengobatan wajib dipilih.',
            'treatment_type_id.exists'    => 'Jenis pengobatan tidak valid.',
            'start_date.required'         => 'Tanggal mulai pengobatan wajib diisi.',
            'start_date.date'             => 'Format tanggal mulai harus berupa tanggal yang valid.',
            'end_date.required'           => 'Tanggal selesai pengobatan wajib diisi.',
            'end_date.date'               => 'Format tanggal selesai harus berupa tanggal yang valid.',
            'end_date.after_or_equal'     => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai pengobatan.',
            'diagnosis_date.date'         => 'Tanggal diagnosis harus berupa tanggal yang valid.',
            'medication_time.required'    => 'Waktu minum obat wajib diisi.',
            'medication_time.date_format' => 'Format waktu minum obat harus dalam format HH:ii (contoh: 07:00).',
            'prescription.array'          => 'Resep harus dalam format array.',
            'treatment_status.required'   => 'Status pengobatan wajib diisi.',
            'treatment_status.in'         => 'Status pengobatan harus salah satu dari: Berjalan, Perlu Tindak Lanjut, Sembuh, Pengobatan Lengkap, Selesai, Gagal, Meninggal, Putus Berobat, Tidak Dievaluasi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

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
            // Validasi: Tidak dapat menambah pengobatan baru jika status pasien meninggal
            if ($patient && strtolower($patient->status ?? '') === 'meninggal') {
                return response()->json([
                    'message' => 'Tidak dapat menambahkan periode pengobatan untuk pasien dengan status meninggal.'
                ], 422);
            }
            $treatment = new PatientTreatment();
        }

        // 4. Tetapkan tanggal mulai dan tanggal diagnosis
        $startDate     = $request->start_date ?? ($request->diagnosis_date ?? now()->toDateString());
        $diagnosisDate = $request->diagnosis_date ?? $startDate;

        // 5. Hitung / tetapkan tanggal selesai pengobatan (utamakan tanggal selesai input Petugas)
        if ($request->filled('end_date')) {
            $endDate = $request->end_date;
        } else {
            $endDate = null;
            $treatmentType = TreatmentType::find($request->treatment_type_id);

            if ($treatmentType && $treatmentType->treatment_duration && $treatmentType->duration_unit) {
                $startDateCarbon = Carbon::parse($startDate);
                $unit = strtolower($treatmentType->duration_unit);

                $endDate = match ($unit) {
                    'day', 'days'     => $startDateCarbon->copy()->addDays($treatmentType->treatment_duration)->toDateString(),
                    'week', 'weeks'   => $startDateCarbon->copy()->addWeeks($treatmentType->treatment_duration)->toDateString(),
                    'month', 'months' => $startDateCarbon->copy()->addMonths($treatmentType->treatment_duration)->toDateString(),
                    'year', 'years'   => $startDateCarbon->copy()->addYears($treatmentType->treatment_duration)->toDateString(),
                    default           => null,
                };
            }
        }

        // 6. Simpan data ke dalam database menggunakan Database Transaction
        try {
            DB::transaction(function () use ($request, $patient, $isUpdate, $treatment, $startDate, $diagnosisDate, $endDate) {
                // Aturan Bisnis Perubahan 5:
                // Ketika Petugas menambahkan episode pengobatan baru sementara pasien masih memiliki pengobatan
                // sebelumnya dengan status Berjalan, sistem harus mengubah status pengobatan sebelumnya
                // menjadi 'Perlu Tindak Lanjut'.
                if (!$isUpdate) {
                    PatientTreatment::where('patient_id', $patient->id)
                        ->where(function ($q) {
                            $q->where('treatment_status', 'Berjalan')
                              ->orWhere('treatment_status', 'berjalan');
                        })
                        ->update([
                            'treatment_status' => 'Perlu Tindak Lanjut',
                        ]);
                }

                $treatment->patient_id        = $request->patient_id;
                $treatment->treatment_type_id = $request->treatment_type_id;
                $treatment->diagnosis_date    = $diagnosisDate;
                $treatment->start_date        = $startDate;
                $treatment->end_date          = $endDate;
                $treatment->treatment_days    = ($startDate && $endDate) ? Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) : 0;
                $treatment->medication_time   = $request->medication_time;
                $treatment->prescription      = $request->prescription ? json_encode($request->prescription) : null;
                $treatment->treatment_status  = $request->treatment_status;
                $treatment->save();

                // 7. Sinkronisasi tanggal mulai pengobatan aktif pada model Patient jika berstatus Berjalan
                if ($treatment->treatment_status === 'Berjalan' && $patient) {
                    // Jika proses update dan statusnya Berjalan, pastikan episode Berjalan lainnya untuk pasien ini
                    // diubah juga menjadi 'Perlu Tindak Lanjut' agar tidak ada episode Berjalan yang bertentangan
                    if ($isUpdate) {
                        PatientTreatment::where('patient_id', $patient->id)
                            ->where('id', '!=', $treatment->id)
                            ->where(function ($q) {
                                $q->where('treatment_status', 'Berjalan')
                                  ->orWhere('treatment_status', 'berjalan');
                            })
                            ->update([
                                'treatment_status' => 'Perlu Tindak Lanjut',
                            ]);
                    }

                    $patient->treatment_start_date = $startDate;
                    $patient->save();
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data pengobatan: ' . $e->getMessage()
            ], 500);
        }

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
        $user = Auth::user();

        // Kader and Patient strictly forbidden from deleting treatments
        if ($user && ($user->isKader() || $user->isPatient())) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk menghapus data pengobatan.'
            ], 403);
        }

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

        // Validasi: Jika pasien hanya memiliki satu periode pengobatan, tolak penghapusan
        $patientTreatmentCount = PatientTreatment::where('patient_id', $treatment->patient_id)->count();
        if ($patientTreatmentCount <= 1) {
            return response()->json([
                'message' => 'Periode pengobatan terakhir pasien tidak dapat dihapus. Pasien harus memiliki minimal satu periode pengobatan.'
            ], 422);
        }

        // Validasi: Hanya periode pengobatan terbaru yang boleh dihapus
        $latestId = PatientTreatment::where('patient_id', $treatment->patient_id)->max('id');
        if ($treatment->id != $latestId) {
            return response()->json([
                'message' => 'Hanya periode pengobatan terbaru yang dapat dihapus.'
            ], 422);
        }

        // Safety check: jika periode pengobatan sudah memiliki riwayat minum obat, tolak penghapusan untuk integritas data monitoring
        if ($treatment->medicationRecords()->exists()) {
            return response()->json([
                'message' => 'Tidak dapat menghapus periode pengobatan yang sudah memiliki riwayat minum obat.'
            ], 422);
        }

        DB::transaction(function () use ($treatment) {
            $patientId = $treatment->patient_id;
            // Bersihkan relasi visits sebelum menghapus record treatment
            $treatment->visits()->delete();
            $treatment->delete();

            // Sinkronkan data pengobatan aktif pasien setelah penghapusan periode terbaru
            $remainingLatest = PatientTreatment::where('patient_id', $patientId)
                ->orderBy('start_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            if ($remainingLatest) {
                $patient = Patient::find($patientId);
                if ($patient) {
                    $patient->treatment_start_date = $remainingLatest->start_date;
                    $patient->save();
                }
            }
        });

        return response()->json([
            'message' => 'Periode pengobatan berhasil dihapus.'
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
        $user = Auth::user();

        // Kader and Patient strictly forbidden from updating treatment status
        if ($user && ($user->isKader() || $user->isPatient())) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk mengubah status pengobatan.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id'               => 'required|exists:patient_treatments,id',
            'treatment_status' => 'required|in:Berjalan,Selesai,Sembuh,Pengobatan Lengkap,Gagal,Meninggal,Putus Berobat,Tidak Dievaluasi,Pindah,perlu_tindak_lanjut,Perlu Tindak Lanjut',
        ], [
            'id.required'               => 'ID pengobatan wajib diisi.',
            'id.exists'                 => 'Data pengobatan tidak ditemukan.',
            'treatment_status.required' => 'Status pengobatan wajib diisi.',
            'treatment_status.in'       => 'Status pengobatan harus salah satu dari: Berjalan, Perlu Tindak Lanjut, Sembuh, Pengobatan Lengkap, Selesai, Gagal, Meninggal, Putus Berobat, Tidak Dievaluasi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $treatment = PatientTreatment::with('patient')->findOrFail($request->id);

        if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengubah status pengobatan pasien ini.'
            ], 403);
        }

        try {
            DB::transaction(function () use ($treatment, $request) {
                $treatment->treatment_status = $request->treatment_status;
                $treatment->save();

                if ($treatment->treatment_status === 'Berjalan' && $treatment->patient) {
                    // Pastikan tidak ada episode Berjalan lainnya yang bertentangan
                    PatientTreatment::where('patient_id', $treatment->patient_id)
                        ->where('id', '!=', $treatment->id)
                        ->where(function ($q) {
                            $q->where('treatment_status', 'Berjalan')
                              ->orWhere('treatment_status', 'berjalan');
                        })
                        ->update([
                            'treatment_status' => 'Perlu Tindak Lanjut',
                        ]);

                    $treatment->patient->treatment_start_date = $treatment->start_date;
                    $treatment->patient->save();
                }
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui status pengobatan: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Status pengobatan berhasil diperbarui.',
            'data'    => $treatment
        ], 200);
    }

    public function submitMedicationProof(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_treatment_id' => 'required|exists:patient_treatments,id',
            'photo'                => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'notes'                => 'nullable|string|max:500',
        ], [
            'patient_treatment_id.required' => 'ID pengobatan wajib diisi.',
            'patient_treatment_id.exists'   => 'Data pengobatan tidak ditemukan.',
            'photo.required'                => 'Bukti foto wajib dilampirkan sebelum melakukan konfirmasi minum obat.',
            'photo.image'                   => 'File bukti harus berupa gambar.',
            'photo.mimes'                   => 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',
            'photo.max'                     => 'Ukuran gambar tidak boleh melebihi 2MB.',
            'notes.string'                  => 'Catatan harus berupa teks.',
            'notes.max'                     => 'Catatan maksimal 500 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $treatment = PatientTreatment::with(['patient.user', 'patient.puskesmas'])->findOrFail($request->patient_treatment_id);

        $user = Auth::user();
        if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengirim bukti minum obat untuk pasien ini.'
            ], 403);
        }

        // Cegah pencatatan ganda pada hari yang sama (Zona waktu Asia/Jakarta)
        $todayRecord = MedicationRecord::where('patient_treatment_id', $treatment->id)
            ->whereDate('created_at', Carbon::today('Asia/Jakarta'))
            ->first();

        if ($todayRecord) {
            return response()->json([
                'message' => 'Anda sudah mencatat minum obat untuk hari ini.',
                'data'    => $todayRecord,
            ], 422);
        }

        $now = Carbon::now('Asia/Jakarta');
        $medicationTimeStr = $treatment->medication_time ?: '08:00:00';
        $medicationTime = Carbon::today('Asia/Jakarta')->setTimeFromTimeString($medicationTimeStr);
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
            'is_verified'          => false,
            'status'               => 'Diminum',
            'confirmation_source'  => 'patient',
            'late'                 => $isLate,
            'notes'                => $request->notes,
        ]);

        // Kirim notifikasi otomatis ke petugas terkait (PJTB dan Kader penanggung jawab)
        try {
            if ($treatment->patient) {
                $officerUsers = $treatment->patient->getResponsibleOfficers();
                if ($officerUsers->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send(
                        $officerUsers,
                        new \App\Notifications\PatientMedicationLoggedNotification(
                            $treatment->patient,
                            $record,
                            $request->notes,
                            $isLate
                        )
                    );

                    // Kirim Push Notification via FCM jika token tersedia (best-effort)
                    $fcmTokens = $officerUsers->pluck('fcm_token')->filter()->unique()->values()->all();
                    if (!empty($fcmTokens)) {
                        $patientName = optional($treatment->patient->user)->name ?? 'Pasien TB';
                        $notifTitle = 'Pasien Telah Minum Obat';
                        $notifBody = "{$patientName} telah mencatat minum obat.";
                        if (!empty($request->notes)) {
                            $notifBody .= " Keterangan: {$request->notes}";
                        }
                        \App\Services\FcmService::sendNotification(
                            $fcmTokens,
                            $notifTitle,
                            $notifBody,
                            [
                                'type'       => 'medication_logged',
                                'patient_id' => (string) $treatment->patient_id,
                                'record_id'  => (string) $record->id,
                            ]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengirim notifikasi minum obat: ' . $e->getMessage());
        }

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

        $patientTreatmentId = request()->query('treatment_id', request()->query('patient_treatment_id'));

        if ($patientTreatmentId) {
            $targetTreatment = PatientTreatment::where('patient_id', $patientId)
                ->where('id', $patientTreatmentId)
                ->first();
        } else {
            $targetTreatment = PatientTreatment::where('patient_id', $patientId)
                ->where('treatment_status', 'Berjalan')
                ->latest('start_date')
                ->first()
                ?? PatientTreatment::where('patient_id', $patientId)->latest('id')->first();
        }

        if (!$targetTreatment) {
            return response()->json([
                'message' => 'Data pengobatan pasien tidak ditemukan.'
            ], 404);
        }

        // Catatan minum obat hanya untuk episode pengobatan yang dipilih/aktif (mencegah pencampuran data antar-episode)
        $records = MedicationRecord::where('patient_treatment_id', $targetTreatment->id)
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
                'submitted_relative'   => $record->created_at ? $record->created_at->locale('id')->diffForHumans() : null,
            ];
        });

        // Hitung statistik kepatuhan dan daftar hari terlewat untuk episode pengobatan ini
        $period = request()->query('period', 'all');
        $activeTreatment = $targetTreatment;
        $adherence = $activeTreatment->calculateDetailedAdherence($period);

        // Satu daftar riwayat minum obat kronologis (terbaru ke terlama)
        $unifiedHistory = ($adherence && !empty($adherence['daily_records']))
            ? $adherence['daily_records']
            : $history;

        // Filter catatan jika parameter filter dikirimkan
        $filter = request()->query('filter', 'all');
        $filteredHistory = collect($unifiedHistory);

        if ($filter === 'verified') {
            $filteredHistory = $filteredHistory->filter(function ($item) {
                return (isset($item['is_verified']) && (int) $item['is_verified'] === 1)
                    || (isset($item['verification_status']) && str_contains(strtolower($item['verification_status']), 'terverifikasi'));
            })->values();
        } elseif ($filter === 'pending') {
            $filteredHistory = $filteredHistory->filter(function ($item) {
                return (isset($item['is_verified']) && (int) $item['is_verified'] === 0)
                    || (isset($item['verification_status']) && str_contains(strtolower($item['verification_status']), 'menunggu'));
            })->values();
        } elseif ($filter === 'late') {
            $filteredHistory = $filteredHistory->filter(function ($item) {
                return isset($item['late']) && (int) $item['late'] === 1;
            })->values();
        } elseif ($filter === 'missed') {
            $filteredHistory = $filteredHistory->filter(function ($item) {
                return (isset($item['status_key']) && $item['status_key'] === 'missed')
                    || (isset($item['status']) && $item['status'] === 'Terlewat');
            })->values();
        }

        $totalRecords = $filteredHistory->count();
        $isTakenToday = $activeTreatment ? $activeTreatment->isTakenToday() : false;

        // Paginasi jika request meminta page/per_page (seperti pada Flutter mobile)
        if (request()->has('page')) {
            $page = max(1, (int) request()->query('page', 1));
            $perPage = max(1, (int) request()->query('per_page', 10));
            $offset = ($page - 1) * $perPage;
            $pagedHistory = $filteredHistory->slice($offset, $perPage)->values()->all();
            $lastPage = (int) ceil($totalRecords / $perPage);

            return response()->json([
                'message'        => 'Riwayat minum obat pasien berhasil diambil.',
                'treatment_id'   => $targetTreatment->id,
                'data'           => $pagedHistory,
                'raw_records'    => $history,
                'adherence'      => $adherence,
                'total'          => $totalRecords,
                'current_page'   => $page,
                'per_page'       => $perPage,
                'last_page'      => $lastPage,
                'is_taken_today' => $isTakenToday,
            ]);
        }

        return response()->json([
            'message'        => 'Riwayat minum obat pasien berhasil diambil.',
            'treatment_id'   => $targetTreatment->id,
            'data'           => $filteredHistory->values()->all(),
            'raw_records'    => $history,
            'adherence'      => $adherence,
            'total'          => $totalRecords,
            'is_taken_today' => $isTakenToday,
        ]);
    }

    public function getVisitsByTreatment($treatmentId)
    {
        $treatment = PatientTreatment::with('patient.puskesmas.subdistrict.district')->find($treatmentId);

        if (!$treatment) {
            return response()->json(['message' => 'Data pengobatan tidak ditemukan.'], 404);
        }

        $user = Auth::user();
        if ($treatment->patient && !$treatment->patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang mengakses data jadwal kontrol pengobatan ini.'
            ], 403);
        }

        $puskesmas = $treatment->patient?->puskesmas;
        $puskesmasName = optional($puskesmas)->name ?? 'Puskesmas';
        $puskesmasLocation = $puskesmas?->full_address ?? $puskesmasName;

        $visits = TreatmentVisit::where('patient_treatment_id', $treatmentId)
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

        // Only authorized officers (PJTB, Admin) can verify medication proof. Kader and Patient are strictly forbidden.
        if (!$user || $user->isKader() || $user->isPatient()) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang memverifikasi bukti minum obat.'
            ], 403);
        }

        $patient = $record->patientTreatment?->patient;
        if ($patient && !$patient->isAccessibleBy($user)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang memverifikasi bukti minum obat pasien ini.'
            ], 403);
        }

        $record->is_verified = true;
        $record->status = 'Terverifikasi';
        $record->verified_by = $user->id;
        $record->verified_at = now();
        if ($request->filled('notes')) {
            $record->verification_note = $request->notes;
        }
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
