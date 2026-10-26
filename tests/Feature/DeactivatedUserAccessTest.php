<?php

namespace Tests\Feature;

use App\Models\ClinicalExamination;
use App\Models\CloseContact;
use App\Models\District;
use App\Models\MedicationRecord;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientMedicationSchedule;
use App\Models\PatientTreatment;
use App\Models\Province;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\TreatmentType;
use App\Models\TreatmentVisit;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class DeactivatedUserAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected $province;
    protected $district;
    protected $subdistrict;
    protected $village;
    protected $puskesmas;
    protected $treatmentType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->province = Province::first();
        $this->district = District::where('province_id', $this->province ? $this->province->id : 1)->first() ?? District::first();
        $this->subdistrict = Subdistrict::where('district_id', $this->district ? $this->district->id : 1)->first() ?? Subdistrict::first();
        $this->village = Village::where('subdistrict_id', $this->subdistrict ? $this->subdistrict->id : 1)->first() ?? Village::first();
        $this->puskesmas = Puskesmas::first();

        $this->treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'name' => 'Kategori 1 (Dewasa)'
        ]);
    }

    protected function createTestUser(array $attributes = []): User
    {
        $unique = uniqid();
        return User::create(array_merge([
            'name'           => "User {$unique}",
            'username'       => "user_{$unique}",
            'email'          => "user_{$unique}@example.com",
            'password'       => Hash::make('password123'),
            'user_type_id'   => 2, // Default patient
            'is_active'      => true,
            'phone'          => '081234567890',
            'gender'         => 'L',
            'place_of_birth' => 'Tasikmalaya',
            'date_of_birth'  => '1995-01-01',
        ], $attributes));
    }

    protected function createTestPatient(User $user, array $patientAttributes = []): Patient
    {
        $unique = mt_rand(10000000, 99999999);
        return Patient::create(array_merge([
            'user_id'              => $user->id,
            'nik'                  => "327801{$unique}",
            'puskesmas_id'         => $this->puskesmas->id,
            'subdistrict_id'       => $this->subdistrict->id,
            'village_id'           => $this->village->id,
            'address'              => 'Jl. Pengujian No. 123',
            'rt'                   => '01',
            'rw'                   => '01',
            'treatment_start_date' => now()->subMonths(1)->format('Y-m-d'),
        ], $patientAttributes));
    }

    protected function createTestOfficer(User $user, int $officerTypeId = 3): Officer
    {
        return Officer::create([
            'user_id'         => $user->id,
            'officer_type_id' => $officerTypeId, // 3: PJTB Puskesmas, 4: Kader
            'puskesmas_id'    => $this->puskesmas->id,
            'district_id'     => $this->district->id,
        ]);
    }

    /**
     * 1. Akun aktif dapat login normal.
     */
    public function test_01_active_account_can_login_normally()
    {
        $user = $this->createTestUser([
            'username'  => 'active_user_01',
            'password'  => Hash::make('secretPass123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'active_user_01',
            'password' => 'secretPass123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Login berhasil',
            ]);
    }

    /**
     * 2. Akun aktif menerima token Sanctum baru.
     */
    public function test_02_active_account_receives_new_sanctum_token()
    {
        $user = $this->createTestUser([
            'username'  => 'token_user_02',
            'password'  => Hash::make('secretPass123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'token_user_02',
            'password' => 'secretPass123',
        ]);

        $response->assertStatus(200);
        $token = $response->json('token');

        $this->assertNotNull($token, 'Token harus disertakan pada respons login akun aktif.');
        $this->assertNotEmpty($token);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    /**
     * 3. Akun aktif dapat mengakses endpoint terlindungi (/api/me, /api/patients).
     */
    public function test_03_active_account_can_access_protected_endpoints()
    {
        $officerUser = $this->createTestUser([
            'username'     => 'officer_user_03',
            'password'     => Hash::make('secretPass123'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);
        $this->createTestOfficer($officerUser, 3);

        $loginResponse = $this->postJson('/api/login', [
            'username' => 'officer_user_03',
            'password' => 'secretPass123',
        ]);
        $token = $loginResponse->json('token');

        // Akses /api/me
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');
        $meResponse->assertStatus(200)
            ->assertJsonPath('user.id', $officerUser->id);

        // Akses /api/patients
        $patientsResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/patients');
        $patientsResponse->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * 4. Akun nonaktif gagal login pada /api/login meskipun username dan password benar.
     */
    public function test_04_deactivated_account_fails_to_login_even_with_correct_credentials()
    {
        $user = $this->createTestUser([
            'username'  => 'inactive_user_04',
            'password'  => Hash::make('correctPassword123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'inactive_user_04',
            'password' => 'correctPassword123',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 5. Akun nonaktif tidak mendapatkan token baru pada saat login gagal.
     */
    public function test_05_deactivated_account_receives_no_new_token_on_failed_login()
    {
        $user = $this->createTestUser([
            'username'  => 'inactive_user_05',
            'password'  => Hash::make('correctPassword123'),
            'is_active' => false,
        ]);

        $tokenCountBefore = PersonalAccessToken::where('tokenable_id', $user->id)->count();

        $response = $this->postJson('/api/login', [
            'username' => 'inactive_user_05',
            'password' => 'correctPassword123',
        ]);

        $response->assertStatus(403);
        $this->assertNull($response->json('token'), 'Akun nonaktif tidak boleh menerima token.');

        $tokenCountAfter = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        $this->assertEquals($tokenCountBefore, $tokenCountAfter, 'Tidak boleh ada token baru yang dibuat di database.');
    }

    /**
     * 6. Respons login untuk akun nonaktif memberikan status code yang tepat (403) dan pesan yang aman.
     */
    public function test_06_deactivated_login_response_returns_exact_403_and_safe_message()
    {
        $user = $this->createTestUser([
            'username'  => 'inactive_user_06',
            'password'  => Hash::make('correctPassword123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'inactive_user_06',
            'password' => 'correctPassword123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code'    => 'ACCOUNT_DEACTIVATED',
                'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi petugas TB Care untuk informasi lebih lanjut.',
            ]);
    }

    /**
     * 7. Akun yang dinonaktifkan setelah login: request berikutnya dengan token lama ditolak.
     */
    public function test_07_account_deactivated_after_login_rejects_subsequent_request_with_old_token()
    {
        // 1. User login saat masih aktif
        $user = $this->createTestUser([
            'username'  => 'active_then_deactivated_07',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'username' => 'active_then_deactivated_07',
            'password' => 'password123',
        ]);
        $token = $loginResponse->json('token');
        $this->assertNotNull($token);

        // Verifikasi token aktif bisa akses /api/me
        $firstAccess = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');
        $firstAccess->assertStatus(200);

        // 2. Administrator menonaktifkan akun di database
        $user->is_active = false;
        $user->save();

        $this->app['auth']->forgetGuards();

        // 3. Request berikutnya menggunakan token lama HARUS DITOLAK (403)
        $subsequentAccess = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');

        $subsequentAccess->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code'    => 'ACCOUNT_DEACTIVATED',
                'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi petugas TB Care untuk informasi lebih lanjut.',
            ]);
    }

    /**
     * 8. Token lama milik akun nonaktif dicabut/tidak valid lagi.
     */
    public function test_08_old_token_of_deactivated_account_is_revoked_and_invalidated()
    {
        $user = $this->createTestUser([
            'username'  => 'revoke_token_user_08',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'username' => 'revoke_token_user_08',
            'password' => 'password123',
        ]);
        $token = $loginResponse->json('token');

        // Pastikan token tersimpan di DB
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        // Nonaktifkan user
        $user->is_active = false;
        $user->save();

        $this->app['auth']->forgetGuards();

        // Request pertama setelah nonaktif menolak & mencabut token via EnsureAccountIsActive
        $firstRejected = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');
        $firstRejected->assertStatus(403);

        // Verifikasi token sekarang telah dicabut / dihapus dari database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);

        $this->app['auth']->forgetGuards();

        // Request kedua dengan token lama langsung 401 Unauthorized karena token sudah tidak ada
        $secondRejected = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me');
        $secondRejected->assertStatus(401);
    }

    /**
     * 9. Endpoint /api/patients tidak memuat data pasien yang akunnya nonaktif.
     */
    public function test_09_api_patients_does_not_contain_deactivated_patients()
    {
        // Petugas aktif yang mengakses list
        $officerUser = $this->createTestUser([
            'username'     => 'pjtb_officer_09',
            'user_type_id' => 3,
            'is_active'    => true,
        ]);
        $this->createTestOfficer($officerUser, 3);

        // Pasien A: Akun Aktif
        $userActive = $this->createTestUser([
            'name'      => 'Pasien Aktif Aman 09',
            'username'  => 'patient_active_09',
            'is_active' => true,
        ]);
        $patientActive = $this->createTestPatient($userActive);

        // Pasien B: Akun Nonaktif
        $userInactive = $this->createTestUser([
            'name'      => 'Pasien Nonaktif Tersembunyi 09',
            'username'  => 'patient_inactive_09',
            'is_active' => false,
        ]);
        $patientInactive = $this->createTestPatient($userInactive);

        // Login petugas
        $token = $this->postJson('/api/login', [
            'username' => 'pjtb_officer_09',
            'password' => 'password123',
        ])->json('token');

        // Panggil /api/patients
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/patients');

        $response->assertStatus(200);

        $patientIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($patientActive->id, $patientIds, 'Pasien aktif harus muncul pada daftar operasional.');
        $this->assertNotContains($patientInactive->id, $patientIds, 'Pasien dengan akun nonaktif TIDAK BOLEH muncul pada daftar mobile.');
    }

    /**
     * 10. Endpoint relasi/list penerima pesan/konsultasi tidak memuat pengguna/pasien nonaktif.
     */
    public function test_10_consultation_recipients_does_not_contain_deactivated_users_or_patients()
    {
        // Petugas aktif (PJTB)
        $officerUser = $this->createTestUser([
            'username'     => 'pjtb_officer_10',
            'user_type_id' => 3,
            'is_active'    => true,
        ]);
        $this->createTestOfficer($officerUser, 3);

        // Pasien Aktif
        $patientUserActive = $this->createTestUser([
            'name'      => 'Pasien Konsultasi Aktif 10',
            'username'  => 'patient_chat_active_10',
            'is_active' => true,
        ]);
        $patientActive = $this->createTestPatient($patientUserActive);

        // Pasien Nonaktif
        $patientUserInactive = $this->createTestUser([
            'name'      => 'Pasien Konsultasi Nonaktif 10',
            'username'  => 'patient_chat_inactive_10',
            'is_active' => false,
        ]);
        $patientInactive = $this->createTestPatient($patientUserInactive);

        $token = $this->postJson('/api/login', [
            'username' => 'pjtb_officer_10',
            'password' => 'password123',
        ])->json('token');

        // Petugas memuat daftar pasien untuk konsultasi
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/consultations/recipients');

        $response->assertStatus(200);

        $recipientIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($patientUserActive->id, $recipientIds, 'Pasien aktif harus ada dalam daftar penerima konsultasi.');
        $this->assertNotContains($patientUserInactive->id, $recipientIds, 'Pengguna nonaktif TIDAK BOLEH muncul dalam daftar penerima konsultasi.');
    }

    /**
     * 11. Akun yang diaktifkan kembali dapat login ulang dan menggunakan layanan secara normal.
     */
    public function test_11_reactivated_account_can_login_again_and_use_services_normally()
    {
        $user = $this->createTestUser([
            'username'  => 'reactivated_user_11',
            'password'  => Hash::make('reactivatePass123'),
            'is_active' => false,
        ]);

        // Login saat nonaktif gagal
        $firstLogin = $this->postJson('/api/login', [
            'username' => 'reactivated_user_11',
            'password' => 'reactivatePass123',
        ]);
        $firstLogin->assertStatus(403);

        // Administrator mengaktifkan kembali akun
        $user->is_active = true;
        $user->save();

        // Login setelah diaktifkan kembali BERHASIL
        $secondLogin = $this->postJson('/api/login', [
            'username' => 'reactivated_user_11',
            'password' => 'reactivatePass123',
        ]);
        $secondLogin->assertStatus(200)
            ->assertJson(['message' => 'Login berhasil']);

        $newToken = $secondLogin->json('token');
        $this->assertNotNull($newToken);

        // Dapat mengakses endpoint terlindungi secara normal
        $me = $this->withHeader('Authorization', "Bearer {$newToken}")
            ->getJson('/api/me');
        $me->assertStatus(200);
    }

    /**
     * 12. Riwayat pengobatan dan rekam medis pasien nonaktif tetap ada di database (tidak terhapus).
     */
    public function test_12_treatment_history_and_clinical_records_of_deactivated_patient_remain_intact_in_database()
    {
        $user = $this->createTestUser([
            'username'  => 'patient_with_records_12',
            'is_active' => true,
        ]);
        $patient = $this->createTestPatient($user);

        // Buat data riwayat pengobatan, jadwal obat, dan kunjungan
        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'start_date'        => now()->subMonths(2)->format('Y-m-d'),
            'end_date'          => now()->addMonths(4)->format('Y-m-d'),
            'diagnosis_date'    => now()->subMonths(2)->format('Y-m-d'),
        ]);

        $schedule = PatientMedicationSchedule::create([
            'patient_id'          => $patient->id,
            'reminder_time'       => '08:00:00',
            'is_active'           => true,
        ]);

        $medRecord = MedicationRecord::create([
            'patient_treatment_id' => $treatment->id,
            'is_verified'          => 1,
            'late'                 => 0,
            'notes'                => 'Obat diminum tepat waktu dengan pendampingan PMO.',
        ]);

        $visit = TreatmentVisit::create([
            'patient_treatment_id' => $treatment->id,
            'visit_date'           => now()->subDays(5)->format('Y-m-d'),
            'notes'                => 'Kondisi stabil, batuk berkurang.',
        ]);

        $exam = ClinicalExamination::create([
            'examination_code'    => 'EXAM-' . uniqid(),
            'patient_id'          => $patient->id,
            'examination_type'    => 'Tes Cepat Molekuler (TCM)',
            'examination_date'    => now()->subMonths(2)->format('Y-m-d'),
            'result'              => 'Positif',
            'status'              => 'Selesai',
        ]);

        // Simulasikan akun dinonaktifkan oleh administrator
        $user->is_active = false;
        $user->save();

        // PASTIKAN data rekam medis TIDAK TERHAPUS dari database
        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
        $this->assertDatabaseHas('patient_treatments', ['id' => $treatment->id]);
        $this->assertDatabaseHas('patient_medication_schedules', ['id' => $schedule->id]);
        $this->assertDatabaseHas('medication_records', ['id' => $medRecord->id]);
        $this->assertDatabaseHas('treatment_visits', ['id' => $visit->id]);
        $this->assertDatabaseHas('clinical_examinations', ['id' => $exam->id]);
    }

    /**
     * 13. Perubahan status aktif/nonaktif pada TB Care Web langsung berdampak pada akses API mobile.
     */
    public function test_13_status_toggle_in_tb_care_web_immediately_impacts_api_access()
    {
        // 1. Admin login ke web
        $adminUser = $this->createTestUser([
            'username'     => 'admin_master_13',
            'user_type_id' => 1,
            'is_active'    => true,
        ]);

        // 2. User target aktif dan memiliki token API mobile
        $targetUser = $this->createTestUser([
            'username'  => 'mobile_target_13',
            'password'  => Hash::make('targetPass123'),
            'is_active' => true,
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'username' => 'mobile_target_13',
            'password' => 'targetPass123',
        ]);
        $targetToken = $loginResponse->json('token');
        $this->assertNotNull($targetToken);

        // Sebelum dimatikan, akses sukses
        $preCheck = $this->withHeader('Authorization', "Bearer {$targetToken}")
            ->getJson('/api/me');
        $preCheck->assertStatus(200);

        // 3. Admin menekan tombol toggleStatus pada TB Care Web
        $encryptedId = encrypt_id($targetUser->id);
        $toggleResponse = $this->actingAs($adminUser, 'web')
            ->patch("/users/{$encryptedId}/toggle-status");

        $toggleResponse->assertRedirect();

        // 4. Periksa bahwa database langsung mencatat is_active = 0
        $targetUser->refresh();
        $this->assertEquals(0, $targetUser->is_active);

        // 5. Periksa bahwa seluruh token Sanctum langsung dicabut dari DB
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $targetUser->id,
        ]);

        $this->app['auth']->forgetGuards();

        // 6. Akses berikutnya dari mobile langsung ditolak 401
        $postCheck = $this->withHeader('Authorization', "Bearer {$targetToken}")
            ->getJson('/api/me');
        $postCheck->assertStatus(401);
    }

    /**
     * 14. Upaya login dengan credential salah tetap menghasilkan pesan kegagalan standar tanpa membocorkan status akun.
     */
    public function test_14_login_attempt_with_invalid_credentials_returns_generic_error_without_leaking_account_status()
    {
        // Akun aktif dengan password salah
        $activeUser = $this->createTestUser([
            'username'  => 'active_user_14',
            'password'  => Hash::make('realPassword123'),
            'is_active' => true,
        ]);

        // Akun nonaktif dengan password salah
        $inactiveUser = $this->createTestUser([
            'username'  => 'inactive_user_14',
            'password'  => Hash::make('realPassword123'),
            'is_active' => false,
        ]);

        // 1. Password salah pada akun aktif
        $resActiveWrongPass = $this->postJson('/api/login', [
            'username' => 'active_user_14',
            'password' => 'wrongPassword999',
        ]);
        $resActiveWrongPass->assertStatus(401)
            ->assertJson([
                'message' => 'Login gagal. Username/email atau password salah.',
            ]);

        // 2. Password salah pada akun nonaktif (TIDAK BOLEH membocorkan bahwa akun dinonaktifkan)
        $resInactiveWrongPass = $this->postJson('/api/login', [
            'username' => 'inactive_user_14',
            'password' => 'wrongPassword999',
        ]);
        $resInactiveWrongPass->assertStatus(401)
            ->assertJson([
                'message' => 'Login gagal. Username/email atau password salah.',
            ]);

        // 3. Username yang tidak terdaftar sama sekali
        $resNonExistent = $this->postJson('/api/login', [
            'username' => 'definitely_non_existent_user_xyz',
            'password' => 'anyPassword123',
        ]);
        $resNonExistent->assertStatus(401)
            ->assertJson([
                'message' => 'Login gagal. Username/email atau password salah.',
            ]);
    }
}
