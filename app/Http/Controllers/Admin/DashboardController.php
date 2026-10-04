<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use App\Models\Screening;
use App\Models\Puskesmas;
use App\Models\EducationalMaterial;
use App\Models\PatientTreatment;
use App\Models\MedicationRecord;
use App\Models\Subdistrict;
use App\Models\District;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Info Box Metrics from actual database
        $totalPengguna = User::count();
        $totalPasien = Patient::count();
        $totalSkrining = Screening::count();
        $risikoRendah = Screening::where('risk_level', 'Risiko Rendah')->count();
        $risikoSedang = Screening::where('risk_level', 'Risiko Sedang')->count();
        $risikoTinggi = Screening::where('risk_level', 'Risiko Tinggi')->count();
        $totalPuskesmas = Puskesmas::count();
        $totalEdukasi = EducationalMaterial::count();

        // Additional clinical metrics
        $pasienAktif = PatientTreatment::where('treatment_status', 'Berjalan')->count();
        $pasienSelesai = PatientTreatment::where('treatment_status', 'Selesai')->count();
        $totalMinumObat = MedicationRecord::count();
        $minumTepatWaktu = MedicationRecord::where('late', 0)->count();
        $tingkatKepatuhan = $totalMinumObat > 0 ? round(($minumTepatWaktu / $totalMinumObat) * 100, 1) : 0;
        $perluTindakLanjut = Screening::where('status', 'Perlu Tindak Lanjut')->count();

        // 2. Chart 1: Status / Risiko Skrining (Doughnut / Pie Chart)
        $chartRisk = [
            'labels' => ['Risiko Rendah', 'Risiko Sedang', 'Risiko Tinggi'],
            'data'   => [$risikoRendah, $risikoSedang, $risikoTinggi],
            'colors' => ['#28a745', '#ffc107', '#dc3545'],
        ];

        // 3. Chart 2: Tren Skrining (Harian 7 hari terakhir, Bulanan 6 bulan)
        $monthlyTrend = Screening::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_year'),
                DB::raw('DATE_FORMAT(created_at, "%b %Y") as month_label'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when risk_level = "Risiko Tinggi" then 1 else 0 end) as high_risk')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(6)->startOfMonth())
            ->groupBy('month_year', 'month_label')
            ->orderBy('month_year', 'asc')
            ->get();

        $chartTrend = [
            'labels'   => $monthlyTrend->pluck('month_label')->toArray(),
            'total'    => $monthlyTrend->pluck('total')->toArray(),
            'highRisk' => $monthlyTrend->pluck('high_risk')->toArray(),
        ];

        // 4. Chart 3: Distribusi Wilayah (Pasien dan Skrining per Kecamatan)
        $subdistrictStats = Subdistrict::withCount(['patients', 'screenings'])
            ->orderByDesc('screenings_count')
            ->limit(7)
            ->get();

        $chartDistribusi = [
            'labels'     => $subdistrictStats->pluck('name')->toArray(),
            'screenings' => $subdistrictStats->pluck('screenings_count')->toArray(),
            'patients'   => $subdistrictStats->pluck('patients_count')->toArray(),
        ];

        // 5. Chart 4: Jenis Kelamin (Pasien & Skrining)
        $genderMale = Screening::where('gender', 'L')->count();
        $genderFemale = Screening::where('gender', 'P')->count();

        $chartGender = [
            'labels' => ['Laki-laki', 'Perempuan'],
            'data'   => [$genderMale, $genderFemale],
            'colors' => ['#1b75bb', '#e83e8c'],
        ];

        // 6. Recent Screenings & Activities
        $latestScreenings = Screening::with(['puskesmas', 'subdistrict'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentActivities = ActivityLog::orderByDesc('id')->limit(6)->get();

        return view('admin.dashboard.index', compact(
            'totalPengguna',
            'totalPasien',
            'totalSkrining',
            'risikoRendah',
            'risikoSedang',
            'risikoTinggi',
            'totalPuskesmas',
            'totalEdukasi',
            'pasienAktif',
            'pasienSelesai',
            'tingkatKepatuhan',
            'perluTindakLanjut',
            'chartRisk',
            'chartTrend',
            'chartDistribusi',
            'chartGender',
            'latestScreenings',
            'recentActivities'
        ))->with([
            'title'        => 'Dashboard Utama',
            'pageTitle'    => 'Dashboard Pengawasan TB Care',
            'pageSubtitle' => 'Ringkasan data klinis, tren skrining, dan kepatuhan pengobatan pasien secara real-time.'
        ]);
    }
}
