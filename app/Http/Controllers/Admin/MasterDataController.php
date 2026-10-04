<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScreeningQuestion;
use App\Models\ScreeningCategory;
use App\Models\TreatmentType;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'questions');

        $questions = ScreeningQuestion::with('category')->orderBy('id', 'asc')->get();
        $categories = ScreeningCategory::orderBy('id', 'asc')->get();
        $treatmentTypes = TreatmentType::all();

        return view('admin.master.index', compact('tab', 'questions', 'categories', 'treatmentTypes'))->with([
            'title'        => 'Master Data Sistem TB Care',
            'pageTitle'    => 'Pengelolaan Master Data',
            'pageSubtitle' => 'Kelola pertanyaan instrumen skrining, bobot skor gejala klinis, dan standar regimen pengobatan.'
        ]);
    }

    public function updateQuestion(Request $request, $id)
    {
        $id = decrypt_id($id);
        $question = ScreeningQuestion::findOrFail($id);

        $request->validate([
            'question_text' => 'required|string',
            'is_critical'   => 'nullable|boolean',
        ]);

        $question->update([
            'question'    => $request->question_text ?? $request->question,
            'is_critical' => $request->has('is_critical'),
        ]);

        ActivityLog::log('Edit Pertanyaan Master', 'Master Data', "Memperbarui teks pertanyaan instrumen ID #{$id}.");

        return back()->with('success', 'Pertanyaan skrining berhasil diperbarui.');
    }

    public function storeTreatmentType(Request $request)
    {
        $request->validate([
            'treatment_type'     => 'required|string|max:255',
            'treatment_duration' => 'required|integer|min:1',
            'duration_unit'      => 'required|string',
            'description'        => 'nullable|string',
        ]);

        $tt = TreatmentType::create([
            'treatment_type'     => $request->treatment_type,
            'treatment_duration' => $request->treatment_duration,
            'duration_unit'      => $request->duration_unit,
            'description'        => $request->description,
        ]);

        ActivityLog::log('Tambah Regimen Pengobatan', 'Master Data', "Menambahkan tipe pengobatan: {$tt->treatment_type}.");

        return back()->with('success', 'Tipe pengobatan berhasil ditambahkan.');
    }
}
