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
use App\Models\PatientTreatment;
use App\Models\TreatmentVisit;
use App\Models\TreatmentType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaderDataScopingTest extends TestCase
{
    use DatabaseTransactions;

    protected $puskesmasA;
    protected $puskesmasB;
    protected $subdistrict;
    protected $villageA;
    protected $villageB;
    protected $adminUser;
    protected $kaderUser;
    protected $kaderOfficer;
    protected $pjtbUser;
    protected $pjtbOfficer;
    protected $patientUser;
    protected $patientSelf;
    protected $patientInScope1;
    protected $patientInScope2;
    protected $patientInScopeNullRt;
    protected $patientDiffRt;
    protected $patientDiffRw;
    protected $patientDiffVillage;
    protected $patientDiffPuskesmas;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Master Data
        $district = District::first();
        $this->subdistrict = Subdistrict::find(44) ?? Subdistrict::first();
        $this->puskesmasA = Puskesmas::find(1) ?? Puskesmas::first();
        $this->puskesmasB = Puskesmas::find(2) ?? Puskesmas::orderBy('id', 'desc')->first();
        $this->villageA = Village::find(1) ?? Village::first();
        $this->villageB = Village::find(2) ?? Village::orderBy('id', 'desc')->first();

        // 2. Setup Admin User (user_type_id = 1)
        $this->adminUser = User::create([
            'name'         => 'Super Admin Testing',
            'username'     => 'admintest_' . uniqid(),
            'phone'        => '0811' . rand(10000000, 99999999),
            'email'        => 'admin_' . uniqid() . '@test.com',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 1,
            'gender'       => 'L',
        ]);

        // 3. Setup Kader User & Officer
        $this->kaderUser = User::create([
            'name'         => 'Kader Testing',
            'username'     => 'kadertest_' . uniqid(),
            'phone'        => '0812' . rand(10000000, 99999999),
            'email'        => 'kader_' . uniqid() . '@test.com',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 3, // Petugas
            'gender'       => 'P',
        ]);

        $this->kaderOfficer = Officer::create([
            'user_id'         => $this->kaderUser->id,
            'officer_type_id' => 4, // Kader
            'puskesmas_id'    => $this->puskesmasA->id,
            'district_id'     => $district->id,
        ]);

        // Area Binaan Kader:
        // Area 1: Desa A, RW 05, RT 01
        KaderArea::create([
            'officer_id'     => $this->kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->villageA->id,
            'rw'             => '05',
            'rt'             => '01',
        ]);

        // Area 2: Desa A, RW 05, RT 02 (RT lain yang di-assign)
        KaderArea::create([
            'officer_id'     => $this->kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->villageA->id,
            'rw'             => '05',
            'rt'             => '02',
        ]);

        // Area 3: Desa A, RW 06, RT null (Membina seluruh RW 06 tanpa batasan RT)
        KaderArea::create([
            'officer_id'     => $this->kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->villageA->id,
            'rw'             => '06',
            'rt'             => null,
        ]);

        // 4. Setup PJTB User & Officer (Puskesmas A)
        $this->pjtbUser = User::create([
            'name'         => 'PJTB Testing',
            'username'     => 'pjtbtest_' . uniqid(),
            'phone'        => '0813' . rand(10000000, 99999999),
            'email'        => 'pjtb_' . uniqid() . '@test.com',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 3, // Petugas
            'gender'       => 'L',
        ]);

        $this->pjtbOfficer = Officer::create([
            'user_id'         => $this->pjtbUser->id,
            'officer_type_id' => 3, // PJTB
            'puskesmas_id'    => $this->puskesmasA->id,
            'district_id'     => $district->id,
        ]);

        // 5. Setup Pasien User & Patients
        $this->patientUser = User::create([
            'name'         => 'Pasien Akun Sendiri',
            'username'     => 'pasientest_' . uniqid(),
            'phone'        => '0814' . rand(10000000, 99999999),
            'email'        => 'pasien_' . uniqid() . '@test.com',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 2, // Pasien
            'gender'       => 'L',
        ]);

        $this->patientSelf = Patient::create([
            'user_id'        => $this->patientUser->id,
            'nik'            => '3201' . rand(100000000000, 999999999999),
            'address'        => 'Jl. Sukamaju No. 1',
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->villageA->id,
            'rw'             => '05',
            'rt'             => '01',
            'puskesmas_id'   => $this->puskesmasA->id,
        ]);

        $createPatient = function ($name, $villageId, $rw, $rt, $puskesmasId) {
            $u = User::create([
                'name'         => $name,
                'username'     => 'u_' . uniqid(),
                'phone'        => '0815' . rand(10000000, 99999999),
                'email'        => 'u_' . uniqid() . '@test.com',
                'password'     => bcrypt('secret123'),
                'user_type_id' => 2,
                'gender'       => 'P',
            ]);
            return Patient::create([
                'user_id'        => $u->id,
                'nik'            => '3201' . rand(100000000000, 999999999999),
                'address'        => 'Alamat ' . $name,
                'subdistrict_id' => $this->subdistrict->id,
                'village_id'     => $villageId,
                'rw'             => $rw,
                'rt'             => $rt,
                'puskesmas_id'   => $puskesmasId,
            ]);
        };

        // Pasien 1: Desa A, RW 05, RT 01 (In Scope Kader)
        $this->patientInScope1 = $createPatient('Pasien In Scope 1', $this->villageA->id, '05', '01', $this->puskesmasA->id);

        // Pasien 2: Desa A, RW 05, RT 02 (In Scope Kader - RT 02 di-assign)
        $this->patientInScope2 = $createPatient('Pasien In Scope 2', $this->villageA->id, '05', '02', $this->puskesmasA->id);

        // Pasien Null RT: Desa A, RW 06, RT 04 (In Scope Kader - Area RW 06 RT is null)
        $this->patientInScopeNullRt = $createPatient('Pasien Null RT Scope', $this->villageA->id, '06', '04', $this->puskesmasA->id);

        // Pasien Luar RT: Desa A, RW 05, RT 03 (Kader hanya bina RT 01 & 02 pada RW 05)
        $this->patientDiffRt = $createPatient('Pasien Diff RT', $this->villageA->id, '05', '03', $this->puskesmasA->id);

        // Pasien Luar RW: Desa A, RW 07, RT 01 (Kader tidak membina RW 07)
        $this->patientDiffRw = $createPatient('Pasien Diff RW', $this->villageA->id, '07', '01', $this->puskesmasA->id);

        // Pasien Luar Desa: Desa B, RW 05, RT 01
        $this->patientDiffVillage = $createPatient('Pasien Diff Village', $this->villageB->id, '05', '01', $this->puskesmasA->id);

        // Pasien Luar Puskesmas: Desa A, RW 05, RT 01 tetapi Puskesmas B
        $this->patientDiffPuskesmas = $createPatient('Pasien Diff Puskesmas', $this->villageA->id, '05', '01', $this->puskesmasB->id);
    }

    /**
     * TC-01: Kader + RT sesuai -> PASS
     */
    public function test_tc01_kader_access_assigned_rt_pass()
    {
        $this->assertTrue($this->patientInScope1->isAccessibleBy($this->kaderUser));

        Sanctum::actingAs($this->kaderUser, ['*']);
        $response = $this->getJson('/api/patients/' . $this->patientInScope1->id . '/show');
        $response->assertStatus(200);

        $indexResponse = $this->getJson('/api/patients');
        $indexResponse->assertStatus(200);
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();
        $this->assertContains($this->patientInScope1->id, $patientIds);
    }

    /**
     * TC-02: Kader + RT lain yang di-assign -> PASS
     */
    public function test_tc02_kader_access_other_assigned_rt_pass()
    {
        $this->assertTrue($this->patientInScope2->isAccessibleBy($this->kaderUser));

        Sanctum::actingAs($this->kaderUser, ['*']);
        $response = $this->getJson('/api/patients/' . $this->patientInScope2->id . '/show');
        $response->assertStatus(200);

        $indexResponse = $this->getJson('/api/patients');
        $indexResponse->assertStatus(200);
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();
        $this->assertContains($this->patientInScope2->id, $patientIds);
    }

    /**
     * TC-03: Kader + RT di luar assignment -> DENIED
     */
    public function test_tc03_kader_access_unassigned_rt_denied()
    {
        $this->assertFalse($this->patientDiffRt->isAccessibleBy($this->kaderUser));

        Sanctum::actingAs($this->kaderUser, ['*']);
        $response = $this->getJson('/api/patients/' . $this->patientDiffRt->id . '/show');
        $response->assertStatus(403);

        $indexResponse = $this->getJson('/api/patients');
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();
        $this->assertNotContains($this->patientDiffRt->id, $patientIds);
    }

    /**
     * TC-04: Kader + RW berbeda -> DENIED
     */
    public function test_tc04_kader_access_different_rw_denied()
    {
        $this->assertFalse($this->patientDiffRw->isAccessibleBy($this->kaderUser));

        Sanctum::actingAs($this->kaderUser, ['*']);
        $response = $this->getJson('/api/patients/' . $this->patientDiffRw->id . '/show');
        $response->assertStatus(403);

        $indexResponse = $this->getJson('/api/patients');
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();
        $this->assertNotContains($this->patientDiffRw->id, $patientIds);
    }

    /**
     * TC-05: Kader + Desa berbeda -> DENIED
     */
    public function test_tc05_kader_access_different_village_denied()
    {
        $this->assertFalse($this->patientDiffVillage->isAccessibleBy($this->kaderUser));

        Sanctum::actingAs($this->kaderUser, ['*']);
        $response = $this->getJson('/api/patients/' . $this->patientDiffVillage->id . '/show');
        $response->assertStatus(403);

        $indexResponse = $this->getJson('/api/patients');
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();
        $this->assertNotContains($this->patientDiffVillage->id, $patientIds);
    }

    /**
     * TC-06: Detail pasien luar scope -> DENIED
     */
    public function test_tc06_detail_patient_outside_scope_denied()
    {
        Sanctum::actingAs($this->kaderUser, ['*']);

        // IDOR attempt on each out-of-scope patient
        $this->getJson('/api/patients/' . $this->patientDiffRt->id . '/show')->assertStatus(403);
        $this->getJson('/api/patients/' . $this->patientDiffRw->id . '/show')->assertStatus(403);
        $this->getJson('/api/patients/' . $this->patientDiffVillage->id . '/show')->assertStatus(403);
        $this->getJson('/api/patients/' . $this->patientDiffPuskesmas->id . '/show')->assertStatus(403);
    }

    /**
     * TC-07: Delete pasien luar scope -> DENIED
     */
    public function test_tc07_delete_patient_outside_scope_denied()
    {
        Sanctum::actingAs($this->kaderUser, ['*']);
        $response = $this->deleteJson('/api/patients/' . $this->patientDiffRt->id . '/delete');
        $response->assertStatus(403);

        $this->assertDatabaseHas('patients', ['id' => $this->patientDiffRt->id]);
    }

    /**
     * TC-08: Create pasien luar scope -> DENIED
     */
    public function test_tc08_create_patient_outside_scope_denied()
    {
        Sanctum::actingAs($this->kaderUser, ['*']);

        $payload = [
            'name'           => 'Pasien Luar Wilayah',
            'phone'          => '081299998888',
            'nik'            => '3201999988887777',
            'gender'         => 'L',
            'place_of_birth' => 'Bandung',
            'date_of_birth'  => '1995-01-01',
            'address'        => 'Jl. Uji No. 9',
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->villageA->id,
            'rw'             => '05',
            'rt'             => '03', // Outside Kader's assigned RT 01 and 02
            'puskesmas_id'   => $this->puskesmasA->id,
        ];

        $response = $this->postJson('/api/patients/store', $payload);
        $response->assertStatus(403);
    }

    /**
     * TC-09: Update pasien keluar scope -> DENIED
     */
    public function test_tc09_update_patient_outside_scope_denied()
    {
        Sanctum::actingAs($this->kaderUser, ['*']);

        // Attempt to move in-scope patient to out-of-scope RW 07
        $payload = [
            'patient_id'     => $this->patientInScope1->id,
            'name'           => $this->patientInScope1->user->name,
            'phone'          => $this->patientInScope1->user->phone,
            'gender'         => 'P',
            'place_of_birth' => 'Bandung',
            'date_of_birth'  => '1995-01-01',
            'nik'            => $this->patientInScope1->nik,
            'address'        => 'Jl. Berubah Wilayah',
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->villageA->id,
            'rw'             => '07', // Outside Kader's assigned territory
            'rt'             => '01',
            'puskesmas_id'   => $this->puskesmasA->id,
        ];

        $response = $this->postJson('/api/patients/store', $payload);
        $response->assertStatus(403);
    }

    /**
     * TC-10: Treatment pasien luar scope -> DENIED
     */
    public function test_tc10_treatment_patient_outside_scope_denied()
    {
        $treatmentType = TreatmentType::first();

        // Treatment in scope
        $treatmentInScope = PatientTreatment::create([
            'patient_id'        => $this->patientInScope1->id,
            'treatment_type_id' => $treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'diagnosis_date'    => '2026-01-01',
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-07-01',
        ]);

        // Treatment out of scope
        $treatmentOutOfScope = PatientTreatment::create([
            'patient_id'        => $this->patientDiffRt->id,
            'treatment_type_id' => $treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'diagnosis_date'    => '2026-01-01',
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-07-01',
        ]);

        Sanctum::actingAs($this->kaderUser, ['*']);

        // In scope -> 200
        $this->getJson('/api/treatments/' . $treatmentInScope->id . '/show')->assertStatus(200);

        // Out of scope -> 403
        $this->getJson('/api/treatments/' . $treatmentOutOfScope->id . '/show')->assertStatus(403);

        // Store treatment for out-of-scope patient -> 403
        $payload = [
            'patient_id'        => $this->patientDiffRt->id,
            'treatment_type_id' => $treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'diagnosis_date'    => '2026-01-01',
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-07-01',
            'medication_time'   => '08:00',
        ];
        $this->postJson('/api/treatments/store', $payload)->assertStatus(403);
    }

    /**
     * TC-11: Treatment visit pasien luar scope -> DENIED
     */
    public function test_tc11_treatment_visit_patient_outside_scope_denied()
    {
        $treatmentType = TreatmentType::first();

        $treatmentOutOfScope = PatientTreatment::create([
            'patient_id'        => $this->patientDiffRt->id,
            'treatment_type_id' => $treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'diagnosis_date'    => '2026-01-01',
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-07-01',
        ]);

        Sanctum::actingAs($this->kaderUser, ['*']);

        $payload = [
            'patient_treatment_id' => $treatmentOutOfScope->id,
            'visit_date'           => date('Y-m-d'),
            'visit_type'           => 'Home Visit',
            'notes'                => 'Kunjungan uji luar wilayah',
        ];

        $response = $this->postJson('/api/visits/store', $payload);
        $response->assertStatus(403);
    }

    /**
     * TC-12: Consultation recipient scoped -> PASS
     */
    public function test_tc12_consultation_recipient_scoped_pass()
    {
        Sanctum::actingAs($this->kaderUser, ['*']);

        $response = $this->getJson('/api/consultations/recipients');
        $response->assertStatus(200);

        $recipientUserIds = collect($response->json('data'))->pluck('id')->all();

        // In scope patients must be present
        $this->assertContains($this->patientInScope1->user_id, $recipientUserIds);
        $this->assertContains($this->patientInScope2->user_id, $recipientUserIds);

        // Out of scope patients must NOT be present
        $this->assertNotContains($this->patientDiffRt->user_id, $recipientUserIds);
        $this->assertNotContains($this->patientDiffRw->user_id, $recipientUserIds);
        $this->assertNotContains($this->patientDiffVillage->user_id, $recipientUserIds);
        $this->assertNotContains($this->patientDiffPuskesmas->user_id, $recipientUserIds);
    }

    /**
     * TC-13: Adherence scoped -> PASS
     */
    public function test_tc13_adherence_scoped_pass()
    {
        $treatmentType = TreatmentType::first();

        // In-scope patient active treatment
        PatientTreatment::create([
            'patient_id'        => $this->patientInScope1->id,
            'treatment_type_id' => $treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'diagnosis_date'    => '2026-01-01',
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-07-01',
        ]);

        // Out-of-scope patient active treatment
        PatientTreatment::create([
            'patient_id'        => $this->patientDiffRt->id,
            'treatment_type_id' => $treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'diagnosis_date'    => '2026-01-01',
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-07-01',
        ]);

        Sanctum::actingAs($this->kaderUser, ['*']);

        $response = $this->getJson('/api/patients/adherence');
        $response->assertStatus(200);

        // Kader only has 1 active treatment in scope (patientInScope1), not 2
        $this->assertEquals(1, $response->json('data.total_treatment'));
    }

    /**
     * TC-14: PJTB tetap melihat seluruh pasien Puskesmas -> PASS
     */
    public function test_tc14_pjtb_can_see_all_patients_in_puskesmas_pass()
    {
        // Puskesmas A patients should all be accessible
        $this->assertTrue($this->patientInScope1->isAccessibleBy($this->pjtbUser));
        $this->assertTrue($this->patientInScope2->isAccessibleBy($this->pjtbUser));
        $this->assertTrue($this->patientDiffRt->isAccessibleBy($this->pjtbUser));
        $this->assertTrue($this->patientDiffRw->isAccessibleBy($this->pjtbUser));
        $this->assertTrue($this->patientDiffVillage->isAccessibleBy($this->pjtbUser));

        // Puskesmas B patient should NOT be accessible
        $this->assertFalse($this->patientDiffPuskesmas->isAccessibleBy($this->pjtbUser));

        Sanctum::actingAs($this->pjtbUser, ['*']);
        $indexResponse = $this->getJson('/api/patients');
        $indexResponse->assertStatus(200);
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();

        $this->assertContains($this->patientInScope1->id, $patientIds);
        $this->assertContains($this->patientDiffRt->id, $patientIds);
        $this->assertContains($this->patientDiffVillage->id, $patientIds);
        $this->assertNotContains($this->patientDiffPuskesmas->id, $patientIds);
    }

    /**
     * TC-15: Admin tetap melihat seluruh pasien -> PASS
     */
    public function test_tc15_admin_can_see_all_patients_pass()
    {
        $this->assertTrue($this->patientInScope1->isAccessibleBy($this->adminUser));
        $this->assertTrue($this->patientDiffRt->isAccessibleBy($this->adminUser));
        $this->assertTrue($this->patientDiffVillage->isAccessibleBy($this->adminUser));
        $this->assertTrue($this->patientDiffPuskesmas->isAccessibleBy($this->adminUser));

        Sanctum::actingAs($this->adminUser, ['*']);
        $indexResponse = $this->getJson('/api/patients');
        $indexResponse->assertStatus(200);
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();

        // Admin can see patients across any puskesmas, village, RW, RT
        $this->assertContains($this->patientInScope1->id, $patientIds);
        $this->assertContains($this->patientDiffRt->id, $patientIds);
        $this->assertContains($this->patientDiffVillage->id, $patientIds);
        $this->assertContains($this->patientDiffPuskesmas->id, $patientIds);
    }

    /**
     * TC-16: Pasien hanya melihat dirinya sendiri -> PASS
     */
    public function test_tc16_patient_can_only_see_self_pass()
    {
        $this->assertTrue($this->patientSelf->isAccessibleBy($this->patientUser));
        $this->assertFalse($this->patientInScope1->isAccessibleBy($this->patientUser));
        $this->assertFalse($this->patientDiffRt->isAccessibleBy($this->patientUser));

        Sanctum::actingAs($this->patientUser, ['*']);
        $indexResponse = $this->getJson('/api/patients');
        $indexResponse->assertStatus(200);
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();

        $this->assertContains($this->patientSelf->id, $patientIds);
        $this->assertNotContains($this->patientInScope1->id, $patientIds);
        $this->assertNotContains($this->patientDiffRt->id, $patientIds);

        // IDOR attempt on another patient returns 403
        $this->getJson('/api/patients/' . $this->patientInScope1->id . '/show')->assertStatus(403);
    }

    /**
     * TC-17: Kader dengan RT NULL dapat melihat seluruh RT pada RW -> PASS
     */
    public function test_tc17_kader_with_null_rt_can_access_all_rt_in_rw_pass()
    {
        // Kader covers RW 06 with rt=null, patient is in RW 06 RT 04
        $this->assertTrue($this->patientInScopeNullRt->isAccessibleBy($this->kaderUser));

        Sanctum::actingAs($this->kaderUser, ['*']);
        $response = $this->getJson('/api/patients/' . $this->patientInScopeNullRt->id . '/show');
        $response->assertStatus(200);

        $indexResponse = $this->getJson('/api/patients');
        $indexResponse->assertStatus(200);
        $patientIds = collect($indexResponse->json('data'))->pluck('id')->all();
        $this->assertContains($this->patientInScopeNullRt->id, $patientIds);
    }
}
