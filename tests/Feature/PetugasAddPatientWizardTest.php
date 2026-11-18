<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Province;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\TreatmentType;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetugasAddPatientWizardTest extends TestCase
{
    use DatabaseTransactions;

    private function createOfficerWithTerritory(int $officerTypeId = 3): array
    {
        $province = Province::first() ?? Province::create(['name' => 'Provinsi Test']);
        $district = District::where('province_id', $province->id)->first()
            ?? District::create(['name' => 'Kabupaten Test', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::where('district_id', $district->id)->first()
            ?? Subdistrict::create(['name' => 'Kecamatan Test', 'district_id' => $district->id]);
        $village = Village::where('subdistrict_id', $subdistrict->id)->first()
            ?? Village::create(['name' => 'Desa Test', 'subdistrict_id' => $subdistrict->id]);

        $puskesmas = Puskesmas::create([
            'name'           => 'Puskesmas Test ' . uniqid(),
            'subdistrict_id' => $subdistrict->id,
        ]);

        $officerUser = User::create([
            'name'           => 'Petugas PJTB ' . uniqid(),
            'username'       => 'petugas_' . uniqid(),
            'email'          => 'petugas_' . uniqid() . '@tbcare.id',
            'phone'          => '0812' . rand(10000000, 99999999),
            'password'       => Hash::make('password'),
            'user_type_id'   => 3,
            'gender'         => 'L',
            'place_of_birth' => 'Bandung',
            'date_of_birth'  => '1990-01-01',
            'is_active'      => true,
        ]);

        $officer = Officer::create([
            'user_id'         => $officerUser->id,
            'officer_type_id' => $officerTypeId,
            'puskesmas_id'    => $puskesmas->id,
        ]);

        return compact('province', 'district', 'subdistrict', 'village', 'puskesmas', 'officerUser', 'officer');
    }

    public function test_generate_username_endpoint_creates_clean_unique_username(): void
    {
        $setup = $this->createOfficerWithTerritory();
        Sanctum::actingAs($setup['officerUser']);

        // Case 1: Simple name
        $response = $this->getJson('/api/patients/generate-username?name=Budi%20Santoso');
        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.username', 'budisantoso')
            ->assertJsonPath('data.default_password', '123456');

        // Case 2: When username already exists, append number
        User::create([
            'name'           => 'Budi Santoso',
            'username'       => 'budisantoso',
            'email'          => 'budisantoso_' . uniqid() . '@test.com',
            'phone'          => '0813' . rand(10000000, 99999999),
            'password'       => Hash::make('password'),
            'user_type_id'   => 2,
            'gender'         => 'L',
            'place_of_birth' => 'Jakarta',
            'date_of_birth'  => '1995-05-05',
        ]);

        $responseCollision = $this->getJson('/api/patients/generate-username?name=Budi%20Santoso');
        $responseCollision->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.username', 'budisantoso1');
    }

    public function test_store_patient_saves_physical_health_address_and_creates_initial_treatment(): void
    {
        $setup = $this->createOfficerWithTerritory();
        Sanctum::actingAs($setup['officerUser']);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1 (Pasien Baru)',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
            'description'        => 'Test description',
        ]);

        $payload = [
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'name'                 => 'Ahmad Dahlan',
            'email'                => 'ahmad_' . uniqid() . '@example.com',
            'phone'                => '0821' . rand(10000000, 99999999),
            'gender'               => 'L',
            'place_of_birth'       => 'Tasikmalaya',
            'date_of_birth'        => '1998-07-15',
            'height'               => 170.5,
            'weight'               => 65.2,
            'blood_type'           => 'AB',
            'comorbidities'        => 'Hipertensi ringan, Diabetes tipe 2',
            'subdistrict_id'       => $setup['subdistrict']->id,
            'village_id'           => $setup['village']->id,
            'rw'                   => '05',
            'rt'                   => '02',
            'address'              => 'Jl. Perintis Kemerdekaan No. 45',
            'puskesmas_id'         => $setup['puskesmas']->id,
            'treatment_start_date' => Carbon::now()->subDays(2)->toDateString(),
            'treatment_type_id'    => $treatmentType->id,
            'medication_time'      => '08:00',
        ];

        $response = $this->postJson('/api/patients/store', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Data pasien baru berhasil ditambahkan.');

        $patientId = $response->json('data.id');
        $patient = Patient::with(['user', 'treatments'])->find($patientId);

        $this->assertNotNull($patient);
        $this->assertEquals(170.5, (float)$patient->height);
        $this->assertEquals(65.2, (float)$patient->weight);
        $this->assertEquals('AB', $patient->blood_type);
        $this->assertEquals('Hipertensi ringan, Diabetes tipe 2', $patient->comorbidities);
        $this->assertEquals($setup['village']->id, $patient->village_id);
        $this->assertEquals($setup['subdistrict']->id, $patient->subdistrict_id);

        // Verify User Account
        $user = $patient->user;
        $this->assertNotNull($user);
        $this->assertEquals('ahmaddahlan', $user->username);
        $this->assertTrue(Hash::check('123456', $user->password));
        $this->assertEquals(2, $user->user_type_id);

        // Verify Initial Treatment created automatically
        $this->assertCount(1, $patient->treatments);
        $treatment = $patient->treatments->first();
        $this->assertEquals($treatmentType->id, $treatment->treatment_type_id);
        $this->assertEquals('Berjalan', $treatment->treatment_status);
        $this->assertEquals('08:00', substr($treatment->medication_time, 0, 5));
        $this->assertEquals($payload['treatment_start_date'], $treatment->start_date);
        $this->assertNotNull($treatment->end_date);
    }

    public function test_kader_and_patient_are_forbidden_from_adding_patient(): void
    {
        $kaderUser = User::create([
            'name'           => 'Kader User',
            'username'       => 'kader_' . uniqid(),
            'email'          => 'kader_' . uniqid() . '@tbcare.id',
            'phone'          => '0812' . rand(10000000, 99999999),
            'password'       => Hash::make('password'),
            'user_type_id'   => 3,
        ]);
        Officer::create([
            'user_id'         => $kaderUser->id,
            'officer_type_id' => 4, // 4 = Kader
        ]);

        Sanctum::actingAs($kaderUser);
        $response = $this->postJson('/api/patients/store', [
            'name' => 'Should Fail',
        ]);
        $response->assertStatus(403);
    }

    public function test_validation_fails_for_invalid_physical_attributes(): void
    {
        $setup = $this->createOfficerWithTerritory();
        Sanctum::actingAs($setup['officerUser']);

        $payload = [
            'nik'            => '3278123456789012',
            'name'           => 'Invalid Attributes',
            'phone'          => '0812' . rand(10000000, 99999999),
            'gender'         => 'L',
            'place_of_birth' => 'Jakarta',
            'date_of_birth'  => '2000-01-01',
            'puskesmas_id'   => $setup['puskesmas']->id,
            'height'         => -10, // Invalid
            'blood_type'     => 'XYZ', // Invalid
        ];

        $response = $this->postJson('/api/patients/store', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['height', 'blood_type']);
    }

    public function test_nik_validation_rules_required_16_digits_and_unique(): void
    {
        $setup = $this->createOfficerWithTerritory();
        Sanctum::actingAs($setup['officerUser']);

        // 1. NIK is missing
        $resMissing = $this->postJson('/api/patients/store', [
            'name'           => 'No NIK',
            'phone'          => '0812' . rand(10000000, 99999999),
            'gender'         => 'L',
            'place_of_birth' => 'Tasikmalaya',
            'date_of_birth'  => '2000-01-01',
            'puskesmas_id'   => $setup['puskesmas']->id,
        ]);
        $resMissing->assertStatus(422)->assertJsonValidationErrors(['nik']);
        $this->assertEquals('NIK wajib diisi.', $resMissing->json('errors.nik.0'));

        // 2. NIK is not 16 digits
        $resDigits = $this->postJson('/api/patients/store', [
            'nik'            => '12345',
            'name'           => 'Short NIK',
            'phone'          => '0812' . rand(10000000, 99999999),
            'gender'         => 'L',
            'place_of_birth' => 'Tasikmalaya',
            'date_of_birth'  => '2000-01-01',
            'puskesmas_id'   => $setup['puskesmas']->id,
        ]);
        $resDigits->assertStatus(422)->assertJsonValidationErrors(['nik']);
        $this->assertEquals('NIK harus terdiri dari 16 digit.', $resDigits->json('errors.nik.0'));

        // 3. NIK is already registered
        $existingNik = '3278999988887777';
        Patient::create([
            'nik'          => $existingNik,
            'user_id'      => $setup['officerUser']->id,
            'puskesmas_id' => $setup['puskesmas']->id,
        ]);

        $resUnique = $this->postJson('/api/patients/store', [
            'nik'            => $existingNik,
            'name'           => 'Duplicate NIK',
            'phone'          => '0812' . rand(10000000, 99999999),
            'gender'         => 'L',
            'place_of_birth' => 'Tasikmalaya',
            'date_of_birth'  => '2000-01-01',
            'puskesmas_id'   => $setup['puskesmas']->id,
        ]);
        $resUnique->assertStatus(422)->assertJsonValidationErrors(['nik']);
        $this->assertEquals('NIK sudah terdaftar.', $resUnique->json('errors.nik.0'));
    }
}
