<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicalExamination;
use App\Models\Patient;
use App\Models\Puskesmas;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExaminationController extends Controller
{
    public function index(Request $request)
    {
        $viewType = $request->query('view'); // 'results', 'diagnosis', or null
        $typeFilter = $request->query('type');
        $resultFilter = $request->query('result');
        $puskesmasFilter = $request->query('puskesmas_id');
        $keyword = $request->query('q');

        $query = ClinicalExamination::with(['patient.user', 'puskesmas']);

        if ($typeFilter) {
            $query->where('examination_type', $typeFilter);
        }

        if ($resultFilter) {
            $query->where('result', $resultFilter);
        }

        if ($puskesmasFilter) {
            $query->where('puskesmas_id', $puskesmasFilter);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('examination_code', 'like', "%{$keyword}%")
                  ->orWhere('diagnosis', 'like', "%{$keyword}%")
                  ->orWhereHas('patient.user', function ($sub) use ($keyword) {
                      $sub->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        $examinations = $query->orderByDesc('examination_date')->get();
        $puskesmasList = Puskesmas::orderBy('name')->get();

        $pageTitle = 'Semua Pemeriksaan TB';
        if ($viewType == 'results') $pageTitle = 'Hasil Pemeriksaan Laboratorium TB';
        if ($viewType == 'diagnosis') $pageTitle = 'Diagnosis Klinis & Bakteriologis TB';

        return view('admin.examinations.index', compact(
            'examinations',
            'puskesmasList',
            'viewType',
            'typeFilter',
            'resultFilter',
            'puskesmasFilter',
            'keyword',
            'pageTitle'
        ))->with([
            'title'        => $pageTitle,
            'pageSubtitle' => 'Pemeriksaan dahak Tes Cepat Molekuler (TCM), mikroskopis BTA, dan Rontgen Dada pasien TB.'
        ]);
    }

    public function create()
    {
        $patients = Patient::with('user')->get();
        $puskesmas = Puskesmas::orderBy('name')->get();

        return view('admin.examinations.form', compact('patients', 'puskesmas'))->with([
            'title'        => 'Tambah Pemeriksaan TB',
            'pageTitle'    => 'Catat Pemeriksaan Laboratorium Baru',
            'pageSubtitle' => 'Input hasil pemeriksaan TCM, BTA, atau foto Thorax pasien TB.',
            'examination'  => new ClinicalExamination(),
            'isEdit'       => false,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'examination_date' => 'required|date',
            'examination_type' => 'required|string',
            'result'           => 'required|string',
            'diagnosis'        => 'required|string',
            'puskesmas_id'     => 'nullable|exists:puskesmas,id',
            'status'           => 'required|in:Menunggu Hasil,Selesai,Perlu Pemeriksaan Ulang',
            'laboratory_notes' => 'nullable|string',
        ], [
            'patient_id.required'       => 'Pasien wajib dipilih.',
            'examination_date.required' => 'Tanggal pemeriksaan wajib diisi.',
            'examination_type.required' => 'Jenis pemeriksaan wajib dipilih.',
            'result.required'           => 'Hasil pemeriksaan wajib dipilih.',
            'diagnosis.required'        => 'Diagnosis klinis wajib diisi.',
        ]);

        $code = 'EXAM-' . Carbon::parse($request->examination_date)->format('Ym') . '-' . str_pad(ClinicalExamination::count() + 1, 4, '0', STR_PAD_LEFT);

        $exam = ClinicalExamination::create([
            'examination_code'  => $code,
            'patient_id'        => $request->patient_id,
            'puskesmas_id'      => $request->puskesmas_id,
            'examination_date'  => $request->examination_date,
            'examination_type'  => $request->examination_type,
            'result'            => $request->result,
            'diagnosis'         => $request->diagnosis,
            'status'            => $request->status,
            'laboratory_notes'  => $request->laboratory_notes,
        ]);

        ActivityLog::log('Tambah Pemeriksaan TB', 'Pemeriksaan', "Mencatat hasil pemeriksaan {$exam->examination_type} kode {$code}.");

        return redirect()->route('admin.examinations.index')->with('success', 'Hasil pemeriksaan laboratorium berhasil disimpan.');
    }

    public function show($id)
    {
        $examination = ClinicalExamination::with(['patient.user', 'puskesmas'])->findOrFail($id);
        return redirect()->route('admin.examinations.edit', $examination->id);
    }

    public function edit($id)
    {
        $examination = ClinicalExamination::with('patient.user')->findOrFail($id);
        $patients = Patient::with('user')->get();
        $puskesmas = Puskesmas::orderBy('name')->get();

        return view('admin.examinations.form', compact('examination', 'patients', 'puskesmas'))->with([
            'title'        => 'Edit Pemeriksaan: ' . $examination->examination_code,
            'pageTitle'    => 'Edit Pemeriksaan Laboratorium',
            'pageSubtitle' => 'Perbarui rincian jenis pemeriksaan, hasil uji, dan diagnosis.',
            'isEdit'       => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $examination = ClinicalExamination::findOrFail($id);

        $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'examination_date' => 'required|date',
            'examination_type' => 'required|string',
            'result'           => 'required|string',
            'diagnosis'        => 'required|string',
            'puskesmas_id'     => 'nullable|exists:puskesmas,id',
            'status'           => 'required|in:Menunggu Hasil,Selesai,Perlu Pemeriksaan Ulang',
            'laboratory_notes' => 'nullable|string',
        ]);

        $examination->update([
            'patient_id'       => $request->patient_id,
            'puskesmas_id'     => $request->puskesmas_id,
            'examination_date' => $request->examination_date,
            'examination_type' => $request->examination_type,
            'result'           => $request->result,
            'diagnosis'        => $request->diagnosis,
            'status'           => $request->status,
            'laboratory_notes' => $request->laboratory_notes,
        ]);

        ActivityLog::log('Perbarui Pemeriksaan TB', 'Pemeriksaan', "Memperbarui data pemeriksaan {$examination->examination_code}.");

        return redirect()->route('admin.examinations.index')->with('success', 'Data pemeriksaan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $examination = ClinicalExamination::findOrFail($id);
        $code = $examination->examination_code;
        $examination->delete();

        ActivityLog::log('Hapus Pemeriksaan TB', 'Pemeriksaan', "Menghapus data pemeriksaan {$code}.");

        return redirect()->route('admin.examinations.index')->with('success', "Pemeriksaan {$code} berhasil dihapus.");
    }
}
