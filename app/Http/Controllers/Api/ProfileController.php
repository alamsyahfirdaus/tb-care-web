<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Patient;
use App\Models\Province;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        // Ambil data user yang sedang login
        $user = Auth::user();

        if ($user->user_type_id == 2) {
            $user->load([
                'patient.puskesmas.subdistrict.district.province',
                'patient.village.subdistrict.district.province',
                'patient.subdistrict.district.province',
            ]);
        } elseif (in_array($user->user_type_id, [3, 4])) {
            $user->load(['officer.puskesmas', 'officer.district']);
        }

        // Tambahkan URL/path ke gambar profil jika ada
        $user->photo = $user->photo ? $user->photo : null;

        $userData = $user->toArray();
        if ($user->patient) {
            $p = $user->patient;
            $subdistrict = $p->subdistrict ?? $p->village?->subdistrict;
            $district = $subdistrict?->district;
            $province = $district?->province;

            $pusk = $p->puskesmas;
            $puskSub = $pusk?->subdistrict?->name;
            $puskDist = $pusk?->subdistrict?->district?->name;
            $puskFormattedName = $pusk
                ? ($puskSub ? $pusk->name . ' (' . $puskSub . ', ' . $puskDist . ')' : $pusk->name)
                : null;

            $userData['nik'] = $p->nik;
            $userData['puskesmas_id'] = $p->puskesmas_id;
            $userData['puskesmas_name'] = $pusk ? $pusk->name : null;
            $userData['puskesmas_subdistrict_name'] = $puskSub;
            $userData['puskesmas_district_name'] = $puskDist;
            $userData['puskesmas_location'] = ($puskSub && $puskDist)
                ? ($puskSub . ' - ' . $puskDist)
                : ($puskSub ?: ($puskDist ?: null));
            $userData['puskesmas_formatted_name'] = $puskFormattedName;
            $userData['province_id'] = $province ? $province->id : null;
            $userData['province_name'] = $province ? $province->name : null;
            $userData['district_id'] = $district ? $district->id : null;
            $userData['district_name'] = $district ? $district->name : null;
            $userData['subdistrict_id'] = $subdistrict ? $subdistrict->id : null;
            $userData['subdistrict_name'] = $subdistrict ? $subdistrict->name : null;
            $userData['village_id'] = $p->village_id;
            $userData['village_name'] = $p->village ? $p->village->name : null;
            $userData['rt'] = $p->rt;
            $userData['rw'] = $p->rw;
            $userData['address'] = $p->address;
        }

        // Kembalikan data profil dalam format JSON
        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil.',
            'data'    => $userData,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $isPatient = ($user->user_type_id == 2);
        $patient = $isPatient ? ($user->patient ?: Patient::where('user_id', $user->id)->first()) : null;
        $patientId = $patient ? $patient->id : null;

        $rules = [
            'name'           => 'sometimes|required|string|max:255',
            'email'          => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'          => ['sometimes', 'required', 'string', 'min:10', 'max:15', Rule::unique('users', 'phone')->ignore($user->id)],
            'gender'         => 'sometimes|nullable|in:L,P',
            'place_of_birth' => 'sometimes|nullable|string|max:255',
            'date_of_birth'  => 'sometimes|nullable|date|before:today',
            'password'       => 'sometimes|nullable|string|min:6',
            'photo'          => 'sometimes|nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if ($isPatient) {
            $rules['nik']            = ['sometimes', 'required', 'digits:16', Rule::unique('patients', 'nik')->ignore($patientId)];
            $rules['puskesmas_id']   = ['sometimes', 'required', 'exists:puskesmas,id'];
            $rules['village_id']     = ['sometimes', 'required', 'exists:villages,id'];
            $rules['rt']             = ['sometimes', 'required', 'string', 'max:10'];
            $rules['rw']             = ['sometimes', 'required', 'string', 'max:10'];
            $rules['address']        = ['sometimes', 'nullable', 'string', 'max:1000'];
        }

        $messages = [
            'name.required'         => 'Nama lengkap wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah digunakan oleh akun lain.',
            'phone.required'        => 'Nomor telepon wajib diisi.',
            'phone.min'             => 'Nomor telepon minimal 10 digit.',
            'phone.max'             => 'Nomor telepon maksimal 15 digit.',
            'phone.unique'          => 'Nomor telepon sudah digunakan oleh akun lain.',
            'gender.in'             => 'Pilihan jenis kelamin tidak valid.',
            'date_of_birth.before'  => 'Tanggal lahir harus sebelum hari ini.',
            'password.min'          => 'Password baru minimal 6 karakter.',
            'photo.image'           => 'File foto harus berupa gambar.',
            'photo.mimes'           => 'Format foto harus JPG, JPEG, atau PNG.',
            'photo.max'             => 'Ukuran foto maksimal 2 MB.',
            'nik.required'          => 'NIK wajib diisi.',
            'nik.digits'            => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique'            => 'NIK sudah terdaftar pada pasien lain.',
            'puskesmas_id.required' => 'Puskesmas pendamping wajib dipilih.',
            'puskesmas_id.exists'   => 'Puskesmas yang dipilih tidak valid.',
            'village_id.required'   => 'Desa/Kelurahan wajib dipilih.',
            'village_id.exists'     => 'Desa/Kelurahan yang dipilih tidak valid.',
            'rt.required'           => 'RT wajib diisi.',
            'rw.required'           => 'RW wajib diisi.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Hierarchical region cross-validation
        if ($request->filled('village_id') && $request->filled('subdistrict_id')) {
            $villageMatches = Village::where('id', $request->village_id)
                ->where('subdistrict_id', $request->subdistrict_id)
                ->exists();
            if (!$villageMatches) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa/Kelurahan tidak sesuai dengan Kecamatan yang dipilih.',
                    'errors'  => ['village_id' => ['Desa/Kelurahan tidak sesuai dengan Kecamatan yang dipilih.']]
                ], 422);
            }
        }

        if ($request->filled('subdistrict_id') && $request->filled('district_id')) {
            $subdistrictMatches = Subdistrict::where('id', $request->subdistrict_id)
                ->where('district_id', $request->district_id)
                ->exists();
            if (!$subdistrictMatches) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kecamatan tidak sesuai dengan Kabupaten/Kota yang dipilih.',
                    'errors'  => ['subdistrict_id' => ['Kecamatan tidak sesuai dengan Kabupaten/Kota yang dipilih.']]
                ], 422);
            }
        }

        if ($request->filled('district_id') && $request->filled('province_id')) {
            $districtMatches = District::where('id', $request->district_id)
                ->where('province_id', $request->province_id)
                ->exists();
            if (!$districtMatches) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kabupaten/Kota tidak sesuai dengan Provinsi yang dipilih.',
                    'errors'  => ['district_id' => ['Kabupaten/Kota tidak sesuai dengan Provinsi yang dipilih.']]
                ], 422);
            }
        }

        // Update data User
        $userUpdate = [];
        if ($request->has('name')) $userUpdate['name'] = trim($request->input('name'));
        if ($request->has('email')) {
            $email = trim((string)$request->input('email'));
            $userUpdate['email'] = !empty($email) ? $email : null;
        }
        if ($request->has('phone')) $userUpdate['phone'] = trim($request->input('phone'));
        if ($request->has('gender')) $userUpdate['gender'] = $request->input('gender') ?: null;
        if ($request->has('place_of_birth')) $userUpdate['place_of_birth'] = $request->input('place_of_birth') ?: null;
        if ($request->has('date_of_birth')) $userUpdate['date_of_birth'] = $request->input('date_of_birth') ?: null;
        if ($request->filled('password')) {
            $userUpdate['password'] = Hash::make($request->input('password'));
        }

        // === FOTO PROFIL ===
        if ($request->hasFile('photo')) {
            if (!empty($user->photo)) {
                $oldPath = public_path('images/' . $user->photo);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $destinationPath = public_path('images');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $fileName = Str::random(20) . '.' . $request->file('photo')->getClientOriginalExtension();
            $request->file('photo')->move($destinationPath, $fileName);
            $userUpdate['photo'] = $fileName;
        }

        $user->update($userUpdate);

        // Update / Create data Patient jika role Pasien (user_type_id == 2)
        if ($isPatient) {
            $patientUpdate = [];
            if ($request->has('nik')) {
                $nik = trim((string)$request->input('nik'));
                $patientUpdate['nik'] = !empty($nik) ? $nik : null;
            }
            if ($request->has('puskesmas_id')) {
                $patientUpdate['puskesmas_id'] = $request->input('puskesmas_id') ?: null;
            }
            if ($request->has('village_id')) {
                $villageId = $request->input('village_id');
                $patientUpdate['village_id'] = $villageId ?: null;
                // Selalu sinkronkan subdistrict_id langsung dari relasi village
                $village = Village::find($villageId);
                if ($village && $village->subdistrict_id) {
                    $patientUpdate['subdistrict_id'] = $village->subdistrict_id;
                }
            } elseif ($request->has('subdistrict_id')) {
                $patientUpdate['subdistrict_id'] = $request->input('subdistrict_id') ?: null;
            }
            if ($request->has('rt')) {
                $patientUpdate['rt'] = $request->input('rt') !== null ? trim((string)$request->input('rt')) : null;
            }
            if ($request->has('rw')) {
                $patientUpdate['rw'] = $request->input('rw') !== null ? trim((string)$request->input('rw')) : null;
            }
            if ($request->has('address')) {
                $patientUpdate['address'] = $request->input('address') !== null ? trim((string)$request->input('address')) : null;
            }

            if ($patient) {
                $patient->update($patientUpdate);
            } else {
                $patientUpdate['user_id'] = $user->id;
                $patient = Patient::create($patientUpdate);
            }
        }

        $user->refresh();
        if ($user->user_type_id == 2) {
            $user->load([
                'patient.puskesmas.subdistrict.district.province',
                'patient.village.subdistrict.district.province',
                'patient.subdistrict.district.province',
            ]);
        }

        $user->photo = $user->photo ?: null;

        $userData = $user->toArray();
        if ($user->patient) {
            $p = $user->patient;
            $subdistrict = $p->subdistrict ?? $p->village?->subdistrict;
            $district = $subdistrict?->district;
            $province = $district?->province;

            $pusk = $p->puskesmas;
            $puskSub = $pusk?->subdistrict?->name;
            $puskDist = $pusk?->subdistrict?->district?->name;
            $puskFormattedName = $pusk
                ? ($puskSub ? $pusk->name . ' (' . $puskSub . ', ' . $puskDist . ')' : $pusk->name)
                : null;

            $userData['nik'] = $p->nik;
            $userData['puskesmas_id'] = $p->puskesmas_id;
            $userData['puskesmas_name'] = $pusk ? $pusk->name : null;
            $userData['puskesmas_subdistrict_name'] = $puskSub;
            $userData['puskesmas_district_name'] = $puskDist;
            $userData['puskesmas_location'] = ($puskSub && $puskDist)
                ? ($puskSub . ' - ' . $puskDist)
                : ($puskSub ?: ($puskDist ?: null));
            $userData['puskesmas_formatted_name'] = $puskFormattedName;
            $userData['province_id'] = $province ? $province->id : null;
            $userData['province_name'] = $province ? $province->name : null;
            $userData['district_id'] = $district ? $district->id : null;
            $userData['district_name'] = $district ? $district->name : null;
            $userData['subdistrict_id'] = $subdistrict ? $subdistrict->id : null;
            $userData['subdistrict_name'] = $subdistrict ? $subdistrict->name : null;
            $userData['village_id'] = $p->village_id;
            $userData['village_name'] = $p->village ? $p->village->name : null;
            $userData['rt'] = $p->rt;
            $userData['rw'] = $p->rw;
            $userData['address'] = $p->address;
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $userData,
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
        }

        return response()->json([
            'message' => 'Token FCM berhasil diperbarui.',
        ]);
    }
}
