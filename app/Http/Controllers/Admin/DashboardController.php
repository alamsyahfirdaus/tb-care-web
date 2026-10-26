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
        $user = auth()->user();

        // 1. Info Box Metrics from actual database
        $totalPengguna = $user->user_type_id == 1
            ? User::count()
            : User::whereHas('patient', fn($q) => $q->accessibleBy($user))->count();
        $totalPasien = Patient::accessibleBy($user)->count();
        $totalSkrining = Screening::accessibleBy($user)->count();
        $risikoRendah = Screening::accessibleBy($user)->where('risk_level', 'Risiko Rendah')->count();
        $risikoSedang = Screening::accessibleBy($user)->where('risk_level', 'Risiko Sedang')->count();
        $risikoTinggi = Screening::accessibleBy($user)->where('risk_level', 'Risiko Tinggi')->count();
        $totalPuskesmas = Puskesmas::accessibleBy($user)->count();
        $totalEdukasi = EducationalMaterial::count();

        // Additional clinical metrics
        $pasienAktif = PatientTreatment::accessibleBy($user)->where('treatment_status', 'Berjalan')->count();
        $pasienSelesai = PatientTreatment::accessibleBy($user)->whereIn('treatment_status', ['Selesai', 'Sembuh', 'Pengobatan Lengkap'])->count();
        $totalMinumObat = MedicationRecord::accessibleBy($user)->count();
        
        // Kepatuhan kohort berbasis dosis jatuh tempo aktual (Eager loaded to eliminate N+1 queries)
        $accessibleTreatments = PatientTreatment::accessibleBy($user)->with(['medicationRecords'])->get();
        $adherenceCohort = PatientTreatment::calculateCohortAdherence($accessibleTreatments);
        $tingkatKepatuhan = $adherenceCohort['percentage'];
        $perluTindakLanjut = Screening::accessibleBy($user)->where('status', 'Perlu Tindak Lanjut')->count();

        // 2. Chart 1: Status / Risiko Skrining (Doughnut / Pie Chart)
        $chartRisk = [
            'labels' => ['Risiko Rendah', 'Risiko Sedang', 'Risiko Tinggi'],
            'data'   => [$risikoRendah, $risikoSedang, $risikoTinggi],
            'colors' => ['#28a745', '#ffc107', '#dc3545'],
        ];

        // 3. Chart 2: Tren Skrining Bulanan (6 bulan kalender kontinu dengan zero-fill untuk bulan tanpa data)
        $trendLabels   = [];
        $trendTotals   = [];
        $trendHighRisk = [];

        // Agregasi skrining 6 bulan terakhir
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $monthlyTrendData = Screening::accessibleBy($user)
            ->where(function ($q) use ($sixMonthsAgo) {
                $q->where('screened_at', '>=', $sixMonthsAgo)
                  ->orWhere('created_at', '>=', $sixMonthsAgo);
            })
            ->get();

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $trendLabels[] = $monthDate->translatedFormat('M Y');

            $monthScreenings = $monthlyTrendData->filter(function ($s) use ($monthKey) {
                $dateStr = $s->screened_at ? Carbon::parse($s->screened_at)->format('Y-m') : Carbon::parse($s->created_at)->format('Y-m');
                return $dateStr === $monthKey;
            });

            $trendTotals[] = $monthScreenings->count();
            $trendHighRisk[] = $monthScreenings->filter(fn($s) => in_array($s->risk_level, ['Risiko Tinggi', 'tinggi']))->count();
        }

        $chartTrend = [
            'labels'   => $trendLabels,
            'total'    => $trendTotals,
            'highRisk' => $trendHighRisk,
        ];

        // 4. Chart 3: Distribusi Wilayah (Pasien dan Skrining per Kecamatan)
        $subdistrictStats = Subdistrict::accessibleBy($user)->withCount([
                'patients' => fn($q) => $q->accessibleBy($user),
                'screenings' => fn($q) => $q->accessibleBy($user),
            ])
            ->orderByDesc('screenings_count')
            ->limit(7)
            ->get();

        $chartDistribusi = [
            'labels'     => $subdistrictStats->pluck('name')->toArray(),
            'screenings' => $subdistrictStats->pluck('screenings_count')->toArray(),
            'patients'   => $subdistrictStats->pluck('patients_count')->toArray(),
        ];

        // 5. Chart 4: Jenis Kelamin (Pasien & Skrining)
        $genderMale = Screening::accessibleBy($user)->where('gender', 'L')->count();
        $genderFemale = Screening::accessibleBy($user)->where('gender', 'P')->count();

        $chartGender = [
            'labels' => ['Laki-laki', 'Perempuan'],
            'data'   => [$genderMale, $genderFemale],
            'colors' => ['#1b75bb', '#e83e8c'],
        ];

        // 6. Recent Screenings & Activities
        $latestScreenings = Screening::accessibleBy($user)->with(['puskesmas', 'subdistrict'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // 6. Recent Screenings & Activities (Petugas hanya melihat log aktivitasnya sendiri)
        $recentActivities = $user->isSuperAdmin()
            ? ActivityLog::orderByDesc('id')->limit(6)->get()
            : ActivityLog::where('user_id', $user->id)->orderByDesc('id')->limit(6)->get();

        $pageTitle = 'Dashboard ' . $user->role_display_name;
        $pageSubtitle = $user->officer && $user->officer->scope_description
            ? 'Cakupan Wilayah Tugas: ' . $user->officer->scope_description
            : 'Ringkasan data klinis, tren skrining, dan kepatuhan pengobatan pasien secara real-time.';

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
            'title'        => $pageTitle,
            'pageTitle'    => $pageTitle,
            'pageSubtitle' => $pageSubtitle
        ]);
    }
}
