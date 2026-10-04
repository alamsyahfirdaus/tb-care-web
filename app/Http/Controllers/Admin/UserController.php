<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\Puskesmas;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Models\KaderArea;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $roleFilter = $request->query('role');
        $statusFilter = $request->query('status');
        $genderFilter = $request->query('gender');
        $keyword = $request->query('q');

        $query = User::with(['userType', 'officer.puskesmas', 'officer.district', 'patient.puskesmas']);

        if ($roleFilter) {
            $query->where('user_type_id', $roleFilter);
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('is_active', $statusFilter);
        }

        if ($genderFilter) {
            $query->where('gender', $genderFilter);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('username', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        $users = $query->orderByDesc('id')->get();
        $userTypes = UserType::all();

        // Subtitle and titles based on active tab
        $activeRoleName = 'Semua Pengguna';
        if ($roleFilter) {
            $matched = $userTypes->firstWhere('id', $roleFilter);
            if ($matched) $activeRoleName = $matched->name;
        }

        return view('admin.users.index', compact('users', 'userTypes', 'roleFilter', 'statusFilter', 'genderFilter', 'keyword', 'activeRoleName'))
            ->with([
                'title'        => 'Manajemen Pengguna',
                'pageTitle'    => 'Pengguna: ' . $activeRoleName,
                'pageSubtitle' => 'Kelola seluruh akun pengguna sistem TB Care baik masyarakat, tenaga kesehatan, maupun administrator.'
            ]);
    }

    public function show($id)
    {
        $id = decrypt_id($id);
        $user = User::with([
            'userType',
            'patient.puskesmas',
            'patient.subdistrict',
            'patient.village',
            'patient.treatments.treatmentType',
            'officer.puskesmas',
            'officer.district',
            'officer.kaderAreas.subdistrict',
            'officer.kaderAreas.village',
            'activityLogs' => function($q) {
                $q->orderByDesc('id')->limit(10);
            }
        ])->findOrFail($id);

        return view('admin.users.show', compact('user'))->with([
            'title'        => 'Detail Pengguna',
            'pageTitle'    => 'Detail Pengguna: ' . $user->name,
            'pageSubtitle' => 'Informasi akun, hak akses, dan relasi peran sistem kesehatan.'
        ]);
    }

    public function form(?string $encryptedId = null)
    {
        $user = null;
        $isEdit = false;

        if ($encryptedId) {
            $id = decrypt_id($encryptedId);
            $user = User::with(['officer', 'patient'])->findOrFail($id);
            $isEdit = true;
        } else {
            $user = new User();
        }

        $userTypes = UserType::all();
        $puskesmas = Puskesmas::orderBy('name')->get();
        $districts = District::orderBy('name')->get();
        $subdistricts = Subdistrict::orderBy('name')->get();

        return view('admin.users.form', compact('user', 'userTypes', 'puskesmas', 'districts', 'subdistricts', 'isEdit'))->with([
            'title'        => $isEdit ? ('Edit Pengguna: ' . $user->name) : 'Tambah Pengguna Baru',
            'pageTitle'    => $isEdit ? ('Edit Pengguna: ' . $user->name) : 'Tambah Akun Pengguna Baru',
            'pageSubtitle' => $isEdit ? 'Perbarui data identitas, peran, dan status akun pengguna.' : 'Buat akun baru untuk Administrator, Pasien, atau Petugas Kesehatan.',
        ]);
    }

    public function create()
    {
        return $this->form();
    }

    public function edit($id)
    {
        return $this->form($id);
    }

    public function save(Request $request, ?string $encryptedId = null)
    {
        $rawId = $request->input('encrypted_id') ?? $encryptedId;
        $user = null;
        $isUpdate = false;

        if ($rawId) {
            $id = decrypt_id($rawId);
            $user = User::findOrFail($id);
            $isUpdate = true;
        }

        $request->validate([
            'name'            => 'required|string|max:255',
            'username'        => ['required', 'string', 'max:50', $isUpdate ? Rule::unique('users')->ignore($user->id) : 'unique:users,username'],
            'email'           => ['nullable', 'email', 'max:100', $isUpdate ? Rule::unique('users')->ignore($user->id) : 'unique:users,email'],
            'phone'           => 'nullable|string|max:20',
            'gender'          => 'required|in:L,P',
            'place_of_birth'  => 'nullable|string|max:100',
            'date_of_birth'   => 'nullable|date',
            'password'        => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'user_type_id'    => 'required|exists:user_types,id',
            'is_active'       => 'required|in:0,1',
            'officer_type_id' => 'nullable|in:1,2,3,4',
            'puskesmas_id'    => 'nullable|exists:puskesmas,id',
            'district_id'     => 'nullable|exists:districts,id',
        ], [
            'name.required'         => 'Nama lengkap wajib diisi.',
            'username.required'     => 'Username wajib diisi.',
            'username.unique'       => 'Username sudah digunakan oleh akun lain.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah terdaftar pada akun lain.',
            'gender.required'       => 'Jenis kelamin wajib dipilih.',
            'password.required'     => 'Kata sandi wajib diisi untuk pengguna baru.',
            'password.min'          => 'Kata sandi minimal 6 karakter.',
            'password.confirmed'    => 'Konfirmasi kata sandi tidak cocok.',
            'user_type_id.required' => 'Peran pengguna wajib dipilih.',
        ]);

        return DB::transaction(function () use ($request, $user, $isUpdate) {
            if ($isUpdate) {
                $updateData = [
                    'name'           => $request->name,
                    'username'       => $request->username,
                    'email'          => $request->email,
                    'phone'          => $request->phone,
                    'gender'         => $request->gender,
                    'place_of_birth' => $request->place_of_birth,
                    'date_of_birth'  => $request->date_of_birth,
                    'user_type_id'   => $request->user_type_id,
                    'is_active'      => $request->is_active,
                ];

                if ($request->filled('password')) {
                    $updateData['password'] = Hash::make($request->password);
                }

                $user->update($updateData);

                if ($user->user_type_id == 3) {
                    Officer::updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'officer_type_id' => $request->officer_type_id ?? 4,
                            'puskesmas_id'    => $request->puskesmas_id,
                            'district_id'     => $request->district_id,
                        ]
                    );
                }

                ActivityLog::log('Perbarui Pengguna', 'Pengguna', "Memperbarui profil akun {$user->name} ({$user->username}).");

                return redirect()->route('admin.users.show', $user)->with('success', 'Data akun pengguna berhasil diperbarui.');
            } else {
                $user = User::create([
                    'name'           => $request->name,
                    'username'       => $request->username,
                    'email'          => $request->email,
                    'phone'          => $request->phone,
                    'gender'         => $request->gender,
                    'place_of_birth' => $request->place_of_birth,
                    'date_of_birth'  => $request->date_of_birth,
                    'password'       => Hash::make($request->password),
                    'user_type_id'   => $request->user_type_id,
                    'is_active'      => $request->is_active,
                ]);

                if ($user->user_type_id == 3) {
                    Officer::create([
                        'user_id'         => $user->id,
                        'officer_type_id' => $request->officer_type_id ?? 4,
                        'puskesmas_id'    => $request->puskesmas_id,
                        'district_id'     => $request->district_id,
                    ]);
                }

                ActivityLog::log('Tambah Pengguna Baru', 'Pengguna', "Membuat akun {$user->name} ({$user->username}) dengan peran ID {$user->user_type_id}.");

                return redirect()->route('admin.users.show', $user)->with('success', 'Akun pengguna berhasil ditambahkan.');
            }
        });
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->save($request, $id);
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $user = User::findOrFail($id);

        if ($user->id == 1 || $user->id == auth()->id()) {
            return redirect()->back()->with('error', 'Akun Administrator utama tidak dapat dihapus.');
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::log('Hapus Pengguna', 'Pengguna', "Menghapus akun pengguna {$name}.");

        return redirect()->route('admin.users.index')->with('success', "Akun pengguna {$name} berhasil dihapus.");
    }
}
