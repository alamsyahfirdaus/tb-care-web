<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Screening;
use App\Models\Patient;
use App\Models\Puskesmas;
use App\Models\ClinicalExamination;
use App\Models\PatientTreatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index()
    {
        // 1. Risk Distribution
        $riskRendah = Screening::where('risk_level', 'rendah')->count();
        $riskSedang = Screening::where('risk_level', 'sedang')->count();
        $riskTinggi = Screening::where('risk_level', 'tinggi')->count();
        $totalScreening = Screening::count();

        // 2. Monthly Trend (last 6 months)
        $monthlyTrend = [];
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $monthName = $monthDate->translatedFormat('M Y');
            $months[] = $monthName;

            $count = Screening::where(function($q) use ($monthKey) {
                $q->where('screened_at', 'like', "{$monthKey}%")
                  ->orWhere('created_at', 'like', "{$monthKey}%");
            })->count();

            $monthlyTrend[] = $count;
        }

        // 3. Gender Breakdown
        $screeningMale   = Screening::where('gender', 'male')->count();
        $screeningFemale = Screening::where('gender', 'female')->count();

        // 4. Age Demographics
        $ageAnak       = Screening::where('age', '<', 15)->count();
        $ageRemaja     = Screening::whereBetween('age', [15, 24])->count();
        $ageProduktif  = Screening::whereBetween('age', [25, 54])->count();
        $ageLansia     = Screening::where('age', '>=', 55)->count();

        // 5. Top 5 Puskesmas by Screenings
        $topPuskesmas = Puskesmas::withCount('screenings')
            ->orderByDesc('screenings_count')
            ->limit(5)
            ->get();
        
        $pkmNames = $topPuskesmas->pluck('name')->toArray();
        $pkmCounts = $topPuskesmas->pluck('screenings_count')->toArray();

        // 6. Clinical Examination Results (TCM / BTA)
        $examPositive = ClinicalExamination::where('result', 'like', '%Positif%')->count();
        $examNegative = ClinicalExamination::where('result', 'like', '%Negatif%')->count();
        $examWaiting  = ClinicalExamination::where('result', 'like', '%Menunggu%')->orWhere('status', 'Menunggu Hasil')->count();

        // 7. Treatment Statuses
        $treatmentActive    = PatientTreatment::where('treatment_status', 'Aktif')->count();
        $treatmentCompleted = PatientTreatment::where('treatment_status', 'Selesai')->count();
        $treatmentDropped   = PatientTreatment::where('treatment_status', 'Putus Obat')->count();

        return view('admin.analytics.index', compact(
            'riskRendah', 'riskSedang', 'riskTinggi', 'totalScreening',
            'months', 'monthlyTrend',
            'screeningMale', 'screeningFemale',
            'ageAnak', 'ageRemaja', 'ageProduktif', 'ageLansia',
            'pkmNames', 'pkmCounts',
            'examPositive', 'examNegative', 'examWaiting',
            'treatmentActive', 'treatmentCompleted', 'treatmentDropped'
        ))->with([
            'title'        => 'Analitik Epidemiologi TB Care',
            'pageTitle'    => 'Pusat Analitik & Tren Epidemiologi TB',
            'pageSubtitle' => 'Visualisasi data analitik skrining, risiko wilayah, profil demografi, dan status diagnostik klinis.'
        ]);
    }
}
