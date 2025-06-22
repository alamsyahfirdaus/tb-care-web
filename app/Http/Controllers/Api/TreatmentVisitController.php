<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TreatmentVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TreatmentVisitController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input dari form request
        $validator = Validator::make($request->all(), [
            'id'                   => 'nullable|exists:treatment_visits,id',
            'patient_treatment_id' => 'required|exists:patient_treatments,id',
            'visit_date'          => 'required|date|after_or_equal:today',
            'visit_time'          => 'nullable|date_format:H:i',
            'visit_status'        => 'in:Terjadwal,Hadir,Tidak Hadir',
            'notes'               => 'nullable|string',
        ], [
            'id.exists'                    => 'Data kunjungan tidak ditemukan.',
            'patient_treatment_id.required' => 'ID pengobatan pasien wajib diisi.',
            'patient_treatment_id.exists'  => 'Data pengobatan tidak ditemukan.',
            'visit_date.required'          => 'Tanggal kunjungan wajib diisi.',
            'visit_date.after_or_equal'    => 'Tanggal kunjungan tidak boleh sebelum hari ini.',
            'visit_date.date'              => 'Tanggal kunjungan harus berupa format tanggal yang valid.',
            'visit_time.date_format'       => 'Format waktu kunjungan harus dalam format HH:ii (contoh: 08:30).',
            'visit_status.in'              => 'Status kunjungan harus salah satu dari: Terjadwal, Hadir, atau Tidak Hadir.',
            'notes.string'                 => 'Catatan harus berupa teks.',
        ]);

        // Validasi tambahan: jika sedang update, maka visit_status wajib diisi
        if ($request->filled('id') && !$request->filled('visit_status')) {
            $validator->after(function ($validator) {
                $validator->errors()->add('visit_status', 'Status kunjungan wajib diisi.');
            });
        }

        // Jika validasi gagal, kirimkan pesan error
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Proses simpan (create baru atau update)
        $visit = $request->filled('id')
            ? TreatmentVisit::findOrFail($request->id) // Update
            : new TreatmentVisit();                   // Create baru

        // Set atribut data kunjungan
        $visit->patient_treatment_id = $request->patient_treatment_id;
        $visit->visit_date           = $request->visit_date;
        $visit->visit_time           = $request->visit_time;
        $visit->visit_status         = $request->visit_status ?? 'Terjadwal';
        $visit->notes                = $request->notes;

        // Simpan ke database
        $visit->save();

        // Kirim respon sukses
        return response()->json([
            'message' => $request->filled('id')
                ? 'Data kunjungan berhasil diperbarui.'
                : 'Kunjungan berhasil ditambahkan.',
            'data'    => $visit
        ], $request->filled('id') ? 200 : 201);
    }

    public function destroy($id)
    {
        // Cari data kunjungan berdasarkan ID
        $visit = TreatmentVisit::find($id);

        // Jika tidak ditemukan
        if (!$visit) {
            return response()->json([
                'message' => 'Data kunjungan tidak ditemukan.'
            ], 404);
        }

        // Hapus data
        $visit->delete();

        // Kirim respon sukses
        return response()->json([
            'message' => 'Kunjungan berhasil dihapus.'
        ]);
    }
}
