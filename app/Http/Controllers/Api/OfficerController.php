<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\KaderArea;
use App\Models\Patient;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfficerController extends Controller
{
    /**
     * GET /api/officer/kader-areas
     * Daftar wilayah binaan kader.
     */
    public function getKaderAreas(Request $request)
    {
        $user = Auth::user();

        if ($user->user_type_id != 3 || !$user->officer) {
            return response()->json([
                'message' => 'Hanya petugas yang memiliki wilayah binaan.',
                'data'    => []
            ], 403);
        }

        $areas = KaderArea::with(['village', 'subdistrict'])
            ->where('officer_id', $user->officer->id)
            ->get()
            ->map(function ($area) {
                return [
                    'id'               => $area->id,
                    'subdistrict_id'   => $area->subdistrict_id,
                    'subdistrict_name' => optional($area->subdistrict)->name,
                    'village_id'       => $area->village_id,
                    'village_name'     => optional($area->village)->name,
                    'rw'               => $area->rw,
                    'rt'               => $area->rt,
                ];
            });

        return response()->json([
            'message' => 'Daftar wilayah binaan kader berhasil diambil.',
            'data'    => $areas
        ]);
    }

    /**
     * GET /api/officer/dashboard
     * Data agregat terpusat untuk Dashboard Beranda Role Petugas (PJTB dan seluruh jabatan di atasnya).
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->user_type_id, [1, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke dashboard petugas.'
            ], 403);
        }

        $officer = $user->officer;
        $officerTypeId = $officer ? (int)$officer->officer_type_id : null;

        // 1. Total Pasien dalam cakupan kewenangan Petugas
        $totalPatients = Patient::accessibleBy($user)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->count();

        // 2. Pengobatan Aktif (status: Berjalan), tanpa double counting pasien dengan multi episode
        $activeTreatments = Patient::accessibleBy($user)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('treatments', function ($q) {
                $q->where('treatment_status', 'Berjalan');
            })
            ->distinct()
            ->count();

        // 3. Pasien Perlu Tindak Lanjut
        $followUpQuery = Patient::accessibleBy($user)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('treatments', function ($q) {
                $q->whereIn('treatment_status', ['perlu_tindak_lanjut', 'Perlu Tindak Lanjut']);
            })
            ->with([
                'user',
                'puskesmas',
                'village',
                'subdistrict',
                'treatments' => function ($q) {
                    $q->whereIn('treatment_status', ['perlu_tindak_lanjut', 'Perlu Tindak Lanjut'])
                        ->orderByDesc('id')
                        ->with('treatmentType');
                }
            ]);

        $totalFollowUpCount = (clone $followUpQuery)->count();

        // Batasi daftar ringkas di dashboard hingga 10 pasien teratas
        $followUpPatients = $followUpQuery
            ->limit(10)
            ->get()
            ->map(function ($patient) {
                $tr = $patient->treatments->first();
                $tTypeName = $tr?->treatmentType 
                    ? $tr->treatmentType->treatment_type
                    : 'Pengobatan TB';

                return [
                    'id'                     => $patient->id,
                    'patient_id'             => $patient->id,
                    'name'                   => optional($patient->user)->name ?? 'Pasien',
                    'nik'                    => $patient->nik,
                    'avatar'                 => strtoupper(substr(trim(optional($patient->user)->name ?? 'P'), 0, 1)),
                    'gender'                 => optional($patient->user)->gender,
                    'puskesmas_name'         => optional($patient->puskesmas)->name ?? '-',
                    'village_name'           => optional($patient->village)->name ?? '-',
                    'treatment_id'           => $tr?->id,
                    'treatment_type'         => $tTypeName,
                    'treatment_status'       => 'Perlu Tindak Lanjut',
                    'treatment_status_label' => 'Perlu Tindak Lanjut',
                    'reason'                 => 'Periode pengobatan berakhir',
                    'start_date'             => $tr?->start_date ? substr($tr->start_date, 0, 10) : null,
                    'end_date'               => $tr?->end_date ? substr($tr->end_date, 0, 10) : null,
                ];
            })
            ->values();

        // 4. Statistik Pasien Berdasarkan Wilayah sesuai hierarki jabatan
        $regionalStats = $this->resolveRegionalStatistics($user, $officer, $officerTypeId);

        $officerScope = $officer?->scope_description ?? ($user->user_type_id == 1 ? 'Wilayah Kerja Admin' : 'Wilayah Kerja');

        $responseData = [
            'officer' => [
                'name'              => $user->name,
                'user_type_id'      => $user->user_type_id,
                'officer_type_id'   => $officerTypeId,
                'officer_type_name' => $officer?->officer_type_name ?? ($user->user_type_id == 1 ? 'Administrator' : 'Petugas'),
                'scope_description' => $officerScope,
            ],
            'total_patients'      => $totalPatients,
            'active_treatments'   => $activeTreatments,
            'follow_up_count'     => $totalFollowUpCount,
            'follow_up_patients'  => $followUpPatients,
            'regional_statistics' => $regionalStats,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard petugas berhasil diambil.',
            'data'    => $responseData
        ]);
    }

    /**
     * Hitung distribusi pasien berdasarkan hierarki wilayah Petugas.
     */
    protected function resolveRegionalStatistics($user, $officer, ?int $officerTypeId): array
    {
        // A. Role PJTB (officer_type_id = 3) -> Tingkat Desa/Kelurahan
        if ($officerTypeId === 3) {
            $puskesmasId = $officer->puskesmas_id;
            $puskesmas = $officer->puskesmas;
            $subdistrictId = $puskesmas?->subdistrict_id;

            $query = DB::table('patients')
                ->join('users', 'patients.user_id', '=', 'users.id')
                ->join('villages', 'patients.village_id', '=', 'villages.id')
                ->where('users.is_active', true)
                ->where('patients.puskesmas_id', $puskesmasId);

            // Filter agar hanya desa dalam cakupan wilayah Puskesmas PJTB
            if ($subdistrictId) {
                $query->where('villages.subdistrict_id', $subdistrictId);
            }

            $raw = $query->select(
                'villages.id',
                DB::raw('TRIM(villages.name) as name'),
                DB::raw('count(patients.id) as patient_count')
            )
            ->groupBy('villages.id', DB::raw('TRIM(villages.name)'))
            ->orderByDesc('patient_count')
            ->orderBy('name', 'asc')
            ->get();

            return [
                'level'       => 'village',
                'level_label' => 'Desa/Kelurahan',
                'title'       => 'Jumlah Pasien Berdasarkan Wilayah',
                'data'        => $raw,
            ];
        }

        // B. Role Dinkes Kab/Kota (officer_type_id = 2) -> Tingkat Kecamatan
        if ($officerTypeId === 2) {
            $districtId = $officer->district_id;

            $raw = DB::table('patients')
                ->join('users', 'patients.user_id', '=', 'users.id')
                ->join('subdistricts', 'patients.subdistrict_id', '=', 'subdistricts.id')
                ->where('users.is_active', true)
                ->where('subdistricts.district_id', $districtId)
                ->select(
                    'subdistricts.id',
                    DB::raw('TRIM(subdistricts.name) as name'),
                    DB::raw('count(patients.id) as patient_count')
                )
                ->groupBy('subdistricts.id', DB::raw('TRIM(subdistricts.name)'))
                ->orderByDesc('patient_count')
                ->orderBy('name', 'asc')
                ->get();

            return [
                'level'       => 'subdistrict',
                'level_label' => 'Kecamatan',
                'title'       => 'Jumlah Pasien Berdasarkan Wilayah',
                'data'        => $raw,
            ];
        }

        // C. Role Dinkes Provinsi (officer_type_id = 1) -> Tingkat Kabupaten/Kota
        if ($officerTypeId === 1) {
            $provinceId = null;
            if ($officer->district_id) {
                $district = District::find($officer->district_id);
                $provinceId = $district ? $district->province_id : null;
            }

            $query = DB::table('patients')
                ->join('users', 'patients.user_id', '=', 'users.id')
                ->join('subdistricts', 'patients.subdistrict_id', '=', 'subdistricts.id')
                ->join('districts', 'subdistricts.district_id', '=', 'districts.id')
                ->where('users.is_active', true);

            if ($provinceId) {
                $query->where('districts.province_id', $provinceId);
            }

            $raw = $query->select(
                'districts.id',
                DB::raw('TRIM(districts.name) as name'),
                DB::raw('count(patients.id) as patient_count')
            )
            ->groupBy('districts.id', DB::raw('TRIM(districts.name)'))
            ->orderByDesc('patient_count')
            ->orderBy('name', 'asc')
            ->get();

            return [
                'level'       => 'district',
                'level_label' => 'Kabupaten/Kota',
                'title'       => 'Jumlah Pasien Berdasarkan Wilayah',
                'data'        => $raw,
            ];
        }

        // D. Role Administrator (user_type_id = 1) / Petugas Umum Lainnya
        // Tampilkan tingkat Kabupaten/Kota jika terdapat lebih dari satu kabupaten, atau Kecamatan jika dalam satu kabupaten
        $districtCounts = DB::table('patients')
            ->join('users', 'patients.user_id', '=', 'users.id')
            ->join('subdistricts', 'patients.subdistrict_id', '=', 'subdistricts.id')
            ->join('districts', 'subdistricts.district_id', '=', 'districts.id')
            ->where('users.is_active', true)
            ->select('districts.id', DB::raw('TRIM(districts.name) as name'), DB::raw('count(patients.id) as patient_count'))
            ->groupBy('districts.id', DB::raw('TRIM(districts.name)'))
            ->orderByDesc('patient_count')
            ->orderBy('name', 'asc')
            ->get();

        return [
            'level'       => 'district',
            'level_label' => 'Kabupaten/Kota',
            'title'       => 'Jumlah Pasien Berdasarkan Wilayah',
            'data'        => $districtCounts,
        ];
    }

    /**
     * GET /api/officer/dashboard/region-patients
     * Daftar pasien dari suatu wilayah yang dipilih pada statistik distribusi (interaktif).
     */
    public function regionPatients(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->user_type_id, [1, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data ini.'
            ], 403);
        }

        $level = trim(strtolower($request->query('level', 'village')));
        $regionId = (int)$request->query('id', 0);

        if ($regionId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'ID wilayah tidak valid.',
                'data'    => []
            ], 400);
        }

        // Siapkan query yang strictly scoped dengan accessibleBy
        $patientsQuery = Patient::accessibleBy($user)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->with([
                'user',
                'puskesmas',
                'village',
                'subdistrict',
                'treatments' => fn($q) => $q->orderByDesc('id')
            ]);

        if ($level === 'village') {
            $patientsQuery->where('village_id', $regionId);
            $regionName = Village::find($regionId)?->name ?? 'Desa/Kelurahan';
        } elseif ($level === 'subdistrict') {
            $patientsQuery->where('subdistrict_id', $regionId);
            $regionName = Subdistrict::find($regionId)?->name ?? 'Kecamatan';
        } elseif ($level === 'district') {
            $patientsQuery->whereHas('subdistrict', fn($q) => $q->where('district_id', $regionId));
            $regionName = District::find($regionId)?->name ?? 'Kabupaten/Kota';
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Tingkat wilayah tidak dikenali.',
                'data'    => []
            ], 400);
        }

        $patients = $patientsQuery->get()->map(function ($patient) {
            $latestTr = $patient->treatments->first();
            $status = $latestTr?->treatment_status ?? 'Belum Mulai';

            return [
                'id'               => $patient->id,
                'name'             => optional($patient->user)->name ?? 'Pasien',
                'nik'              => $patient->nik,
                'puskesmas_name'   => optional($patient->puskesmas)->name ?? '-',
                'village_name'     => optional($patient->village)->name ?? '-',
                'treatment_status' => $status,
                'gender'           => optional($patient->user)->gender,
            ];
        })->sortBy(fn($p) => strtolower($p['name']))->values();

        return response()->json([
            'success'     => true,
            'message'     => 'Daftar pasien berhasil diambil.',
            'region_name' => $regionName,
            'level'       => $level,
            'data'        => $patients
        ]);
    }
}
