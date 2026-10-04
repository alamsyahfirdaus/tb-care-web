<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Screening;
use App\Models\ScreeningAnswer;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\District;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScreeningController extends Controller
{
    public function index(Request $request)
    {
        $riskFilter = $request->query('risk');
        $statusFilter = $request->query('status');
        $puskesmasFilter = $request->query('puskesmas_id');
        $subdistrictFilter = $request->query('subdistrict_id');
        $genderFilter = $request->query('gender');
        $dateStart = $request->query('date_start');
        $dateEnd = $request->query('date_end');
        $keyword = $request->query('q');

        $query = Screening::with(['puskesmas', 'subdistrict', 'village', 'category', 'patient']);

        if ($riskFilter) {
            $query->where('risk_level', $riskFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($puskesmasFilter) {
            $query->where('puskesmas_id', $puskesmasFilter);
        }

        if ($subdistrictFilter) {
            $query->where('subdistrict_id', $subdistrictFilter);
        }

        if ($genderFilter) {
            $query->where('gender', $genderFilter);
        }

        if ($dateStart) {
            $query->whereDate('screened_at', '>=', $dateStart);
        }

        if ($dateEnd) {
            $query->whereDate('screened_at', '<=', $dateEnd);
        }

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                  ->orWhere('person_name', 'like', "%{$keyword}%")
                  ->orWhere('nik', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        $screenings = $query->orderByDesc('id')->get();
        $puskesmasList = Puskesmas::orderBy('name')->get();
        $subdistricts = Subdistrict::orderBy('name')->get();

        $pageTitle = 'Semua Skrining TB';
        if ($riskFilter) {
            $pageTitle = 'Skrining TB: ' . $riskFilter;
        }

        return view('admin.screenings.index', compact(
            'screenings',
            'puskesmasList',
            'subdistricts',
            'riskFilter',
            'statusFilter',
            'puskesmasFilter',
            'subdistrictFilter',
            'genderFilter',
            'dateStart',
            'dateEnd',
            'keyword',
            'pageTitle'
        ))->with([
            'title'        => $pageTitle,
            'pageSubtitle' => 'Pemantauan hasil penapisan mandiri dan faskes untuk deteksi dini gejala Tuberkulosis.'
        ]);
    }

    public function actionNeeded(Request $request)
    {
        $request->merge(['status' => 'Perlu Tindak Lanjut']);
        return $this->index($request);
    }

    public function show($id)
    {
        $id = decrypt_id($id);
        $screening = Screening::with([
            'user',
            'patient.treatments',
            'puskesmas',
            'subdistrict.district.province',
            'village',
            'category',
            'answers.question'
        ])->findOrFail($id);

        // Group answers by group_name ('Faktor Risiko', 'Skrining Gejala')
        $groupedAnswers = $screening->answers->groupBy('group_name');

        return view('admin.screenings.show', compact('screening', 'groupedAnswers'))->with([
            'title'        => 'Detail Skrining: ' . $screening->code,
            'pageTitle'    => 'Hasil Skrining TB: ' . $screening->code,
            'pageSubtitle' => 'Penilaian risiko lengkap, rincian seluruh jawaban pertanyaan, dan rekomendasi medis.'
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $id = decrypt_id($id);
        $request->validate([
            'status' => 'required|in:Perlu Tindak Lanjut,Dalam Pemantauan,Selesai',
            'notes'  => 'nullable|string',
        ]);

        $screening = Screening::findOrFail($id);
        $screening->update([
            'status' => $request->status,
            'notes'  => $request->notes,
        ]);

        ActivityLog::log('Update Status Skrining', 'Skrining TB', "Mengubah status skrining {$screening->code} menjadi {$request->status}.");

        return redirect()->back()->with('success', 'Status tindak lanjut skrining berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $screening = Screening::findOrFail($id);
        $code = $screening->code;

        $screening->answers()->delete();
        $screening->delete();

        ActivityLog::log('Hapus Skrining TB', 'Skrining TB', "Menghapus data skrining {$code}.");

        return redirect()->route('admin.screenings.index')->with('success', "Data skrining {$code} berhasil dihapus.");
    }
}
