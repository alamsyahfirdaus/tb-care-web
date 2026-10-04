<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class PatientRegistrationTreatmentTest extends TestCase
{
    /**
     * Helper to generate a valid registration payload
     */
    private function generatePayload(array $overrides = []): array
    {
        $uniqueNik = '3278' . str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);
        $uniquePhone = '0812' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        return array_merge([
            'name'                 => 'Budi Santoso',
            'nik'                  => $uniqueNik,
            'phone'                => $uniquePhone,
            'puskesmas_id'         => 1,
            'treatment_start_date' => Carbon::today()->format('Y-m-d'),
            'province_id'          => 1,
            'district_id'          => 27,
            'subdistrict_id'       => 41,
            'village_id'           => 9,
        ], $overrides);
    }

    /**
     * Test 1: Registrasi otomatis membuat treatment
     */
    public function test_registration_automatically_creates_treatment()
    {
        $payload = $this->generatePayload();

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user_id',
                    'pasien_id',
                    'username',
                    'password',
                ],
                'user' => [
                    'id',
                    'name',
                    'username',
                    'phone',
                ],
                'treatment' => [
                    'id',
                    'patient_id',
                    'treatment_type_id',
                    'diagnosis_date',
                    'start_date',
                    'medication_time',
                    'prescription',
                    'treatment_status',
                ],
            ]);

        $userId = $response->json('user.id');
        $patient = Patient::where('user_id', $userId)->first();
        $this->assertNotNull($patient);

        // Assert database: patient_treatments memiliki treatment untuk patient tersebut
        $this->assertDatabaseHas('patient_treatments', [
            'patient_id' => $patient->id,
        ]);

        $treatment = PatientTreatment::where('patient_id', $patient->id)->first();
        $this->assertNotNull($treatment);
        $this->assertEquals($patient->id, $treatment->patient_id);

        // Cleanup
        $treatment->delete();
        $patient->delete();
        User::find($userId)?->delete();
    }

    /**
     * Test 2 to 8: Seluruh nilai field treatment sesuai dengan spesifikasi
     */
    public function test_treatment_field_values_strictly_match_specifications()
    {
        $inputStartDate = '2026-10-04';
        $payload = $this->generatePayload([
            'treatment_start_date' => $inputStartDate,
        ]);

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201);

        $userId = $response->json('user.id');
        $patient = Patient::where('user_id', $userId)->first();
        $this->assertNotNull($patient);

        $treatment = PatientTreatment::where('patient_id', $patient->id)->first();
        $this->assertNotNull($treatment);

        // Test 2: patient_id = patient.id
        $this->assertEquals($patient->id, $treatment->patient_id);

        // Test 3: start_date mengikuti input (2026-10-04)
        $this->assertEquals($inputStartDate, $treatment->start_date);

        // Test 4: medication_time = 07:00:00
        $this->assertStringStartsWith('07:00', $treatment->medication_time);

        // Test 5: treatment_type_id = 1 (Pasien Baru)
        $this->assertEquals(1, $treatment->treatment_type_id);

        // Test 6: diagnosis_date IS NULL
        $this->assertNull($treatment->diagnosis_date);

        // Test 7: prescription IS NULL
        $this->assertNull($treatment->prescription);

        // Test 8: treatment_status = Berjalan
        $this->assertEquals('Berjalan', $treatment->treatment_status);

        // Cleanup
        $treatment->delete();
        $patient->delete();
        User::find($userId)?->delete();
    }

    /**
     * Test: Start date mengikuti input tanggal dinamis
     */
    public function test_start_date_follows_past_input_date()
    {
        $specificDate = Carbon::today()->subDays(12)->format('Y-m-d');
        $payload = $this->generatePayload([
            'treatment_start_date' => $specificDate,
        ]);

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201);

        $patientId = $response->json('data.pasien_id');
        $this->assertDatabaseHas('patient_treatments', [
            'patient_id' => $patientId,
            'start_date' => $specificDate,
        ]);

        // Cleanup
        PatientTreatment::where('patient_id', $patientId)->delete();
        $patient = Patient::find($patientId);
        $userId = $patient?->user_id;
        $patient?->delete();
        if ($userId) {
            User::find($userId)?->delete();
        }
    }

    /**
     * Test 19: Database Transaction / Rollback
     * Jika pembuatan treatment gagal, User dan Patient tidak boleh tersimpan.
     */
    public function test_transaction_rolls_back_user_and_patient_when_treatment_creation_fails()
    {
        $uniquePhone = '0899' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $uniqueNik = '3278' . str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);

        $payload = $this->generatePayload([
            'phone'                => $uniquePhone,
            'nik'                  => $uniqueNik,
            'treatment_start_date' => '1999-01-01',
        ]);

        // Pasang event hook untuk mensimulasikan kegagalan saat insert PatientTreatment
        PatientTreatment::creating(function () {
            throw new \Exception('Simulated database error during PatientTreatment creation');
        });

        try {
            $response = $this->postJson('/api/register', $payload);
            // Harusnya melempar 500 karena exception di dalam DB::transaction
            $this->assertEquals(500, $response->status());
        } finally {
            // Bersihkan event listener agar tidak mempengaruhi test lain
            PatientTreatment::flushEventListeners();
        }

        // Assert ROLLBACK: User tidak tersimpan
        $this->assertDatabaseMissing('users', [
            'phone' => $uniquePhone,
        ]);

        // Assert ROLLBACK: Patient tidak tersimpan
        $this->assertDatabaseMissing('patients', [
            'nik' => $uniqueNik,
        ]);

        // Assert ROLLBACK: Treatment tidak tersimpan
        $this->assertDatabaseMissing('patient_treatments', [
            'medication_time' => '07:00:00',
            'start_date'      => $payload['treatment_start_date'],
        ]);
    }

    /**
     * Test: Pastikan proses registrasi hanya menghasilkan tepat 1 Treatment
     */
    public function test_one_registration_creates_exactly_one_treatment()
    {
        $payload = $this->generatePayload();

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201);

        $patientId = $response->json('data.pasien_id');
        $treatmentsCount = PatientTreatment::where('patient_id', $patientId)->count();

        $this->assertEquals(1, $treatmentsCount);

        // Cleanup
        PatientTreatment::where('patient_id', $patientId)->delete();
        $patient = Patient::find($patientId);
        $userId = $patient?->user_id;
        $patient?->delete();
        if ($userId) {
            User::find($userId)?->delete();
        }
    }

    /**
     * Test 20: End-to-end Test
     * Register -> Create User -> Create Patient -> Create Treatment -> Response Credential -> Login
     */
    public function test_end_to_end_registration_treatment_and_login_flow()
    {
        $uniqueNik = '3278' . str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);
        $uniquePhone = '0812' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $treatmentDate = Carbon::today()->subDays(3)->format('Y-m-d');

        $payload = [
            'name'                 => 'Budi Santoso',
            'nik'                  => $uniqueNik,
            'phone'                => $uniquePhone,
            'puskesmas_id'         => 1,
            'treatment_start_date' => $treatmentDate,
            'province_id'          => 1,
            'district_id'          => 27,
            'subdistrict_id'       => 41,
            'village_id'           => 9,
        ];

        // 1. POST /api/register
        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201);

        $username = $response->json('data.username');
        $password = $response->json('data.password');
        $userId = $response->json('user.id');
        $patientId = $response->json('data.pasien_id');

        $this->assertNotEmpty($username);
        $this->assertEquals('123456', $password);

        // 2. Cek Database USER
        $user = User::with('patient')->find($userId);
        $this->assertNotNull($user);
        $this->assertEquals($username, $user->username);
        $this->assertEquals(2, $user->user_type_id); // Pasien
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('123456', $user->password));

        // 3. Cek Database PATIENT
        $patient = Patient::with('treatments')->find($patientId);
        $this->assertNotNull($patient);
        $this->assertEquals($user->id, $patient->user_id);
        $this->assertEquals($uniqueNik, $patient->nik);
        $this->assertEquals($treatmentDate, Carbon::parse($patient->treatment_start_date)->format('Y-m-d'));

        // 4. Cek Database PATIENT TREATMENT
        $treatment = PatientTreatment::where('patient_id', $patient->id)->first();
        $this->assertNotNull($treatment);
        $this->assertEquals($patient->id, $treatment->patient_id);
        $this->assertEquals(1, $treatment->treatment_type_id); // Kategori 1 (Pasien Baru)
        $this->assertNull($treatment->diagnosis_date);
        $this->assertEquals($treatmentDate, $treatment->start_date);
        $this->assertStringStartsWith('07:00', $treatment->medication_time);
        $this->assertNull($treatment->prescription);
        $this->assertEquals('Berjalan', $treatment->treatment_status);

        // 5. Cek Relasi Antar Model: User -> Patient -> Treatment
        $this->assertEquals($patient->id, $user->patient->id);
        $this->assertEquals($treatment->id, $patient->treatments->first()->id);
        $this->assertEquals($user->id, $treatment->patient->user->id);

        // 6. Test Login dengan kredensial pasien baru (username otomatis & password 123456)
        $loginResponse = $this->postJson('/api/login', [
            'username' => $username,
            'password' => $password,
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => [
                    'id',
                    'user_type_id',
                    'patient' => [
                        'id',
                        'nik',
                    ]
                ]
            ]);

        $this->assertEquals(2, $loginResponse->json('user.user_type_id'));
        $this->assertEquals($patient->id, $loginResponse->json('user.patient.id'));
        $this->assertNotEmpty($loginResponse->json('token'));

        // Cleanup
        $treatment->delete();
        $patient->delete();
        $user->tokens()->delete();
        $user->delete();
    }
}
