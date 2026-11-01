<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Officer;
use App\Models\KaderArea;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Screening;
use App\Models\CloseContact;
use App\Models\ActivityLog;
use App\Support\EncryptedId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KaderController extends Controller
{
    /**
     * Pastikan pengguna terotentikasi dan memiliki hak akses manajemen kader.
     * Hanya Super Administrator (user_type_id = 1) dan PJTB Puskesmas (user_type_id = 3, officer_type_id = 3)
     * yang diizinkan mengakses modul ini. Pasien, Kader, dan peran lain ditolak (403).
     */
    protected function authorizeAccess(): User
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (!$user->isSuperAdmin() && !$user->isPJTB()) {
            abort(403, 'Akses ditolak. Modul Manajemen Kader hanya dapat diakses oleh Super Administrator dan Petugas Penanggung Jawab TB (PJTB) Puskesmas.');
        }

        return $user;
    }

    /**
     * Resolusi ID Kader (mendukung ID numerik maupun string terenkripsi).
     */
    protected function resolveKaderId($id): int
    {
        if (is_numeric($id)) {
            return (int) $id;
        }

        return EncryptedId::decrypt($id);
    }

    /**
     * Validasi dan otorisasi akses terhadap record Kader tertentu.
     * PJTB hanya dapat mengelola Kader pada Puskesmas yang sama dengan wilayah tugasnya.
     */
    protected function authorizeKaderAccess(User $currentUser, $id): User
    {
        $kaderId = $this->resolveKaderId($id);

        $kader = User::with(['officer.puskesmas', 'officer.kaderAreas.village', 'officer.kaderAreas.subdistrict'])
            ->where('user_type_id', 3)
            ->whereHas('officer', function ($q) {
                $q->where('officer_type_id', 4);
            })
            ->findOrFail($kaderId);

        // Jika PJTB, periksa puskesmas_id
        if ($currentUser->isPJTB()) {
            $pjtbPuskesmasId = optional($currentUser->officer)->puskesmas_id;
            $kaderPuskesmasId = optional($kader->officer)->puskesmas_id;

            if (!$pjtbPuskesmasId || $pjtbPuskesmasId !== $kaderPuskesmasId) {
                abort(403, 'Anda tidak memiliki hak akses untuk mengelola Kader di luar wilayah kerja Puskesmas Anda.');
            }
        }

        return $kader;
    }

    /**
     * Halaman daftar kader binaan.
     */
    public function index(Request $request)
    {
        $currentUser = $this->authorizeAccess();

        $query = User::with([
            'officer.puskesmas',
            'officer.district',
            'officer.kaderAreas.village',
            'officer.kaderAreas.subdistrict'
        ])
        ->where('user_type_id', 3)
        ->whereHas('officer', function ($q) {
            $q->where('officer_type_id', 4);
        });

        // 1. Scoping kewenangan Puskesmas
        $currentPuskesmas = null;
        if ($currentUser->isPJTB()) {
            $currentPuskesmas = $currentUser->officer->puskesmas;
            $puskesmasId = optional($currentPuskesmas)->id;
            $query->whereHas('officer', function ($q) use ($puskesmasId) {
                $q->where('puskesmas_id', $puskesmasId);
            });
        } elseif ($currentUser->isSuperAdmin()) {
            if ($request->filled('puskesmas_id')) {
                $query->whereHas('officer', function ($q) use ($request) {
                    $q->where('puskesmas_id', $request->puskesmas_id);
                });
            }
        }

        // 2. Clone query untuk ringkasan metrik statistik sebelum filter spesifik diaplikasikan
        $metricsQuery = clone $query;
        $totalKader = $metricsQuery->count();
        $activeKader = (clone $metricsQuery)->where('is_active', 1)->count();
        $inactiveKader = (clone $metricsQuery)->where('is_active', 0)->count();

        // 3. Filter berdasarkan Status Akun
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        // 4. Filter berdasarkan Desa/Kelurahan Binaan
        if ($request->filled('village_id')) {
            $query->whereHas('officer.kaderAreas', function ($q) use ($request) {
                $q->where('village_id', $request->village_id);
            });
        }

        // 5. Filter Pencarian Nama / Username / Kontak
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('username', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        // 6. Urutan dan Paginasi
        $kaders = $query->orderByDesc('id')->paginate(15)->withQueryString();

        // 7. Master data untuk dropdown filter
        if ($currentUser->isPJTB() && $currentPuskesmas) {
            $villages = Village::where('subdistrict_id', $currentPuskesmas->subdistrict_id)
                ->orderBy('name')
                ->get();
            $puskesmasList = collect([$currentPuskesmas]);
        } else {
            $villages = Village::orderBy('name')->get();
            $puskesmasList = Puskesmas::orderBy('name')->get();
        }

        return view('admin.kaders.index', compact(
            'kaders',
            'currentUser',
            'currentPuskesmas',
            'villages',
            'puskesmasList',
            'totalKader',
            'activeKader',
            'inactiveKader'
        ))->with([
            'title'        => 'Manajemen Kader',
            'pageTitle'    => 'Manajemen Kader Kesehatan TB',
            'pageSubtitle' => $currentUser->isPJTB()
                ? 'Kelola akun kader, penugasan wilayah binaan desa/kelurahan, dan pemantauan di wilayah kerja ' . ($currentPuskesmas->name ?? 'Puskesmas')
                : 'Kelola seluruh kader kesehatan pendamping pasien TB lintas fasilitas pelayanan kesehatan.'
        ]);
    }

    /**
     * Form penambahan kader baru.
     */
    public function create()
    {
        $currentUser = $this->authorizeAccess();
        $isEdit = false;
        $kader = new User();
        $assignedVillageIds = [];

        if ($currentUser->isPJTB()) {
            $puskesmas = $currentUser->officer->puskesmas;
            if (!$puskesmas) {
                return redirect()->route('dashboard')->with('error', 'Akun PJTB Anda belum ditautkan ke Puskesmas mana pun.');
            }
            $puskesmasList = collect([$puskesmas]);
            $subdistricts = Subdistrict::where('district_id', optional($puskesmas->subdistrict)->district_id ?? 0)
                ->orderBy('name')
                ->get();
            $villages = Village::where('subdistrict_id', $puskesmas->subdistrict_id)
                ->orderBy('name')
                ->get();
        } else {
            $puskesmasList = Puskesmas::orderBy('name')->get();
            $subdistricts = Subdistrict::orderBy('name')->get();
            $villages = Village::orderBy('name')->get();
        }

        return view('admin.kaders.form', compact(
            'kader',
            'isEdit',
            'currentUser',
            'puskesmasList',
            'subdistricts',
            'villages',
            'assignedVillageIds'
        ))->with([
            'title'        => 'Tambah Kader Baru',
            'pageTitle'    => 'Registrasi Kader Kesehatan TB',
            'pageSubtitle' => 'Buat akun kader baru dan tetapkan desa/kelurahan binaan untuk pendampingan pasien TB.'
        ]);
    }

    /**
     * Simpan kader baru ke database.
     */
    public function store(Request $request)
    {
        $currentUser = $this->authorizeAccess();

        $rules = [
            'name'           => 'required|string|max:255',
            'username'       => 'required|string|max:50|unique:users,username',
            'email'          => 'nullable|email|max:100|unique:users,email',
            'phone'          => 'nullable|string|max:20',
            'gender'         => 'required|in:L,P',
            'place_of_birth' => 'nullable|string|max:100',
            'date_of_birth'  => 'nullable|date',
            'password'       => 'required|string|min:6|confirmed',
            'is_active'      => 'required|in:0,1',
            'village_ids'    => 'nullable|array',
            'village_ids.*'  => 'exists:villages,id',
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['puskesmas_id'] = 'required|exists:puskesmas,id';
        }

        $messages = [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'username.required'  => 'Username akun wajib diisi.',
            'username.unique'    => 'Username sudah terdaftar pada sistem TB Care.',
            'email.email'        => 'Format alamat email tidak valid.',
            'email.unique'       => 'Email sudah digunakan oleh akun lain.',
            'gender.required'    => 'Jenis kelamin wajib dipilih.',
            'password.required'  => 'Kata sandi awal wajib diisi.',
            'password.min'       => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'puskesmas_id.required' => 'Puskesmas penugasan wajib dipilih.',
        ];

        $request->validate($rules, $messages);

        // Tentukan Puskesmas penugasan secara aman (PJTB dipaksa Puskesmas miliknya)
        if ($currentUser->isPJTB()) {
            $puskesmasId = $currentUser->officer->puskesmas_id;
        } else {
            $puskesmasId = (int) $request->puskesmas_id;
        }

        $puskesmas = Puskesmas::with('subdistrict')->findOrFail($puskesmasId);
        $districtId = optional($puskesmas->subdistrict)->district_id;

        return DB::transaction(function () use ($request, $currentUser, $puskesmas, $districtId) {
            // 1. Buat User (user_type_id = 3 / Petugas)
            $user = User::create([
                'name'           => $request->name,
                'username'       => $request->username,
                'email'          => $request->email,
                'phone'          => $request->phone,
                'gender'         => $request->gender,
                'place_of_birth' => $request->place_of_birth,
                'date_of_birth'  => $request->date_of_birth,
                'password'       => Hash::make($request->password),
                'user_type_id'   => 3,
                'is_active'      => (int) $request->is_active,
            ]);

            // 2. Buat Officer (officer_type_id = 4 / Kader Puskesmas)
            $officer = Officer::create([
                'user_id'         => $user->id,
                'officer_type_id' => 4,
                'puskesmas_id'    => $puskesmas->id,
                'district_id'     => $districtId,
            ]);

            // 3. Simpan Desa/Kelurahan Binaan
            $villageCount = 0;
            if ($request->filled('village_ids') && is_array($request->village_ids)) {
                $uniqueVillages = array_unique($request->village_ids);
                foreach ($uniqueVillages as $villageId) {
                    $village = Village::find($villageId);
                    if ($village) {
                        KaderArea::create([
                            'officer_id'     => $officer->id,
                            'subdistrict_id' => $village->subdistrict_id,
                            'village_id'     => $village->id,
                        ]);
                        $villageCount++;
                    }
                }
            }

            // 4. Catat Log Aktivitas
            $areaInfo = $villageCount > 0 ? " dengan {$villageCount} desa/kelurahan binaan" : " tanpa wilayah binaan awal";
            ActivityLog::log(
                'Tambah Kader Baru',
                'Petugas',
                "Menambahkan akun Kader {$user->name} (@{$user->username}) pada Puskesmas {$puskesmas->name}{$areaInfo}."
            );

            return redirect()->route('kaders.show', $user->encrypted_id)
                ->with('success', "Akun Kader {$user->name} berhasil didaftarkan untuk Puskesmas {$puskesmas->name}.");
        });
    }

    /**
     * Detail kader, profil akun, wilayah binaan, dan metrik pendampingan.
     */
    public function show($id)
    {
        $currentUser = $this->authorizeAccess();
        $kader = $this->authorizeKaderAccess($currentUser, $id);

        $kader->load([
            'officer.puskesmas.subdistrict.district',
            'officer.kaderAreas.subdistrict',
            'officer.kaderAreas.village',
            'activityLogs' => function ($q) {
                $q->orderByDesc('id')->limit(10);
            }
        ]);

        $puskesmas = $kader->officer->puskesmas;
        $assignedVillageIds = $kader->officer->kaderAreas->pluck('village_id')->filter()->unique();

        // Metrik wilayah binaan kader
        $totalPatientsInArea = 0;
        $activeTreatmentsInArea = 0;
        $closeContactsInArea = 0;

        if ($assignedVillageIds->isNotEmpty()) {
            $totalPatientsInArea = Patient::whereIn('village_id', $assignedVillageIds)->count();
            $activeTreatmentsInArea = PatientTreatment::whereHas('patient', function ($q) use ($assignedVillageIds) {
                $q->whereIn('village_id', $assignedVillageIds);
            })->where('treatment_status', 'Berjalan')->count();
            $closeContactsInArea = CloseContact::whereHas('patient', function ($q) use ($assignedVillageIds) {
                $q->whereIn('village_id', $assignedVillageIds);
            })->count();
        }

        // Aktivitas skrining yang dilakukan oleh kader ini secara langsung
        $screeningsRecorded = Screening::where('user_id', $kader->id)->count();

        // Data untuk modal tambah wilayah binaan
        $subdistricts = Subdistrict::where('district_id', optional($puskesmas->subdistrict)->district_id ?? 0)
            ->orderBy('name')
            ->get();
        $availableVillages = Village::where('subdistrict_id', optional($puskesmas)->subdistrict_id ?? 0)
            ->orderBy('name')
            ->get();

        return view('admin.kaders.show', compact(
            'kader',
            'currentUser',
            'puskesmas',
            'assignedVillageIds',
            'totalPatientsInArea',
            'activeTreatmentsInArea',
            'closeContactsInArea',
            'screeningsRecorded',
            'subdistricts',
            'availableVillages'
        ))->with([
            'title'        => 'Detail Kader: ' . $kader->name,
            'pageTitle'    => 'Profil & Wilayah Binaan Kader',
            'pageSubtitle' => 'Informasi identitas akun, status mobile, desa binaan, dan ringkasan pemantauan pasien.'
        ]);
    }

    /**
     * Form edit kader.
     */
    public function edit($id)
    {
        $currentUser = $this->authorizeAccess();
        $kader = $this->authorizeKaderAccess($currentUser, $id);
        $isEdit = true;

        $kader->load(['officer.puskesmas', 'officer.kaderAreas']);
        $assignedVillageIds = $kader->officer->kaderAreas->pluck('village_id')->toArray();

        if ($currentUser->isPJTB()) {
            $puskesmas = $currentUser->officer->puskesmas;
            $puskesmasList = collect([$puskesmas]);
            $subdistricts = Subdistrict::where('district_id', optional($puskesmas->subdistrict)->district_id ?? 0)
                ->orderBy('name')
                ->get();
            $villages = Village::where('subdistrict_id', $puskesmas->subdistrict_id)
                ->orderBy('name')
                ->get();
        } else {
            $puskesmasList = Puskesmas::orderBy('name')->get();
            $subdistricts = Subdistrict::orderBy('name')->get();
            $villages = Village::orderBy('name')->get();
        }

        return view('admin.kaders.form', compact(
            'kader',
            'isEdit',
            'currentUser',
            'puskesmasList',
            'subdistricts',
            'villages',
            'assignedVillageIds'
        ))->with([
            'title'        => 'Edit Kader: ' . $kader->name,
            'pageTitle'    => 'Perbarui Data Akun Kader',
            'pageSubtitle' => 'Ubah identitas kader, atur ulang desa binaan, atau reset kata sandi.'
        ]);
    }

    /**
     * Simpan pembaruan kader ke database.
     */
    public function update(Request $request, $id)
    {
        $currentUser = $this->authorizeAccess();
        $kader = $this->authorizeKaderAccess($currentUser, $id);

        $rules = [
            'name'           => 'required|string|max:255',
            'username'       => ['required', 'string', 'max:50', Rule::unique('users')->ignore($kader->id)],
            'email'          => ['nullable', 'email', 'max:100', Rule::unique('users')->ignore($kader->id)],
            'phone'          => 'nullable|string|max:20',
            'gender'         => 'required|in:L,P',
            'place_of_birth' => 'nullable|string|max:100',
            'date_of_birth'  => 'nullable|date',
            'password'       => 'nullable|string|min:6|confirmed',
            'is_active'      => 'required|in:0,1',
            'village_ids'    => 'nullable|array',
            'village_ids.*'  => 'exists:villages,id',
        ];

        if ($currentUser->isSuperAdmin()) {
            $rules['puskesmas_id'] = 'required|exists:puskesmas,id';
        }

        $messages = [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'username.required'  => 'Username akun wajib diisi.',
            'username.unique'    => 'Username sudah terdaftar pada sistem TB Care.',
            'email.email'        => 'Format alamat email tidak valid.',
            'email.unique'       => 'Email sudah digunakan oleh akun lain.',
            'gender.required'    => 'Jenis kelamin wajib dipilih.',
            'password.min'       => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'puskesmas_id.required' => 'Puskesmas penugasan wajib dipilih.',
        ];

        $request->validate($rules, $messages);

        return DB::transaction(function () use ($request, $currentUser, $kader) {
            $wasActive = (bool) $kader->is_active;
            $newActive = (int) $request->is_active;

            $updateData = [
                'name'           => $request->name,
                'username'       => $request->username,
                'email'          => $request->email,
                'phone'          => $request->phone,
                'gender'         => $request->gender,
                'place_of_birth' => $request->place_of_birth,
                'date_of_birth'  => $request->date_of_birth,
                'is_active'      => $newActive,
            ];

            $passwordChanged = false;
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
                $passwordChanged = true;
            }

            $kader->update($updateData);

            // Jika akun dinonaktifkan atau password direset, cabut token mobile Sanctum
            if (($wasActive && !$newActive) || $passwordChanged) {
                if (method_exists($kader, 'tokens')) {
                    $kader->tokens()->delete();
                }
            }

            // Super Admin dapat memindahkan puskesmas
            if ($currentUser->isSuperAdmin() && $request->filled('puskesmas_id')) {
                $puskesmas = Puskesmas::with('subdistrict')->findOrFail($request->puskesmas_id);
                $kader->officer->update([
                    'puskesmas_id' => $puskesmas->id,
                    'district_id'  => optional($puskesmas->subdistrict)->district_id,
                ]);
            }

            // Sinkronisasi wilayah desa/kelurahan binaan
            if ($request->has('village_ids')) {
                $newVillageIds = is_array($request->village_ids) ? array_unique($request->village_ids) : [];

                // Hapus desa yang tidak dipilih lagi
                $kader->officer->kaderAreas()->whereNotIn('village_id', $newVillageIds)->delete();

                // Tambahkan desa baru yang belum terdaftar
                $existingVillageIds = $kader->officer->kaderAreas()->pluck('village_id')->toArray();
                foreach ($newVillageIds as $villageId) {
                    if (!in_array($villageId, $existingVillageIds)) {
                        $village = Village::find($villageId);
                        if ($village) {
                            KaderArea::create([
                                'officer_id'     => $kader->officer->id,
                                'subdistrict_id' => $village->subdistrict_id,
                                'village_id'     => $village->id,
                            ]);
                        }
                    }
                }
            }

            ActivityLog::log(
                'Perbarui Data Kader',
                'Petugas',
                "Memperbarui profil dan konfigurasi wilayah binaan Kader {$kader->name} (@{$kader->username})."
            );

            return redirect()->route('kaders.show', $kader->encrypted_id)
                ->with('success', "Data akun Kader {$kader->name} berhasil diperbarui.");
        });
    }

    /**
     * Aktifkan atau nonaktifkan akun kader.
     */
    public function toggleStatus($id)
    {
        $currentUser = $this->authorizeAccess();
        $kader = $this->authorizeKaderAccess($currentUser, $id);

        $newStatus = !$kader->is_active;
        $kader->is_active = $newStatus;
        $kader->save();

        if (!$newStatus) {
            // Cabut seluruh token Sanctum agar sesi mobile langsung terputus seketika
            if (method_exists($kader, 'tokens')) {
                $kader->tokens()->delete();
            }

            ActivityLog::log(
                'Nonaktifkan Akun Kader',
                'Petugas',
                "Menonaktifkan akun Kader {$kader->name} (@{$kader->username}). Token akses TB Care Mobile telah dicabut."
            );

            return redirect()->back()->with('success', "Akun Kader {$kader->name} dinonaktifkan. Sesi dan token mobile telah dicabut.");
        } else {
            ActivityLog::log(
                'Aktifkan Akun Kader',
                'Petugas',
                "Mengaktifkan kembali akun Kader {$kader->name} (@{$kader->username})."
            );

            return redirect()->back()->with('success', "Akun Kader {$kader->name} berhasil diaktifkan kembali.");
        }
    }

    /**
     * Hapus akun kader beserta relasi penugasannya.
     */
    public function destroy($id)
    {
        $currentUser = $this->authorizeAccess();
        $kader = $this->authorizeKaderAccess($currentUser, $id);

        $name = $kader->name;
        $username = $kader->username;

        DB::transaction(function () use ($kader) {
            // 1. Cabut token mobile
            if (method_exists($kader, 'tokens')) {
                $kader->tokens()->delete();
            }

            // 2. Hapus wilayah binaan dan record officer
            if ($kader->officer) {
                if (method_exists($kader->officer, 'kaderAreas')) {
                    $kader->officer->kaderAreas()->delete();
                }
                $kader->officer->delete();
            }

            // 3. Lepaskan tautan screening agar histori medis tetap utuh
            Screening::where('user_id', $kader->id)->update(['user_id' => null]);

            // 4. Hapus record user
            $kader->delete();
        });

        ActivityLog::log(
            'Hapus Akun Kader',
            'Petugas',
            "Menghapus akun Kader {$name} (@{$username})."
        );

        return redirect()->route('kaders.index')
            ->with('success', "Akun Kader {$name} berhasil dihapus.");
    }

    /**
     * Ekspor data kader ke file format CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $currentUser = $this->authorizeAccess();

        $query = User::with([
            'officer.puskesmas',
            'officer.kaderAreas.village'
        ])
        ->where('user_type_id', 3)
        ->whereHas('officer', function ($q) {
            $q->where('officer_type_id', 4);
        });

        if ($currentUser->isPJTB()) {
            $puskesmasId = optional($currentUser->officer)->puskesmas_id;
            $query->whereHas('officer', function ($q) use ($puskesmasId) {
                $q->where('puskesmas_id', $puskesmasId);
            });
            $filenamePuskesmas = optional($currentUser->officer->puskesmas)->name ?? 'Puskesmas';
        } else {
            if ($request->filled('puskesmas_id')) {
                $query->whereHas('officer', function ($q) use ($request) {
                    $q->where('puskesmas_id', $request->puskesmas_id);
                });
            }
            $filenamePuskesmas = 'Semua_Wilayah';
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        if ($request->filled('village_id')) {
            $query->whereHas('officer.kaderAreas', function ($q) use ($request) {
                $q->where('village_id', $request->village_id);
            });
        }

        $kaders = $query->orderBy('name')->get();

        $cleanFilename = preg_replace('/[^A-Za-z0-9_\-]/', '_', $filenamePuskesmas);
        $filename = "Daftar_Kader_{$cleanFilename}_" . date('Ymd_His') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($kaders) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM untuk Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header kolom CSV
            fputcsv($handle, [
                'No',
                'Kode Kader',
                'Nama Lengkap',
                'Username',
                'Email',
                'No HP/WA',
                'Jenis Kelamin',
                'Puskesmas Penugasan',
                'Desa / Kelurahan Binaan',
                'Status Akun',
                'Tanggal Terdaftar',
            ]);

            foreach ($kaders as $index => $k) {
                $villagesText = $k->officer->kaderAreas
                    ->map(fn($a) => optional($a->village)->name)
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $kodeKader = 'KDR-' . str_pad($k->officer->id ?? $k->id, 4, '0', STR_PAD_LEFT);

                fputcsv($handle, [
                    $index + 1,
                    $kodeKader,
                    $k->name,
                    $k->username,
                    $k->email ?? '-',
                    $k->phone ?? '-',
                    $k->gender == 'L' ? 'Laki-laki' : 'Perempuan',
                    optional($k->officer->puskesmas)->name ?? '-',
                    $villagesText ?: 'Belum Ada Wilayah',
                    $k->is_active ? 'Aktif' : 'Nonaktif',
                    $k->created_at ? $k->created_at->format('d/m/Y H:i') : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
