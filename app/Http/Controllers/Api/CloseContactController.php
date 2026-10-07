<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CloseContact;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CloseContactController extends Controller
{
    /**
     * Resolve target patient and verify access for the authenticated user.
     *
     * @param Request $request
     * @return array [Patient|null, string|null, int|null] (patient, error_message, status_code)
     */
    protected function resolveAuthorizedPatient(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return [null, 'Sesi tidak valid. Silakan login kembali.', 401];
        }

        // 1. Role Pasien (user_type_id = 2): Strictly self patient
        if ($user->user_type_id == 2) {
            $patient = $user->patient;
            if (!$patient) {
                return [null, 'Data pasien tidak ditemukan untuk akun ini.', 404];
            }
            return [$patient, null, 200];
        }

        // 2. Role Admin (1) atau Petugas (3, 4)
        $patientId = $request->input('patient_id') ?: $request->query('patient_id');
        if (!$patientId) {
            return [null, 'Parameter patient_id wajib disertakan untuk peran petugas/admin.', 422];
        }

        $patient = Patient::find($patientId);
        if (!$patient) {
            return [null, 'Data pasien tidak ditemukan.', 404];
        }

        if (method_exists($patient, 'isAccessibleBy') && !$patient->isAccessibleBy($user)) {
            return [null, 'Anda tidak memiliki wewenang mengakses data pasien ini.', 403];
        }

        return [$patient, null, 200];
    }

    /**
     * Verify whether the authenticated user has authorization over a given contact.
     *
     * @param CloseContact $contact
     * @return array [bool, string|null, int]
     */
    protected function verifyContactAccess(CloseContact $contact)
    {
        $user = Auth::user();
        if (!$user) {
            return [false, 'Sesi tidak valid. Silakan login kembali.', 401];
        }

        // Pasien hanya boleh mengakses kontak miliknya sendiri
        if ($user->user_type_id == 2) {
            $patient = $user->patient;
            if (!$patient || $contact->patient_id != $patient->id) {
                return [false, 'Anda tidak memiliki wewenang untuk mengakses data anggota serumah ini.', 403];
            }
            return [true, null, 200];
        }

        // Petugas / Admin: cek wewenang atas pasien terkait kontak
        $patient = $contact->patient;
        if ($patient && method_exists($patient, 'isAccessibleBy') && !$patient->isAccessibleBy($user)) {
            return [false, 'Anda tidak memiliki wewenang untuk mengakses data anggota serumah pasien ini.', 403];
        }

        return [true, null, 200];
    }

    /**
     * Format a contact model into clean, unified array for API response.
     */
    protected function formatContact(CloseContact $contact)
    {
        $latestScreening = $contact->latestScreening;
        $latestScreeningData = null;
        if ($latestScreening) {
            $latestScreeningData = [
                'id'                    => $latestScreening->id,
                'code'                  => $latestScreening->code,
                'risk_level'            => $latestScreening->risk_level,
                'status'                => $latestScreening->status,
                'total_score'           => $latestScreening->total_score,
                'symptoms_count'        => $latestScreening->symptoms_count,
                'has_critical_symptom'  => (bool) $latestScreening->has_critical_symptom,
                'recommendation'        => $latestScreening->recommendation,
                'notes'                 => $latestScreening->notes,
                'screened_at'           => $latestScreening->screened_at ? $latestScreening->screened_at->format('Y-m-d H:i') : null,
                'screened_at_formatted' => $latestScreening->screened_at ? $latestScreening->screened_at->isoFormat('D MMMM Y') : null,
            ];
        }

        $screenings = $contact->screenings ?? collect([]);
        $screeningsList = $screenings->map(function ($s) {
            return [
                'id'                    => $s->id,
                'code'                  => $s->code,
                'risk_level'            => $s->risk_level,
                'status'                => $s->status,
                'total_score'           => $s->total_score,
                'symptoms_count'        => $s->symptoms_count,
                'recommendation'        => $s->recommendation,
                'screened_at'           => $s->screened_at ? $s->screened_at->format('Y-m-d H:i') : null,
                'screened_at_formatted' => $s->screened_at ? $s->screened_at->isoFormat('D MMMM Y') : null,
            ];
        })->values()->all();

        return [
            'id'               => $contact->id,
            'contact_code'     => $contact->contact_code,
            'patient_id'       => $contact->patient_id,
            'name'             => $contact->name,
            'relationship'     => $contact->relationship,
            'gender'           => $contact->gender,
            'gender_label'     => $contact->gender === 'P' ? 'Perempuan' : 'Laki-laki',
            'date_of_birth'    => $contact->date_of_birth ? Carbon::parse($contact->date_of_birth)->format('Y-m-d') : null,
            'age'              => $contact->date_of_birth ? Carbon::parse($contact->date_of_birth)->age : ($contact->age !== null ? (int) $contact->age : null),
            'nik'              => $contact->nik,
            'phone'            => $contact->phone,
            'address'          => $contact->address,
            'screening_date'   => $contact->screening_date ? Carbon::parse($contact->screening_date)->format('Y-m-d') : null,
            'screening_result' => $contact->screening_result ?? ($latestScreeningData ? $latestScreeningData['risk_level'] : 'Belum Skrining'),
            'tpt_status'       => $contact->tpt_status ?? 'Tidak Perlu',
            'notes'            => $contact->notes,
            'latest_screening' => $latestScreeningData,
            'screenings'       => $screeningsList,
            'created_at'       => $contact->created_at ? $contact->created_at->format('Y-m-d H:i:s') : null,
            'updated_at'       => $contact->updated_at ? $contact->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * GET /api/contacts
     * Ambil daftar kontak erat / anggota serumah pasien.
     */
    public function index(Request $request)
    {
        [$patient, $error, $status] = $this->resolveAuthorizedPatient($request);
        if ($error) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], $status);
        }

        $contacts = CloseContact::with(['latestScreening', 'screenings'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn($c) => $this->formatContact($c));

        return response()->json([
            'success' => true,
            'message' => 'Daftar anggota serumah berhasil dimuat.',
            'count'   => $contacts->count(),
            'data'    => $contacts,
        ], 200);
    }

    /**
     * GET /api/contacts/count
     * Ambil jumlah anggota serumah pasien (untuk ringkasan badge di kartu).
     */
    public function count(Request $request)
    {
        [$patient, $error, $status] = $this->resolveAuthorizedPatient($request);
        if ($error) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], $status);
        }

        $count = CloseContact::where('patient_id', $patient->id)->count();

        return response()->json([
            'success' => true,
            'count'   => $count,
        ], 200);
    }

    /**
     * GET /api/contacts/{id}
     * Ambil detail data satu anggota serumah.
     */
    public function show(Request $request, $id)
    {
        $contact = CloseContact::with(['latestScreening', 'screenings'])->find($id);
        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Data anggota serumah tidak ditemukan.',
            ], 404);
        }

        [$allowed, $error, $status] = $this->verifyContactAccess($contact);
        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail anggota serumah berhasil dimuat.',
            'data'    => $this->formatContact($contact),
        ], 200);
    }

    /**
     * POST /api/contacts
     * Tambah anggota serumah baru.
     */
    public function store(Request $request)
    {
        [$patient, $error, $status] = $this->resolveAuthorizedPatient($request);
        if ($error) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], $status);
        }

        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'relationship'     => 'required|string|max:100',
            'gender'           => 'required|in:L,P',
            'date_of_birth'    => 'nullable|date|before_or_equal:today',
            'age'              => 'nullable|integer|min:0|max:120',
            'nik'              => 'nullable|string|max:30',
            'phone'            => 'nullable|string|max:30',
            'address'          => 'nullable|string',
            'screening_date'   => 'nullable|date',
            'screening_result' => 'nullable|string|max:100',
            'tpt_status'       => 'nullable|string|max:100',
            'notes'            => 'nullable|string',
        ], [
            'name.required'         => 'Nama lengkap wajib diisi.',
            'relationship.required' => 'Hubungan dengan pasien wajib dipilih.',
            'gender.required'       => 'Jenis kelamin wajib dipilih.',
            'gender.in'             => 'Pilihan jenis kelamin tidak valid (L atau P).',
            'date_of_birth.date'    => 'Format tanggal lahir tidak valid.',
            'date_of_birth.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
            'age.integer'           => 'Usia harus berupa angka bulat.',
            'age.min'               => 'Usia minimal 0 tahun.',
            'age.max'               => 'Usia maksimal 120 tahun.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Hitung umur secara otomatis jika tanggal lahir diberikan
        $dob = $request->filled('date_of_birth') ? Carbon::parse($request->date_of_birth) : null;
        $age = $dob ? $dob->age : ($request->filled('age') ? (int) $request->age : 0);

        // Generate kode kontak unik format KONT-YYYYMM-XXXX
        $code = 'KONT-' . Carbon::now()->format('Ym') . '-' . str_pad(CloseContact::count() + 1, 4, '0', STR_PAD_LEFT);
        $counter = 1;
        while (CloseContact::where('contact_code', $code)->exists()) {
            $code = 'KONT-' . Carbon::now()->format('Ym') . '-' . str_pad(CloseContact::count() + 1 + $counter, 4, '0', STR_PAD_LEFT);
            $counter++;
        }

        $contact = DB::transaction(function () use ($request, $patient, $code, $dob, $age) {
            $contact = new CloseContact();
            $contact->contact_code     = $code;
            $contact->patient_id       = $patient->id;
            $contact->name             = trim($request->name);
            $contact->relationship     = trim($request->relationship);
            $contact->gender           = $request->gender;
            $contact->date_of_birth    = $dob ? $dob->format('Y-m-d') : null;
            $contact->age              = $age;
            $contact->nik              = $request->filled('nik') ? trim($request->nik) : null;
            $contact->phone            = $request->filled('phone') ? trim($request->phone) : null;
            $contact->address          = $request->filled('address') ? trim($request->address) : null;
            $contact->screening_date   = $request->filled('screening_date') ? $request->screening_date : null;
            $contact->screening_result = $request->filled('screening_result') ? trim($request->screening_result) : 'Belum Skrining';
            $contact->tpt_status       = $request->filled('tpt_status') ? trim($request->tpt_status) : 'Tidak Perlu';
            $contact->notes            = $request->filled('notes') ? trim($request->notes) : null;
            $contact->save();

            if (class_exists(ActivityLog::class)) {
                ActivityLog::log(
                    'Tambah Anggota Serumah',
                    'Kontak Erat',
                    "Menambahkan anggota serumah {$contact->name} ({$contact->relationship}) untuk Pasien ID {$patient->id}."
                );
            }

            return $contact;
        });

        return response()->json([
            'success' => true,
            'message' => 'Anggota serumah berhasil ditambahkan.',
            'data'    => $this->formatContact($contact),
        ], 201);
    }

    /**
     * PUT/POST /api/contacts/{id}
     * Ubah data anggota serumah.
     */
    public function update(Request $request, $id)
    {
        $contact = CloseContact::find($id);
        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Data anggota serumah tidak ditemukan.',
            ], 404);
        }

        [$allowed, $error, $status] = $this->verifyContactAccess($contact);
        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], $status);
        }

        $validator = Validator::make($request->all(), [
            'name'             => 'required|string|max:255',
            'relationship'     => 'required|string|max:100',
            'gender'           => 'required|in:L,P',
            'date_of_birth'    => 'nullable|date|before_or_equal:today',
            'age'              => 'nullable|integer|min:0|max:120',
            'nik'              => 'nullable|string|max:30',
            'phone'            => 'nullable|string|max:30',
            'address'          => 'nullable|string',
            'screening_date'   => 'nullable|date',
            'screening_result' => 'nullable|string|max:100',
            'tpt_status'       => 'nullable|string|max:100',
            'notes'            => 'nullable|string',
        ], [
            'name.required'         => 'Nama lengkap wajib diisi.',
            'relationship.required' => 'Hubungan dengan pasien wajib dipilih.',
            'gender.required'       => 'Jenis kelamin wajib dipilih.',
            'gender.in'             => 'Pilihan jenis kelamin tidak valid (L atau P).',
            'date_of_birth.date'    => 'Format tanggal lahir tidak valid.',
            'date_of_birth.before_or_equal' => 'Tanggal lahir tidak boleh melebihi hari ini.',
            'age.integer'           => 'Usia harus berupa angka bulat.',
            'age.min'               => 'Usia minimal 0 tahun.',
            'age.max'               => 'Usia maksimal 120 tahun.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $dob = $request->filled('date_of_birth') ? Carbon::parse($request->date_of_birth) : ($contact->date_of_birth ? Carbon::parse($contact->date_of_birth) : null);
        $age = $dob ? $dob->age : ($request->filled('age') ? (int) $request->age : $contact->age);

        DB::transaction(function () use ($request, $contact, $dob, $age) {
            $contact->name             = trim($request->name);
            $contact->relationship     = trim($request->relationship);
            $contact->gender           = $request->gender;
            $contact->date_of_birth    = $dob ? $dob->format('Y-m-d') : null;
            $contact->age              = $age;
            $contact->nik              = $request->filled('nik') ? trim($request->nik) : null;
            $contact->phone            = $request->filled('phone') ? trim($request->phone) : null;
            $contact->address          = $request->filled('address') ? trim($request->address) : null;
            if ($request->has('screening_date')) {
                $contact->screening_date = $request->filled('screening_date') ? $request->screening_date : null;
            }
            if ($request->has('screening_result')) {
                $contact->screening_result = trim($request->screening_result);
            }
            if ($request->has('tpt_status')) {
                $contact->tpt_status = trim($request->tpt_status);
            }
            if ($request->has('notes')) {
                $contact->notes = $request->filled('notes') ? trim($request->notes) : null;
            }
            $contact->save();

            if (class_exists(ActivityLog::class)) {
                ActivityLog::log(
                    'Perbarui Anggota Serumah',
                    'Kontak Erat',
                    "Memperbarui data anggota serumah {$contact->name} (Kode {$contact->contact_code})."
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Data anggota serumah berhasil diperbarui.',
            'data'    => $this->formatContact($contact->fresh()),
        ], 200);
    }

    /**
     * DELETE /api/contacts/{id}
     * Hapus satu anggota serumah.
     */
    public function destroy(Request $request, $id)
    {
        $contact = CloseContact::find($id);
        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Data anggota serumah tidak ditemukan.',
            ], 404);
        }

        [$allowed, $error, $status] = $this->verifyContactAccess($contact);
        if (!$allowed) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], $status);
        }

        $contactName = $contact->name;
        $patientId = $contact->patient_id;

        DB::transaction(function () use ($contact, $contactName, $patientId) {
            $contact->delete();

            if (class_exists(ActivityLog::class)) {
                ActivityLog::log(
                    'Hapus Anggota Serumah',
                    'Kontak Erat',
                    "Menghapus anggota serumah {$contactName} dari Pasien ID {$patientId}."
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Anggota serumah berhasil dihapus.',
        ], 200);
    }
}
