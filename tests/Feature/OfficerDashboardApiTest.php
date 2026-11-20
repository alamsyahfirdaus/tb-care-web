<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Province;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfficerDashboardApiTest extends TestCase
{
    public function test_unauthenticated_cannot_access_officer_dashboard()
    {
        $response = $this->getJson('/api/officer/dashboard');
        $response->assertStatus(401);
    }

    public function test_patient_user_cannot_access_officer_dashboard()
    {
        $patientUser = User::create([
            'name'         => 'Test Pasien',
            'username'     => 'testpasien_' . uniqid(),
            'email'        => 'pasien_' . uniqid() . '@example.com',
            'password'     => bcrypt('secret'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);
        Sanctum::actingAs($patientUser);

        $response = $this->getJson('/api/officer/dashboard');
        $response->assertStatus(403);
    }

    public function test_pjtb_can_access_officer_dashboard_with_village_distribution()
    {
        $province = Province::firstOrCreate(['id' => 32], ['name' => 'Jawa Barat']);
        $district = District::firstOrCreate(['id' => 3278], ['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::firstOrCreate(['id' => 327801], ['name' => 'Tamansari', 'district_id' => $district->id]);
        $villageA = Village::firstOrCreate(['id' => 3278011], ['name' => 'Setiawargi', 'subdistrict_id' => $subdistrict->id]);
        $villageB = Village::firstOrCreate(['id' => 3278012], ['name' => 'Mugarsari', 'subdistrict_id' => $subdistrict->id]);

        $puskesmas = Puskesmas::create([
            'name'           => 'Puskesmas Test ' . uniqid(),
            'code'           => 'P' . rand(1000, 9999),
            'address'        => 'Jl. Test',
            'subdistrict_id' => $subdistrict->id,
        ]);

        $pjtbUser = User::create([
            'name'         => 'PJTB Test',
            'username'     => 'pjtb_' . uniqid(),
            'email'        => 'pjtb_' . uniqid() . '@example.com',
            'password'     => bcrypt('secret'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);

        $officer = Officer::create([
            'user_id'         => $pjtbUser->id,
            'officer_type_id' => 3, // PJTB
            'puskesmas_id'    => $puskesmas->id,
            'district_id'     => $district->id,
        ]);

        // Buat 2 pasien di puskesmas ini
        $patientUser1 = User::create([
            'name'         => 'Pasien Satu',
            'username'     => 'p1_' . uniqid(),
            'email'        => 'p1_' . uniqid() . '@example.com',
            'password'     => bcrypt('secret'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $p1 = Patient::create([
            'user_id'        => $patientUser1->id,
            'puskesmas_id'   => $puskesmas->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $villageA->id,
            'nik'            => '3278000000000001',
        ]);

        // Buat pengobatan aktif
        PatientTreatment::create([
            'patient_id'       => $p1->id,
            'treatment_status' => 'Berjalan',
            'start_date'       => now()->subMonths(1)->toDateString(),
            'end_date'         => now()->addMonths(5)->toDateString(),
        ]);

        // Pasien 2: Perlu Tindak Lanjut
        $patientUser2 = User::create([
            'name'         => 'Pasien Dua',
            'username'     => 'p2_' . uniqid(),
            'email'        => 'p2_' . uniqid() . '@example.com',
            'password'     => bcrypt('secret'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $p2 = Patient::create([
            'user_id'        => $patientUser2->id,
            'puskesmas_id'   => $puskesmas->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $villageB->id,
            'nik'            => '3278000000000002',
        ]);

        PatientTreatment::create([
            'patient_id'       => $p2->id,
            'treatment_status' => 'perlu_tindak_lanjut',
            'start_date'       => now()->subMonths(7)->toDateString(),
            'end_date'         => now()->subDays(1)->toDateString(),
        ]);

        Sanctum::actingAs($pjtbUser);

        $response = $this->getJson('/api/officer/dashboard');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('village', $data['regional_statistics']['level']);
        $this->assertEquals('Jumlah Pasien Berdasarkan Wilayah', $data['regional_statistics']['title']);
        $this->assertEquals(2, $data['total_patients']);
        $this->assertEquals(1, $data['active_treatments']);
        $this->assertEquals(1, $data['follow_up_count']);
        $this->assertCount(1, $data['follow_up_patients']);
        $this->assertEquals('Pasien Dua', $data['follow_up_patients'][0]['name']);
        $this->assertEquals('Perlu Tindak Lanjut', $data['follow_up_patients'][0]['treatment_status']);

        // Verifikasi endpoint region-patients
        $regionRes = $this->getJson('/api/officer/dashboard/region-patients?level=village&id=' . $villageA->id);
        $regionRes->assertStatus(200);
        $this->assertEquals('Setiawargi', $regionRes->json('region_name'));
        $this->assertCount(1, $regionRes->json('data'));
        $this->assertEquals('Pasien Satu', $regionRes->json('data.0.name'));
    }

    public function test_dinkes_kab_kota_sees_subdistrict_level_distribution()
    {
        $province = Province::firstOrCreate(['id' => 32], ['name' => 'Jawa Barat']);
        $district = District::firstOrCreate(['id' => 3278], ['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);

        $dinkesKabUser = User::create([
            'name'         => 'Dinkes Kota Test',
            'username'     => 'dinkeskota_' . uniqid(),
            'email'        => 'dinkeskota_' . uniqid() . '@example.com',
            'password'     => bcrypt('secret'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);

        Officer::create([
            'user_id'         => $dinkesKabUser->id,
            'officer_type_id' => 2, // Dinkes Kab/Kota
            'district_id'     => $district->id,
        ]);

        Sanctum::actingAs($dinkesKabUser);

        $response = $this->getJson('/api/officer/dashboard');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('subdistrict', $data['regional_statistics']['level']);
        $this->assertEquals('Jumlah Pasien Berdasarkan Wilayah', $data['regional_statistics']['title']);
    }

    public function test_dinkes_provinsi_sees_district_level_distribution()
    {
        $province = Province::firstOrCreate(['id' => 32], ['name' => 'Jawa Barat']);
        $district = District::firstOrCreate(['id' => 3278], ['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);

        $dinkesProvUser = User::create([
            'name'         => 'Dinkes Provinsi Test',
            'username'     => 'dinkesprov_' . uniqid(),
            'email'        => 'dinkesprov_' . uniqid() . '@example.com',
            'password'     => bcrypt('secret'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);

        Officer::create([
            'user_id'         => $dinkesProvUser->id,
            'officer_type_id' => 1, // Dinkes Provinsi
            'district_id'     => $district->id,
        ]);

        Sanctum::actingAs($dinkesProvUser);

        $response = $this->getJson('/api/officer/dashboard');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('district', $data['regional_statistics']['level']);
        $this->assertEquals('Jumlah Pasien Berdasarkan Wilayah', $data['regional_statistics']['title']);
    }
}
