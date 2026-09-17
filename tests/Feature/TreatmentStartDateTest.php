<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\KaderArea;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TreatmentStartDateTest extends TestCase
{
    use DatabaseTransactions;

    protected $puskesmas;
    protected $subdistrict;
    protected $village;
    protected $adminUser;
    protected $kaderUser;
    protected $kaderOfficer;
    protected $kaderVillage;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup master references
        $this->puskesmas = Puskesmas::first();
        $this->subdistrict = Subdistrict::first();
        $this->village = Village::first();

        // 2. Setup Admin User (user_type_id = 1)
        $this->adminUser = User::create([
            'name'         => 'Admin Test TS',
            'username'     => 'admints_' . uniqid(),
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 1,
            'is_active'    => true,
        ]);

        // 3. Setup Kader User (user_type_id = 3, officer_type_id = 4)
        $district = District::first();
        $this->kaderUser = User::create([
            'name'         => 'Kader Test TS',
            'username'     => 'kaderts_' . uniqid(),
            'phone'        => '0813' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);

        $this->kaderOfficer = Officer::create([
            'user_id'         => $this->kaderUser->id,
            'officer_type_id' => 4,
            'puskesmas_id'    => $this->puskesmas->id,
            'district_id'     => $district?->id,
        ]);

        $this->kaderVillage = $this->village;

        // Assign territory: RW 05, RT 01
        KaderArea::create([
            'officer_id'     => $this->kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->kaderVillage->id,
            'rw'             => '05',
            'rt'             => '01',
        ]);
    }

    /**
     * Skenario 1: Simpan pasien baru dengan treatment_start_date hari ini -> BERHASIL (200 / 201)
     */
    public function test_create_patient_with_today_treatment_start_date_succeeds()
    {
        Sanctum::actingAs($this->adminUser);

        $today = Carbon::today()->format('Y-m-d');

        $response = $this->postJson('/api/patients/store', [
            'name'                 => 'Pasien Hari Ini',
            'phone'                => '0821' . rand(10000000, 99999999),
            'gender'               => 'L',
            'place_of_birth'       => 'Jakarta',
            'date_of_birth'        => '1995-05-10',
            'puskesmas_id'         => $this->puskesmas->id,
            'subdistrict_id'       => $this->subdistrict->id,
            'village_id'           => $this->village->id,
            'treatment_start_date' => $today,
        ]);

        $response->assertStatus(201);
        $patientId = $response->json('data.id');

        $this->assertDatabaseHas('patients', [
            'id'                   => $patientId,
            'treatment_start_date' => $today,
        ]);
    }

    /**
     * Skenario 2: Simpan pasien baru dengan treatment_start_date 30 hari yang lalu -> BERHASIL
     */
    public function test_create_patient_with_past_treatment_start_date_succeeds()
    {
        Sanctum::actingAs($this->adminUser);

        $pastDate = Carbon::today()->subDays(30)->format('Y-m-d');

        $response = $this->postJson('/api/patients/store', [
            'name'                 => 'Pasien 30 Hari Lalu',
            'phone'                => '0822' . rand(10000000, 99999999),
            'gender'               => 'P',
            'place_of_birth'       => 'Bandung',
            'date_of_birth'        => '1998-08-20',
            'puskesmas_id'         => $this->puskesmas->id,
            'treatment_start_date' => $pastDate,
        ]);

        $response->assertStatus(201);
        $patientId = $response->json('data.id');

        $this->assertDatabaseHas('patients', [
            'id'                   => $patientId,
            'treatment_start_date' => $pastDate,
        ]);
    }

    /**
     * Skenario 3: Simpan pasien baru dengan treatment_start_date besok / masa depan -> GAGAL (422)
     */
    public function test_create_patient_with_future_treatment_start_date_fails_validation()
    {
        Sanctum::actingAs($this->adminUser);

        $futureDate = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->postJson('/api/patients/store', [
            'name'                 => 'Pasien Masa Depan',
            'phone'                => '0823' . rand(10000000, 99999999),
            'gender'               => 'L',
            'place_of_birth'       => 'Surabaya',
            'date_of_birth'        => '2000-01-01',
            'puskesmas_id'         => $this->puskesmas->id,
            'treatment_start_date' => $futureDate,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['treatment_start_date']);
    }

    /**
     * Skenario 4: Simpan pasien baru dengan tanggal diagnosis dan tanggal mulai pengobatan berbeda -> BERHASIL
     */
    public function test_create_patient_with_different_diagnosis_and_treatment_start_date_succeeds()
    {
        Sanctum::actingAs($this->adminUser);

        $diagnosisDate = Carbon::today()->subDays(15)->format('Y-m-d');
        $treatmentDate = Carbon::today()->subDays(10)->format('Y-m-d');

        $response = $this->postJson('/api/patients/store', [
            'name'                 => 'Pasien Beda Tanggal',
            'phone'                => '0824' . rand(10000000, 99999999),
            'gender'               => 'P',
            'place_of_birth'       => 'Medan',
            'date_of_birth'        => '1992-03-15',
            'puskesmas_id'         => $this->puskesmas->id,
            'diagnosis_date'       => $diagnosisDate,
            'treatment_start_date' => $treatmentDate,
        ]);

        $response->assertStatus(201);
        $patientId = $response->json('data.id');

        $this->assertDatabaseHas('patients', [
            'id'                   => $patientId,
            'diagnosis_date'       => $diagnosisDate,
            'treatment_start_date' => $treatmentDate,
        ]);
    }

    /**
     * Skenario 5: Pasien lama dengan treatment_start_date = null -> Detail pasien tetap bisa dibuka, tidak crash
     */
    public function test_legacy_patient_with_null_treatment_start_date_returns_null_safely()
    {
        Sanctum::actingAs($this->adminUser);

        $legacyUser = User::create([
            'name'         => 'Pasien Lama Tanpa Tanggal',
            'username'     => 'legacypatient_' . uniqid(),
            'phone'        => '0825' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $legacyPatient = Patient::create([
            'user_id'              => $legacyUser->id,
            'puskesmas_id'         => $this->puskesmas->id,
            'diagnosis_date'       => '2025-01-01',
            'treatment_start_date' => null,
        ]);

        // 1. API show
        $responseShow = $this->getJson("/api/patients/{$legacyPatient->id}/show");
        $responseShow->assertStatus(200);
        $responseShow->assertJsonPath('data.treatment_start_date', null);

        // 2. API index
        $responseIndex = $this->getJson('/api/patients');
        $responseIndex->assertStatus(200);

        $found = collect($responseIndex->json('data'))->firstWhere('id', $legacyPatient->id);
        $this->assertNotNull($found);
        $this->assertNull($found['treatment_start_date']);
    }

    /**
     * Skenario 6: Edit pasien lama, isi treatment_start_date dengan tanggal valid -> BERHASIL
     */
    public function test_update_patient_with_valid_treatment_start_date_succeeds()
    {
        Sanctum::actingAs($this->adminUser);

        $user = User::create([
            'name'         => 'Pasien Untuk Edit',
            'username'     => 'editpatient_' . uniqid(),
            'phone'        => '0826' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $patient = Patient::create([
            'user_id'              => $user->id,
            'puskesmas_id'         => $this->puskesmas->id,
            'treatment_start_date' => null,
        ]);

        $validDate = Carbon::today()->subDays(5)->format('Y-m-d');

        $response = $this->postJson('/api/patients/store', [
            'patient_id'           => $patient->id,
            'name'                 => $user->name,
            'phone'                => $user->phone,
            'gender'               => 'L',
            'place_of_birth'       => 'Semarang',
            'date_of_birth'        => '1990-12-12',
            'puskesmas_id'         => $this->puskesmas->id,
            'treatment_start_date' => $validDate,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('patients', [
            'id'                   => $patient->id,
            'treatment_start_date' => $validDate,
        ]);
    }

    /**
     * Skenario 7: Edit pasien lama, isi treatment_start_date dengan tanggal masa depan -> GAGAL (422)
     */
    public function test_update_patient_with_future_treatment_start_date_fails_validation()
    {
        Sanctum::actingAs($this->adminUser);

        $user = User::create([
            'name'         => 'Pasien Untuk Edit Gagal',
            'username'     => 'editfail_' . uniqid(),
            'phone'        => '0827' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $patient = Patient::create([
            'user_id'              => $user->id,
            'puskesmas_id'         => $this->puskesmas->id,
            'treatment_start_date' => '2026-01-01',
        ]);

        $futureDate = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->postJson('/api/patients/store', [
            'patient_id'           => $patient->id,
            'name'                 => $user->name,
            'phone'                => $user->phone,
            'gender'               => 'L',
            'place_of_birth'       => 'Semarang',
            'date_of_birth'        => '1990-12-12',
            'puskesmas_id'         => $this->puskesmas->id,
            'treatment_start_date' => $futureDate,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['treatment_start_date']);
    }

    /**
     * Skenario 8: Kader mencoba mengakses / mengedit pasien di luar wilayah binaannya -> GAGAL (403)
     */
    public function test_kader_update_or_access_patient_outside_assigned_territory_fails_with_403()
    {
        Sanctum::actingAs($this->kaderUser);

        // Pasien di luar wilayah binaan Kader (RW 99, RT 99)
        $outsideUser = User::create([
            'name'         => 'Pasien Luar Wilayah',
            'username'     => 'outside_' . uniqid(),
            'phone'        => '0828' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $outsidePatient = Patient::create([
            'user_id'              => $outsideUser->id,
            'puskesmas_id'         => $this->puskesmas->id,
            'village_id'           => $this->kaderVillage->id,
            'rw'                   => '99',
            'rt'                   => '99',
            'treatment_start_date' => '2026-01-10',
        ]);

        // 1. Coba show detail pasien -> 403 Forbidden
        $responseShow = $this->getJson("/api/patients/{$outsidePatient->id}/show");
        $responseShow->assertStatus(403);

        // 2. Coba edit pasien -> 403 Forbidden
        $responseUpdate = $this->postJson('/api/patients/store', [
            'patient_id'           => $outsidePatient->id,
            'name'                 => 'Coba Ubah',
            'phone'                => $outsideUser->phone,
            'gender'               => 'L',
            'place_of_birth'       => 'Semarang',
            'date_of_birth'        => '1990-12-12',
            'puskesmas_id'         => $this->puskesmas->id,
            'village_id'           => $this->kaderVillage->id,
            'rw'                   => '99',
            'rt'                   => '99',
            'treatment_start_date' => Carbon::today()->format('Y-m-d'),
        ]);
        $responseUpdate->assertStatus(403);
    }

    /**
     * Skenario 9: Registrasi pasien baru melalui /api/register dengan treatment_start_date -> BERHASIL
     */
    public function test_patient_registration_with_treatment_start_date_succeeds()
    {
        $today = Carbon::today()->format('Y-m-d');
        $phone = '0829' . rand(10000000, 99999999);

        $response = $this->postJson('/api/register', [
            'name'                 => 'Pasien Register Mandiri',
            'phone'                => $phone,
            'gender'               => 'L',
            'puskesmas_id'         => $this->puskesmas->id,
            'subdistrict_id'       => $this->subdistrict->id,
            'treatment_start_date' => $today,
        ]);

        $response->assertStatus(201);
        $user = User::where('phone', $phone)->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('patients', [
            'user_id'              => $user->id,
            'treatment_start_date' => $today,
        ]);
    }

    /**
     * Skenario 10: Registrasi pasien baru melalui /api/register dengan tanggal masa depan -> GAGAL (422)
     */
    public function test_patient_registration_with_future_treatment_start_date_fails_validation()
    {
        $futureDate = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->postJson('/api/register', [
            'name'                 => 'Pasien Register Gagal',
            'phone'                => '0830' . rand(10000000, 99999999),
            'gender'               => 'P',
            'puskesmas_id'         => $this->puskesmas->id,
            'treatment_start_date' => $futureDate,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['treatment_start_date']);
    }
}
