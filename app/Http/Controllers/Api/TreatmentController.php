<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicationRecord;
use App\Models\PatientTreatment;
use App\Models\TreatmentType;
use App\Models\TreatmentVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        // Jika validasi gagal, kembalikan response error
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 2. Tentukan apakah ini proses update atau create
        $isUpdate = $request->filled('id');
        $treatment = $isUpdate
            ? PatientTreatment::findOrFail($request->id)
            : new PatientTreatment();

        // 3. Tetapkan tanggal diagnosis dan tanggal mulai (default ke hari ini jika tidak diisi)
        $diagnosisDate = $request->diagnosis_date ?? now()->toDateString();
        $startDate     = $request->start_date ?? $diagnosisDate;

        // 4. Hitung tanggal selesai pengobatan hanya jika:
        // - sedang create
        // - atau terjadi perubahan treatment_type_id atau start_date
        $endDate = null;
        if (!$isUpdate || $request->hasAny(['treatment_type_id', 'start_date'])) {
            $treatmentType = TreatmentType::find($request->treatment_type_id);

            if ($treatmentType && $treatmentType->treatment_duration && $treatmentType->duration_unit) {
                $startDateCarbon = Carbon::parse($startDate);
                $unit = strtolower($treatmentType->duration_unit);

                // Hitung tanggal selesai berdasarkan unit durasi
                $endDate = match ($unit) {
                    'day'   => $startDateCarbon->copy()->addDays($treatmentType->treatment_duration),
                    'week'  => $startDateCarbon->copy()->addWeeks($treatmentType->treatment_duration),
                    'month' => $startDateCarbon->copy()->addMonths($treatmentType->treatment_duration),
                    'year'  => $startDateCarbon->copy()->addYears($treatmentType->treatment_duration),
                    default => null,
                };
            }
        }

        // 5. Simpan data ke dalam database
        $treatment->patient_id        = $request->patient_id;
        $treatment->treatment_type_id = $request->treatment_type_id;
        $treatment->diagnosis_date    = $diagnosisDate;
        $treatment->start_date        = $startDate;
        $treatment->end_date          = $endDate;
        $treatment->treatment_days    = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
        $treatment->medication_time   = $request->medication_time;
        $treatment->prescription      = $request->prescription ? json_encode($request->prescription) : null;
        $treatment->treatment_status  = $request->treatment_status;
        $treatment->save();

        // 6. Response sukses
        return response()->json([
            'message' => $isUpdate
                ? 'Data pengobatan berhasil diperbarui.'
                : 'Data pengobatan berhasil ditambahkan.',
            'data'    => $treatment
        ], $isUpdate ? 200 : 201);
    }

    public function destroy($id)
    {
        // Cari data pengobatan berdasarkan ID
        $treatment = PatientTreatment::find($id);

        // Jika data tidak ditemukan, kembalikan response error
        if (!$treatment) {
            return response()->json([
                'message' => 'Data pengobatan tidak ditemukan.'
            ], 404);
        }

        // Hapus data pengobatan
        $treatment->delete();

        // Kembalikan response sukses
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
        // Validasi masukan
        $validator = Validator::make($request->all(), [
            'id'               => 'required|exists:patient_treatments,id',
            'treatment_status' => 'required|in:Berjalan,Selesai,Gagal,Meninggal',
        ], [
            'id.required'               => 'ID pengobatan wajib diisi.',
            'id.exists'                 => 'Data pengobatan tidak ditemukan.',
            'treatment_status.required' => 'Status pengobatan wajib diisi.',
            'treatment_status.in'       => 'Status pengobatan harus salah satu dari: Berjalan, Selesai, Gagal, Meninggal.',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Ambil dan update status pengobatan
        $treatment = PatientTreatment::findOrFail($request->id);
        $treatment->treatment_status = $request->treatment_status;
        $treatment->save();

        return response()->json([
            'message' => 'Status pengobatan berhasil diperbarui.',
            'data'    => $treatment
        ], 200);
    }

    public function submitMedicationProof(Request $request)
    {
        // 1. Validasi input dari pengguna
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

        // 2. Jika validasi gagal, kembalikan respons error
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // 3. Ambil data pengobatan terkait
        $treatment = PatientTreatment::findOrFail($request->patient_treatment_id);

        // 4. Bandingkan waktu saat ini dengan waktu ideal minum obat (format HH:ii)
        $expectedTime = Carbon::now()->format('H:i');
        $isLate = $expectedTime > $treatment->medication_time;

        // 5. Simpan foto bukti minum obat
        $fileName = null;
        if ($request->hasFile('photo')) {
            $fileName = Str::random(20) . '.' . $request->file('photo')->getClientOriginalExtension();
            $request->file('photo')->storeAs('images', $fileName, 'public');
        }

        // 6. Simpan data ke dalam tabel medication_records
        $record = MedicationRecord::create([
            'patient_treatment_id' => $request->patient_treatment_id,
            'photo'                => $fileName,
            'is_verified'          => false,
            'late'                 => $isLate,
            'notes'                => $request->notes,
        ]);

        // 7. Kembalikan respons sukses
        return response()->json([
            'message' => 'Bukti minum obat berhasil disimpan.',
            'data'    => $record
        ], 201);
    }

    public function medicationHistory($treatmentId)
    {
        // 1. Validasi keberadaan data pengobatan
        $treatment = PatientTreatment::find($treatmentId);

        if (!$treatment) {
            return response()->json([
                'message' => 'Data pengobatan tidak ditemukan.'
            ], 404);
        }

        // 2. Ambil semua catatan minum obat berdasarkan treatment_id
        $records = MedicationRecord::where('patient_treatment_id', $treatmentId)
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Mapping data untuk respons agar lebih rapi dan jelas
        $history = $records->map(function ($record) {
            return [
                'id'                   => $record->id,
                'photo_url'           => $record->photo
                    ? asset('storage/images/' . $record->photo)
                    : null,
                'is_verified'         => $record->is_verified,
                'late'                => $record->late,
                'notes'               => $record->notes,
                'submitted_at'        => $record->created_at->format('Y-m-d H:i:s'),
                'submitted_relative'  => $record->created_at->diffForHumans(),
            ];
        });

        // 4. Kirim response JSON
        return response()->json([
            'message' => 'Riwayat minum obat berhasil diambil.',
            'data'    => $history
        ]);
    }

    public function getVisitsByTreatment($treatmentId)
    {
        $treatment = PatientTreatment::find($treatmentId);

        if (!$treatment) {
            return response()->json(['message' => 'Data pengobatan tidak ditemukan.'], 404);
        }

        $visits = TreatmentVisit::where('patient_treatment_id', $treatmentId)
            ->orderBy('visit_date', 'asc')
            ->get();

        return response()->json([
            'message' => 'Daftar kunjungan berhasil diambil.',
            'data'    => $visits
        ]);
    }
}
