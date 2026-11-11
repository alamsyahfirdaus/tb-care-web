<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Patient;
use App\Models\Province;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PatientProfileRefinementTest extends TestCase
{
    use DatabaseTransactions;

    protected $patientUser;
    protected $patient;
    protected $puskesmas1;
    protected $puskesmas2;
    protected $village;
    protected $subdistrict;

    protected function setUp(): void
    {
        parent::setUp();

        $province = Province::firstOrCreate(
            ['name' => 'Jawa Barat'],
            ['id' => 32]
        );

        $district = District::firstOrCreate(
            ['name' => 'Kota Tasikmalaya'],
            ['province_id' => $province->id]
        );

        $this->subdistrict = Subdistrict::firstOrCreate(
            ['name' => 'Tamansari'],
            ['district_id' => $district->id]
        );

        $this->village = Village::firstOrCreate(
            ['name' => 'Setiawargi'],
            ['subdistrict_id' => $this->subdistrict->id]
        );

        $this->puskesmas1 = Puskesmas::firstOrCreate(
            ['name' => 'Puskesmas Tamansari'],
            ['subdistrict_id' => $this->subdistrict->id]
        );

        $this->puskesmas2 = Puskesmas::firstOrCreate(
            ['name' => 'Puskesmas Cibeureum'],
            ['subdistrict_id' => $this->subdistrict->id]
        );

        $this->patientUser = User::create([
            'name'         => 'Pasien Testing Refinement',
            'username'     => 'pasientest_refine_' . uniqid(),
            'email'        => 'pasientest_refine_' . uniqid() . '@example.com',
            'phone'        => '0812' . rand(10000000, 99999999),
            'gender'       => 'L',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 2, // Pasien
            'is_active'    => true,
        ]);

        $this->patient = Patient::create([
            'user_id'        => $this->patientUser->id,
            'nik'            => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'   => $this->puskesmas1->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village->id,
            'rt'             => '001',
            'rw'             => '002',
            'address'        => 'Jl. Sukamaju No. 12',
            'height'         => 165.00,
            'weight'         => 60.50,
            'blood_type'     => 'O',
            'occupation'     => 'Dosen',
        ]);
    }

    public function test_profile_show_returns_new_physical_and_occupational_fields()
    {
        $response = $this->actingAs($this->patientUser, 'sanctum')
            ->getJson('/api/profile/show');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'name'         => 'Pasien Testing Refinement',
                'height'       => 165.0,
                'weight'       => 60.5,
                'blood_type'   => 'O',
                'occupation'   => 'Dosen',
                'puskesmas_id' => $this->puskesmas1->id,
            ],
        ]);
    }

    public function test_single_request_updates_all_fields_including_puskesmas_and_new_fields()
    {
        $payload = [
            'name'         => 'Pasien Terupdate Lengkap',
            'phone'        => $this->patientUser->phone,
            'nik'          => $this->patient->nik,
            'puskesmas_id' => $this->puskesmas2->id,
            'village_id'   => $this->village->id,
            'rt'           => '005',
            'rw'           => '006',
            'address'      => 'Jl. Babakan Baru No. 88',
            'height'       => 172.5,
            'weight'       => 65.0,
            'blood_type'   => 'AB',
            'occupation'   => 'Guru',
        ];

        $response = $this->actingAs($this->patientUser, 'sanctum')
            ->putJson('/api/profile/update', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'name'         => 'Pasien Terupdate Lengkap',
                'height'       => 172.5,
                'weight'       => 65.0,
                'blood_type'   => 'AB',
                'occupation'   => 'Guru',
                'puskesmas_id' => $this->puskesmas2->id,
                'rt'           => '005',
                'rw'           => '006',
            ],
        ]);

        $this->patient->refresh();
        $this->assertEquals(172.5, $this->patient->height);
        $this->assertEquals(65.0, $this->patient->weight);
        $this->assertEquals('AB', $this->patient->blood_type);
        $this->assertEquals('Guru', $this->patient->occupation);
        $this->assertEquals($this->puskesmas2->id, $this->patient->puskesmas_id);
    }

    public function test_validation_rejects_invalid_blood_type_and_out_of_range_height_weight()
    {
        $payload = [
            'blood_type' => 'XYZ', // Tidak valid
            'height'     => 10,    // Di bawah 30 cm
            'weight'     => 500,   // Di atas 300 kg
        ];

        $response = $this->actingAs($this->patientUser, 'sanctum')
            ->putJson('/api/profile/update', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['blood_type', 'height', 'weight']);
    }
}
