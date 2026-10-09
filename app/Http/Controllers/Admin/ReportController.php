<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Screening;
use App\Models\Patient;
use App\Models\User;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $type       = $request->query('type', 'screening'); // screening, patient, regional, user
        $startDate  = $request->query('start_date', Carbon::now()->subMonths(1)->format('Y-m-d'));
        $endDate    = $request->query('end_date', Carbon::now()->format('Y-m-d'));
        $puskesmasId= $request->query('puskesmas_id');
        $riskLevel  = $request->query('risk_level');

        $puskesmasList = Puskesmas::accessibleBy(auth()->user())->orderBy('name')->get();
        $subdistricts  = Subdistrict::accessibleBy(auth()->user())->orderBy('name')->get();

        $data = null;
        $regionalSummary = null;

        if ($type == 'screening') {
            $query = Screening::accessibleBy(auth()->user())->with(['puskesmas', 'subdistrict', 'user'])
                ->where(function($q) use ($startDate, $endDate) {
                    $q->whereBetween('screened_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                      ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                });

            if ($puskesmasId) $query->where('puskesmas_id', $puskesmasId);
            if ($riskLevel) $query->where('risk_level', $riskLevel);

            $data = $query->orderByDesc('id')->get();
        } elseif ($type == 'patient') {
            $query = Patient::accessibleBy(auth()->user())->with(['puskesmas', 'subdistrict', 'user', 'activeTreatment'])
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            if ($puskesmasId) $query->where('puskesmas_id', $puskesmasId);

            $data = $query->orderByDesc('created_at')->get();
        } elseif ($type == 'regional') {
            $regionalSummary = Puskesmas::accessibleBy(auth()->user())->with(['subdistrict.district'])
                ->withCount([
                    'patients' => fn($q) => $q->accessibleBy(auth()->user()),
                    'screenings' => fn($q) => $q->accessibleBy(auth()->user()),
                    'screenings as high_risk_count' => function($q) {
                        $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Tinggi', 'tinggi']);
                    },
                    'screenings as medium_risk_count' => function($q) {
                        $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Sedang', 'sedang']);
                    },
                    'screenings as low_risk_count' => function($q) {
                        $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Rendah', 'rendah']);
                    }
                ])
                ->orderBy('name')
                ->get();
        } else {
            // User report - restricted to Administrator only
            if (auth()->user()->user_type_id !== 1) {
                abort(403, 'Akses ke laporan pengguna hanya diperuntukkan bagi Administrator.');
            }

            $query = User::with(['role', 'patient', 'officer'])
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            $data = $query->orderByDesc('created_at')->get();
        }

        return view('admin.reports.index', compact(
            'type', 'startDate', 'endDate', 'puskesmasId', 'riskLevel',
            'puskesmasList', 'subdistricts', 'data', 'regionalSummary'
        ))->with([
            'title'        => 'Laporan Komprehensif TB Care',
            'pageTitle'    => 'Pusat Laporan & Ekspor Data TB Care',
            'pageSubtitle' => 'Cetak laporan resmi skrining, register pasien, sebaran wilayah, dan statistik periode.'
        ]);
    }

    public function print(Request $request)
    {
        $type       = $request->query('type', 'screening');
        $startDate  = $request->query('start_date', Carbon::now()->subMonths(1)->format('Y-m-d'));
        $endDate    = $request->query('end_date', Carbon::now()->format('Y-m-d'));
        $puskesmasId= $request->query('puskesmas_id');
        $riskLevel  = $request->query('risk_level');

        $pkmSelected = $puskesmasId ? Puskesmas::find($puskesmasId) : null;

        $items = null;
        $regionalSummary = null;

        if ($type == 'screening') {
            $query = Screening::accessibleBy(auth()->user())->with(['puskesmas', 'subdistrict', 'user'])
                ->where(function($q) use ($startDate, $endDate) {
                    $q->whereBetween('screened_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                      ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                });
            if ($puskesmasId) $query->where('puskesmas_id', $puskesmasId);
            if ($riskLevel) $query->where('risk_level', $riskLevel);
            $items = $query->orderBy('id', 'asc')->get();
        } elseif ($type == 'patient') {
            $query = Patient::accessibleBy(auth()->user())->with(['puskesmas', 'subdistrict', 'village', 'user', 'activeTreatment'])
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            if ($puskesmasId) $query->where('puskesmas_id', $puskesmasId);
            $items = $query->orderBy('created_at', 'asc')->get();
        } elseif ($type == 'regional') {
            $regionalSummary = Puskesmas::accessibleBy(auth()->user())->with(['subdistrict.district'])
                ->withCount([
                    'patients' => fn($q) => $q->accessibleBy(auth()->user()),
                    'screenings' => fn($q) => $q->accessibleBy(auth()->user()),
                    'screenings as high_risk_count' => function($q) {
                        $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Tinggi', 'tinggi']);
                    },
                    'screenings as medium_risk_count' => function($q) {
                        $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Sedang', 'sedang']);
                    },
                    'screenings as low_risk_count' => function($q) {
                        $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Rendah', 'rendah']);
                    }
                ])
                ->orderBy('name')
                ->get();
        } else {
            // User report - restricted to Administrator only
            if (auth()->user()->user_type_id !== 1) {
                abort(403, 'Akses ke cetak laporan pengguna hanya diperuntukkan bagi Administrator.');
            }

            $items = User::with(['role', 'patient', 'officer'])
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->orderBy('created_at', 'asc')
                ->get();
        }

        ActivityLog::log('Cetak Laporan', 'Laporan', "Mencetak laporan {$type} periode {$startDate} s.d {$endDate}.");

        return view('admin.reports.print', compact(
            'type', 'startDate', 'endDate', 'pkmSelected', 'riskLevel', 'items', 'regionalSummary'
        ));
    }

    public function exportCsv(Request $request)
    {
        $type       = $request->query('type', 'screening');
        $startDate  = $request->query('start_date', Carbon::now()->subMonths(1)->format('Y-m-d'));
        $endDate    = $request->query('end_date', Carbon::now()->format('Y-m-d'));
        $puskesmasId= $request->query('puskesmas_id');
        $riskLevel  = $request->query('risk_level');

        if ($type === 'user' && auth()->user()->user_type_id !== 1) {
            abort(403, 'Akses ekspor data pengguna hanya diperuntukkan bagi Administrator.');
        }

        $fileName = "Laporan_{$type}_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($type, $startDate, $endDate, $puskesmasId, $riskLevel) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM for Microsoft Excel

            if ($type == 'screening') {
                fputcsv($file, ['No', 'Kode Skrining', 'Nama Pasien', 'NIK', 'Jenis Kelamin', 'Usia', 'Puskesmas', 'Kecamatan', 'Skor', 'Kategori Risiko', 'Status', 'Tanggal Skrining']);
                $query = Screening::accessibleBy(auth()->user())->with(['puskesmas', 'subdistrict', 'user'])
                    ->where(function($q) use ($startDate, $endDate) {
                        $q->whereBetween('screened_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                          ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                    });
                if ($puskesmasId) $query->where('puskesmas_id', $puskesmasId);
                if ($riskLevel) $query->where('risk_level', $riskLevel);
                
                $idx = 1;
                foreach ($query->orderBy('id', 'asc')->cursor() as $row) {
                    fputcsv($file, [
                        $idx++,
                        $row->code,
                        $row->person_name ?? optional($row->user)->name ?? 'Peserta',
                        $row->nik ?? optional($row->user)->nik ?? '-',
                        in_array($row->gender, ['L', 'male']) ? 'Laki-laki' : 'Perempuan',
                        $row->age . ' Thn',
                        $row->puskesmas->name ?? '-',
                        $row->subdistrict->name ?? '-',
                        $row->total_score,
                        strtoupper($row->risk_level),
                        strtoupper($row->status),
                        $row->screened_at ? $row->screened_at->format('Y-m-d') : ($row->created_at ? $row->created_at->format('Y-m-d') : '-')
                    ]);
                }
            } elseif ($type == 'patient') {
                fputcsv($file, ['No', 'No Registrasi TB', 'Nama Pasien', 'NIK', 'JK', 'Usia', 'No HP', 'Puskesmas', 'Desa/Kel', 'Status Pengobatan', 'Tanggal Terdaftar']);
                $query = Patient::accessibleBy(auth()->user())->with(['puskesmas', 'subdistrict', 'village', 'user', 'activeTreatment'])
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                if ($puskesmasId) $query->where('puskesmas_id', $puskesmasId);

                $idx = 1;
                foreach ($query->orderBy('created_at', 'asc')->cursor() as $row) {
                    $u = $row->user;
                    fputcsv($file, [
                        $idx++,
                        $row->patient_number ?? ('TB-' . $row->id),
                        optional($u)->name ?? $row->name ?? 'Pasien',
                        $row->nik ?? '-',
                        optional($u)->gender == 'P' ? 'P' : 'L',
                        optional($u)->date_of_birth ? Carbon::parse($u->date_of_birth)->age . ' Thn' : '-',
                        optional($u)->phone ?? '-',
                        $row->puskesmas->name ?? '-',
                        $row->village->name ?? ($row->subdistrict->name ?? '-'),
                        $row->activeTreatment->treatment_status ?? 'Tidak Dalam Pengobatan',
                        $row->created_at->format('Y-m-d')
                    ]);
                }
            } elseif ($type == 'regional') {
                fputcsv($file, ['No', 'Nama Puskesmas', 'Kecamatan', 'Kabupaten/Kota', 'Total Pasien', 'Total Skrining', 'Risiko Tinggi', 'Risiko Sedang', 'Risiko Rendah']);
                $pkmData = Puskesmas::accessibleBy(auth()->user())->with(['subdistrict.district'])
                    ->withCount([
                        'patients' => fn($q) => $q->accessibleBy(auth()->user()),
                        'screenings' => fn($q) => $q->accessibleBy(auth()->user()),
                        'screenings as high_risk' => function($q) { $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Tinggi', 'tinggi']); },
                        'screenings as med_risk' => function($q) { $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Sedang', 'sedang']); },
                        'screenings as low_risk' => function($q) { $q->accessibleBy(auth()->user())->whereIn('risk_level', ['Risiko Rendah', 'rendah']); },
                    ])->orderBy('name')->get();

                $idx = 1;
                foreach ($pkmData as $pkm) {
                    fputcsv($file, [
                        $idx++,
                        $pkm->name,
                        $pkm->subdistrict->name ?? '-',
                        $pkm->subdistrict->district->name ?? '-',
                        $pkm->patients_count,
                        $pkm->screenings_count,
                        $pkm->high_risk,
                        $pkm->med_risk,
                        $pkm->low_risk,
                    ]);
                }
            }

            fclose($file);
        };

        ActivityLog::log('Ekspor CSV', 'Laporan', "Mengekspor data {$type} ke format CSV/Excel.");

        return response()->stream($callback, 200, $headers);
    }
}
