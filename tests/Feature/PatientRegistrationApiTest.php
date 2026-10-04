<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Tests\TestCase;

class PatientRegistrationApiTest extends TestCase
{
    public function test_cascading_territory_endpoints()
    {
        // 1. Provinces
        $response = $this->getJson('/api/provinces');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name']
                ]
            ]);

        // 2. Districts (Jawa Barat = 1)
        $response = $this->getJson('/api/provinces/1/districts');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'province_id']
                ]
            ]);

        // 3. Subdistricts (Kota Tasikmalaya = 27)
        $response = $this->getJson('/api/districts/27/subdistricts');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'district_id']
                ]
            ]);

        // 4. Villages (Kawalu = 41)
        $response = $this->getJson('/api/subdistricts/41/villages');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => ['id', 'name', 'subdistrict_id']
                ]
            ]);
    }

    public function test_patient_registration_validation()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'nik',
                'phone',
                'puskesmas_id',
                'treatment_start_date',
                'subdistrict_id',
                'village_id',
            ]);
    }

    public function test_patient_registration_full_flow()
    {
        $uniqueNik = '3278' . str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);
        $uniquePhone = '0877' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

        $payload = [
            'name'                 => 'Testing Pasien Otomatis',
            'nik'                  => $uniqueNik,
            'phone'                => $uniquePhone,
            'puskesmas_id'         => 1,
            'treatment_start_date' => now()->subDays(5)->format('Y-m-d'),
            'province_id'          => 1,
            'district_id'          => 27,
            'subdistrict_id'       => 41,
            'village_id'           => 9,
        ];

        // 1. Submit Registration
        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'username',
                    'password',
                ],
                'user' => [
                    'id',
                    'name',
                    'username',
                    'phone',
                ]
            ]);

        $responseData = $response->json();
        $username = $responseData['data']['username'];
        $password = $responseData['data']['password'];

        $this->assertNotEmpty($username);
        $this->assertEquals('123456', $password);

        // Verify user in DB has user_type_id == 2 (Pasien)
        $user = User::where('username', $username)->first();
        $this->assertNotNull($user);
        $this->assertEquals(2, $user->user_type_id);

        // Verify patient record in DB
        $patient = Patient::where('user_id', $user->id)->first();
        $this->assertNotNull($patient);
        $this->assertEquals($uniqueNik, $patient->nik);
        $this->assertEquals(41, $patient->subdistrict_id);
        $this->assertEquals(9, $patient->village_id);

        // 2. Test Duplicate NIK
        $dupNikPayload = $payload;
        $dupNikPayload['phone'] = '0888' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $dupNikResponse = $this->postJson('/api/register', $dupNikPayload);
        $dupNikResponse->assertStatus(422)
            ->assertJsonValidationErrors(['nik']);

        // 3. Test Duplicate Phone
        $dupPhonePayload = $payload;
        $dupPhonePayload['nik'] = '3278' . str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);
        $dupPhoneResponse = $this->postJson('/api/register', $dupPhonePayload);
        $dupPhoneResponse->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);

        // 4. Test Login with newly generated credentials
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

        // Cleanup
        $patient->treatments()->delete();
        $patient->delete();
        $user->tokens()->delete();
        $user->delete();
    }
}
