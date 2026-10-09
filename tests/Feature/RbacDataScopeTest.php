<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\KaderArea;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\CloseContact;
use App\Models\ClinicalExamination;
use App\Models\MedicationRecord;
use App\Models\TreatmentVisit;
use App\Models\Puskesmas;
use App\Models\Province;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use App\Models\TreatmentType;
use App\Models\Screening;
use App\Models\Consultation;
use App\Models\ConsultationReply;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RbacDataScopeTest extends TestCase
{
    use DatabaseTransactions;

    protected $provinceA;
    protected $provinceB;
    protected $districtA;
    protected $districtB;
    protected $subdistrictA;
    protected $subdistrictB;
    protected $villageA1;
    protected $villageA2;
    protected $villageB;
    protected $puskesmasA;
    protected $puskesmasB;

    protected $adminUser;
    protected $dinkesProvUser;
    protected $dinkesKabUser;
    protected $pjtbUser;
    protected $kaderUser;
    protected $emptyKaderUser;
    protected $patientUserA;
    protected $patientUserB;

    protected $patientA1; // In Puskesmas A, Village A1, RW 01, RT 01
    protected $patientA2; // In Puskesmas A, Village A1, RW 02, RT 01 (Diff RW)
    protected $patientB;  // In Puskesmas B, District B, Province B

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Territories & Puskesmas
        $this->provinceA = Province::first() ?? Province::create(['name' => 'Provinsi Alpha']);
        $this->provinceB = Province::where('id', '!=', $this->provinceA->id)->first()
            ?? Province::create(['name' => 'Provinsi Beta']);

        $this->districtA = District::where('province_id', $this->provinceA->id)->first()
            ?? District::create(['name' => 'Kota Alpha', 'province_id' => $this->provinceA->id]);
        $this->districtB = District::where('province_id', $this->provinceB->id)->first()
            ?? District::create(['name' => 'Kabupaten Beta', 'province_id' => $this->provinceB->id]);

        $this->subdistrictA = Subdistrict::where('district_id', $this->districtA->id)->first()
            ?? Subdistrict::create(['name' => 'Kecamatan Alpha', 'district_id' => $this->districtA->id]);
        $this->subdistrictB = Subdistrict::where('district_id', $this->districtB->id)->first()
            ?? Subdistrict::create(['name' => 'Kecamatan Beta', 'district_id' => $this->districtB->id]);

        $this->villageA1 = Village::where('subdistrict_id', $this->subdistrictA->id)->first()
            ?? Village::create(['name' => 'Kelurahan Alpha 1', 'subdistrict_id' => $this->subdistrictA->id]);
        $this->villageA2 = Village::where('subdistrict_id', $this->subdistrictA->id)->where('id', '!=', $this->villageA1->id)->first()
            ?? Village::create(['name' => 'Kelurahan Alpha 2', 'subdistrict_id' => $this->subdistrictA->id]);
        $this->villageB = Village::where('subdistrict_id', $this->subdistrictB->id)->first()
            ?? Village::create(['name' => 'Kelurahan Beta', 'subdistrict_id' => $this->subdistrictB->id]);

        $this->puskesmasA = Puskesmas::where('subdistrict_id', $this->subdistrictA->id)->first()
            ?? Puskesmas::create(['name' => 'Puskesmas Alpha', 'subdistrict_id' => $this->subdistrictA->id]);
        $this->puskesmasB = Puskesmas::where('subdistrict_id', $this->subdistrictB->id)->first()
            ?? Puskesmas::create(['name' => 'Puskesmas Beta', 'subdistrict_id' => $this->subdistrictB->id]);

        // 2. Roles & Users Setup
        // Admin (Role 1)
        $this->adminUser = User::create([
            'name'         => 'Admin Master Test',
            'username'     => 'admin_rbac_' . uniqid(),
            'email'        => 'admin_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 1,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);

        // Dinkes Provinsi (Role 3, Subtype 1)
        $this->dinkesProvUser = User::create([
            'name'         => 'Dinkes Provinsi Test',
            'username'     => 'prov_' . uniqid(),
            'email'        => 'prov_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);
        Officer::create([
            'user_id'         => $this->dinkesProvUser->id,
            'officer_type_id' => 1,
            'district_id'     => $this->districtA->id, // Derived province = Province A
        ]);

        // Dinkes Kab/Kota (Role 3, Subtype 2)
        $this->dinkesKabUser = User::create([
            'name'         => 'Dinkes Kab Test',
            'username'     => 'kab_' . uniqid(),
            'email'        => 'kab_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);
        Officer::create([
            'user_id'         => $this->dinkesKabUser->id,
            'officer_type_id' => 2,
            'district_id'     => $this->districtA->id,
        ]);

        // PJTB Puskesmas (Role 3, Subtype 3)
        $this->pjtbUser = User::create([
            'name'         => 'PJTB Puskesmas Test',
            'username'     => 'pjtb_' . uniqid(),
            'email'        => 'pjtb_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);
        Officer::create([
            'user_id'         => $this->pjtbUser->id,
            'officer_type_id' => 3,
            'puskesmas_id'    => $this->puskesmasA->id,
        ]);

        // Kader Puskesmas (Role 3, Subtype 4) assigned to Village A1, RW 01, RT 01
        $this->kaderUser = User::create([
            'name'         => 'Kader Test',
            'username'     => 'kader_' . uniqid(),
            'email'        => 'kader_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'P',
        ]);
        $kaderOfficer = Officer::create([
            'user_id'         => $this->kaderUser->id,
            'officer_type_id' => 4,
            'puskesmas_id'    => $this->puskesmasA->id,
        ]);
        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $this->subdistrictA->id,
            'village_id'     => $this->villageA1->id,
            'rw'             => '01',
            'rt'             => '01',
        ]);

        // Kader with NO assigned area
        $this->emptyKaderUser = User::create([
            'name'         => 'Empty Kader Test',
            'username'     => 'empty_kader_' . uniqid(),
            'email'        => 'empty_kader_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'P',
        ]);
        Officer::create([
            'user_id'         => $this->emptyKaderUser->id,
            'officer_type_id' => 4,
            'puskesmas_id'    => $this->puskesmasA->id,
        ]);

        // Patient User A (Role 2)
        $this->patientUserA = User::create([
            'name'         => 'Pasien A',
            'username'     => 'pasiena_' . uniqid(),
            'email'        => 'pasiena_' . uniqid() . '@test.com',
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);
        $this->patientA1 = Patient::create([
            'user_id'              => $this->patientUserA->id,
            'nik'                  => '3271' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $this->puskesmasA->id,
            'subdistrict_id'       => $this->subdistrictA->id,
            'village_id'           => $this->villageA1->id,
            'rw'                   => '01',
            'rt'                   => '01',
            'address'              => 'Jl. Sukajadi No. 1',
            'treatment_start_date' => now()->toDateString(),
        ]);

        // Patient A2 (Same Puskesmas A, but RW 02 - outside Kader's specific RW)
        $userA2 = User::create([
            'name'         => 'Pasien A2',
            'username'     => 'pasiena2_' . uniqid(),
            'email'        => 'pasiena2_' . uniqid() . '@test.com',
            'phone'        => '0813' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
            'gender'       => 'P',
        ]);
        $this->patientA2 = Patient::create([
            'user_id'              => $userA2->id,
            'nik'                  => '3272' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $this->puskesmasA->id,
            'subdistrict_id'       => $this->subdistrictA->id,
            'village_id'           => $this->villageA1->id,
            'rw'                   => '02',
            'rt'                   => '01',
            'address'              => 'Jl. Sukajadi No. 2',
            'treatment_start_date' => now()->toDateString(),
        ]);

        // Patient B (Puskesmas B, District B, Province B)
        $this->patientUserB = User::create([
            'name'         => 'Pasien B',
            'username'     => 'pasienb_' . uniqid(),
            'email'        => 'pasienb_' . uniqid() . '@test.com',
            'phone'        => '0814' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);
        $this->patientB = Patient::create([
            'user_id'              => $this->patientUserB->id,
            'nik'                  => '3273' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $this->puskesmasB->id,
            'subdistrict_id'       => $this->subdistrictB->id,
            'village_id'           => $this->villageB->id,
            'rw'                   => '01',
            'rt'                   => '01',
            'address'              => 'Jl. Beta Raya',
            'treatment_start_date' => now()->toDateString(),
        ]);
    }

    /**
     * Test 1: Administrator (user_type_id = 1) has global access.
     */
    public function test_admin_has_unrestricted_global_access()
    {
        // 1. Eloquent scope returns all patients
        $this->assertEquals(Patient::count(), Patient::accessibleBy($this->adminUser)->count());
        $this->assertTrue($this->patientA1->isAccessibleBy($this->adminUser));
        $this->assertTrue($this->patientB->isAccessibleBy($this->adminUser));

        // 2. Web Admin Index and Show
        $response = $this->actingAs($this->adminUser)->get(route('admin.patients.index'));
        $response->assertStatus(200);

        $responseShow = $this->actingAs($this->adminUser)->get(route('admin.patients.show', $this->patientB->encrypted_id));
        $responseShow->assertStatus(200);

        // 3. API Index & Show
        Sanctum::actingAs($this->adminUser);
        $apiResponse = $this->getJson('/api/patients');
        $apiResponse->assertStatus(200);

        $apiShow = $this->getJson("/api/patients/{$this->patientB->id}");
        $apiShow->assertStatus(200);
    }

    /**
     * Test 2: Pasien (user_type_id = 2) is strictly confined to own data.
     */
    public function test_patient_can_only_access_own_data_and_gets_403_on_others()
    {
        // 1. Eloquent Scope
        $scoped = Patient::accessibleBy($this->patientUserA)->get();
        $this->assertCount(1, $scoped);
        $this->assertEquals($this->patientA1->id, $scoped->first()->id);

        $this->assertTrue($this->patientA1->isAccessibleBy($this->patientUserA));
        $this->assertFalse($this->patientB->isAccessibleBy($this->patientUserA));

        // 2. API Own Access -> 200 OK
        Sanctum::actingAs($this->patientUserA);
        $ownResponse = $this->getJson("/api/patients/{$this->patientA1->id}");
        $ownResponse->assertStatus(200);

        // 3. API IDOR to other patient -> 403 Forbidden
        $otherResponse = $this->getJson("/api/patients/{$this->patientB->id}");
        $otherResponse->assertStatus(403);

        // 4. API IDOR to treatment history of other patient -> 403 Forbidden
        $historyResponse = $this->getJson("/api/patients/{$this->patientB->id}/treatments");
        $historyResponse->assertStatus(403);
    }

    /**
     * Test 3: Dinkes Provinsi (user_type_id = 3, subtype 1) scopes by province.
     */
    public function test_dinkes_provinsi_can_access_patients_within_province_only()
    {
        // Patient A1 is in Province A, Patient B is in Province B
        $this->assertTrue($this->patientA1->isAccessibleBy($this->dinkesProvUser));
        $this->assertFalse($this->patientB->isAccessibleBy($this->dinkesProvUser));

        // Web Admin Detail
        $resIn = $this->actingAs($this->dinkesProvUser)->get(route('admin.patients.show', $this->patientA1->encrypted_id));
        $resIn->assertStatus(200);

        $resOut = $this->actingAs($this->dinkesProvUser)->get(route('admin.patients.show', $this->patientB->encrypted_id));
        $resOut->assertStatus(403);

        // API Detail
        Sanctum::actingAs($this->dinkesProvUser);
        $apiIn = $this->getJson("/api/patients/{$this->patientA1->id}");
        $apiIn->assertStatus(200);

        $apiOut = $this->getJson("/api/patients/{$this->patientB->id}");
        $apiOut->assertStatus(403);
    }

    /**
     * Test 4: Dinkes Kab/Kota (user_type_id = 3, subtype 2) scopes by district.
     */
    public function test_dinkes_kab_kota_can_access_patients_within_district_only()
    {
        // Patient A1 & A2 are in District A, Patient B is in District B
        $this->assertTrue($this->patientA1->isAccessibleBy($this->dinkesKabUser));
        $this->assertTrue($this->patientA2->isAccessibleBy($this->dinkesKabUser));
        $this->assertFalse($this->patientB->isAccessibleBy($this->dinkesKabUser));

        // Web Admin
        $resIn = $this->actingAs($this->dinkesKabUser)->get(route('admin.patients.show', $this->patientA1->encrypted_id));
        $resIn->assertStatus(200);

        $resOut = $this->actingAs($this->dinkesKabUser)->get(route('admin.patients.show', $this->patientB->encrypted_id));
        $resOut->assertStatus(403);

        // API
        Sanctum::actingAs($this->dinkesKabUser);
        $apiIn = $this->getJson("/api/patients/{$this->patientA1->id}");
        $apiIn->assertStatus(200);

        $apiOut = $this->getJson("/api/patients/{$this->patientB->id}");
        $apiOut->assertStatus(403);
    }

    /**
     * Test 5: PJTB Puskesmas (user_type_id = 3, subtype 3) scopes by puskesmas_id.
     */
    public function test_pjtb_puskesmas_can_access_patients_in_own_puskesmas_only()
    {
        // Patient A1 & A2 are in Puskesmas A
        $this->assertTrue($this->patientA1->isAccessibleBy($this->pjtbUser));
        $this->assertTrue($this->patientA2->isAccessibleBy($this->pjtbUser));

        // Patient B is in Puskesmas B -> Forbidden
        $this->assertFalse($this->patientB->isAccessibleBy($this->pjtbUser));

        // Web Admin
        $resIn = $this->actingAs($this->pjtbUser)->get(route('admin.patients.show', $this->patientA1->encrypted_id));
        $resIn->assertStatus(200);

        $resOut = $this->actingAs($this->pjtbUser)->get(route('admin.patients.show', $this->patientB->encrypted_id));
        $resOut->assertStatus(403);

        // API
        Sanctum::actingAs($this->pjtbUser);
        $apiIn = $this->getJson("/api/patients/{$this->patientA1->id}");
        $apiIn->assertStatus(200);

        $apiOut = $this->getJson("/api/patients/{$this->patientB->id}");
        $apiOut->assertStatus(403);
    }

    /**
     * Test 6: Kader Puskesmas (user_type_id = 3, subtype 4) strictly scopes to assigned kader_areas.
     */
    public function test_kader_puskesmas_strictly_scoped_to_assigned_areas()
    {
        // Assigned area: Puskesmas A, Village A1, RW 01, RT 01
        // Patient A1 matches exactly -> Accessible
        $this->assertTrue($this->patientA1->isAccessibleBy($this->kaderUser));

        // Patient A2 has RW 02 (outside assigned RW 01) -> Inaccessible
        $this->assertFalse($this->patientA2->isAccessibleBy($this->kaderUser));

        // Patient B is in Puskesmas B -> Inaccessible
        $this->assertFalse($this->patientB->isAccessibleBy($this->kaderUser));

        // Kader with NO area assigned -> sees 0 patients
        $this->assertEquals(0, Patient::accessibleBy($this->emptyKaderUser)->count());
        $this->assertFalse($this->patientA1->isAccessibleBy($this->emptyKaderUser));

        // API checks
        Sanctum::actingAs($this->kaderUser);
        $apiIn = $this->getJson("/api/patients/{$this->patientA1->id}");
        $apiIn->assertStatus(200);

        $apiDiffRw = $this->getJson("/api/patients/{$this->patientA2->id}");
        $apiDiffRw->assertStatus(403);

        $apiDiffPusk = $this->getJson("/api/patients/{$this->patientB->id}");
        $apiDiffPusk->assertStatus(403);
    }

    /**
     * Test 7: IDOR Protection on Mutations (Update & Delete) and Child Entities.
     */
    public function test_mutation_idor_and_child_entities_protected()
    {
        // 1. Web Admin: PJTB attempts to delete Patient B (from Puskesmas B) -> 403
        $delResponse = $this->actingAs($this->pjtbUser)->delete(route('admin.patients.destroy', $this->patientB->encrypted_id));
        $delResponse->assertStatus(403);

        // 2. Web Admin: PJTB attempts to edit Patient B -> 403
        $editResponse = $this->actingAs($this->pjtbUser)->get(route('admin.patients.edit', $this->patientB->encrypted_id));
        $editResponse->assertStatus(403);

        // 3. API: PJTB attempts to delete Patient B -> 403
        Sanctum::actingAs($this->pjtbUser);
        $apiDel = $this->deleteJson("/api/patients/{$this->patientB->id}");
        $apiDel->assertStatus(403);

        // 4. Child Entity: Close Contact of Patient B
        $contactB = CloseContact::create([
            'contact_code'     => 'KONT-' . uniqid(),
            'patient_id'       => $this->patientB->id,
            'name'             => 'Keluarga B',
            'relationship'     => 'Keluarga Serumah',
            'gender'           => 'P',
            'age'              => 30,
            'screening_result' => 'Negatif',
            'tpt_status'       => 'Tidak Perlu',
        ]);

        $this->assertFalse($contactB->isAccessibleBy($this->pjtbUser));
        $this->assertTrue($contactB->isAccessibleBy($this->patientUserB));

        // PJTB attempting to delete Contact of Patient B via Web Admin -> 403
        $delContact = $this->actingAs($this->pjtbUser)->delete(route('admin.contacts.destroy', $contactB->encrypted_id));
        $delContact->assertStatus(403);

        // 5. Child Entity: PatientTreatment of Patient B
        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1 Dewasa',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);
        $treatmentB = PatientTreatment::create([
            'patient_id'        => $this->patientB->id,
            'treatment_type_id' => $treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'diagnosis_date'    => now()->toDateString(),
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addMonths(6)->toDateString(),
            'medication_time'   => '08:00',
        ]);

        $this->assertFalse($treatmentB->isAccessibleBy($this->pjtbUser));

        // PJTB updating status of treatment B via API -> 403
        $updateTr = $this->postJson('/api/treatments/status', [
            'id'               => $treatmentB->id,
            'treatment_status' => 'Selesai',
        ]);
        $updateTr->assertStatus(403);

        // 6. Child Entity: Medication Record of Treatment B
        $recordB = MedicationRecord::create([
            'patient_treatment_id' => $treatmentB->id,
            'is_verified'          => 0,
            'late'                 => 0,
        ]);

        $this->assertFalse($recordB->isAccessibleBy($this->pjtbUser));

        // PJTB verifying medication proof for patient B via API -> 403
        $verifyRes = $this->putJson('/api/treatments/verify', [
            'id' => $recordB->id,
        ]);
        $verifyRes->assertStatus(403);
    }

    /**
     * Test 8: Security Audit Vulnerability Fixes Regression Test.
     * Verifies remediation of Critical and High audit findings:
     * - Mass password reset escalation blocked (403 for non-admin, 401 unauth)
     * - Unauthenticated & unauthorized access to screening detail blocked (401/403)
     * - Legacy web routes inaccessible to patients (redirect 302)
     * - IDOR on treatment visit store and destroy blocked (403)
     * - IDOR on consultation and reply delete blocked (403)
     * - User report module restricted to Administrator (403 for officers)
     */
    public function test_audit_vulnerability_fixes_regression()
    {
        // 1. Password Batch Reset privilege escalation prevention
        // Guest -> 401
        $this->postJson('/api/password/batch-reset')->assertStatus(401);

        // Patient -> 403
        Sanctum::actingAs($this->patientUserA);
        $this->postJson('/api/password/batch-reset')->assertStatus(403);

        // Kader -> 403
        Sanctum::actingAs($this->kaderUser);
        $this->postJson('/api/password/batch-reset')->assertStatus(403);

        // 2. Screening detail data leakage protection
        $screeningB = Screening::create([
            'patient_id'       => $this->patientB->id,
            'user_id'          => $this->patientUserB->id,
            'puskesmas_id'     => $this->puskesmasB->id,
            'district_id'      => $this->districtB->id,
            'subdistrict_id'   => $this->subdistrictB->id,
            'village_id'       => $this->villageB->id,
            'total_score'      => 12,
            'risk_level'       => 'Risiko Tinggi',
            'status'           => 'Perlu Tindak Lanjut',
        ]);

        // Unauthenticated -> 401
        $this->app['auth']->forgetGuards();
        $this->getJson("/api/screening/{$screeningB->id}")->assertStatus(401);

        // Patient A trying to view Screening of Patient B -> 403
        Sanctum::actingAs($this->patientUserA);
        $this->getJson("/api/screening/{$screeningB->id}")->assertStatus(403);

        // PJTB User of PKM A trying to view Screening of Patient B (PKM B) -> 403
        Sanctum::actingAs($this->pjtbUser);
        $this->getJson("/api/screening/{$screeningB->id}")->assertStatus(403);

        // Admin -> 200
        Sanctum::actingAs($this->adminUser);
        $this->getJson("/api/screening/{$screeningB->id}")->assertStatus(200);

        // 3. Legacy Web Routes Isolation for Patients
        // Patient attempting to access legacy /patients -> redirect 302
        $resPatients = $this->actingAs($this->patientUserA)->get(route('patients'));
        $resPatients->assertStatus(302);

        // Patient attempting to access legacy /pkm -> redirect 302
        $resPkm = $this->actingAs($this->patientUserA)->get(route('pkm'));
        $resPkm->assertStatus(302);

        // Patient attempting to access legacy /user/1/list -> redirect 302
        $resUserList = $this->actingAs($this->patientUserA)->get(route('user.list', ['id' => base64_encode(2)]));
        $resUserList->assertStatus(302);

        // 4. IDOR on Treatment Visits
        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1 Dewasa',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);
        $treatmentB = PatientTreatment::create([
            'patient_id'        => $this->patientB->id,
            'treatment_type_id' => $treatmentType->id,
            'treatment_status'  => 'Berjalan',
            'diagnosis_date'    => now()->toDateString(),
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addMonths(6)->toDateString(),
            'medication_time'   => '08:00',
        ]);

        $visitB = TreatmentVisit::create([
            'patient_treatment_id' => $treatmentB->id,
            'visit_date'           => now()->addDays(2)->toDateString(),
            'visit_time'           => '09:00',
            'visit_status'         => 'Terjadwal',
            'notes'                => 'Kontrol rutin Pasien B',
        ]);

        // PJTB User (PKM A) attempting to overwrite Visit B (PKM B) via API -> 403
        Sanctum::actingAs($this->pjtbUser);
        $resVisitUpdate = $this->postJson('/api/visits/store', [
            'id'                   => $visitB->id,
            'patient_treatment_id' => $treatmentB->id,
            'visit_date'           => now()->addDays(5)->toDateString(),
            'visit_status'         => 'Hadir',
        ]);
        $resVisitUpdate->assertStatus(403);

        // PJTB User (PKM A) attempting to delete Visit B -> 403
        $resVisitDel = $this->deleteJson("/api/visits/{$visitB->id}/delete");
        $resVisitDel->assertStatus(403);

        // 5. IDOR on Consultations and Replies
        $consultationB = Consultation::create([
            'user_id'     => $this->patientUserB->id,
            'title'       => 'Konsultasi Pribadi B',
            'message'     => 'Pertanyaan keluhan dari Pasien B',
            'is_answered' => false,
        ]);

        $replyB = ConsultationReply::create([
            'consultation_id' => $consultationB->id,
            'user_id'         => $this->patientUserB->id,
            'message'         => 'Balasan tambahan dari Pasien B',
        ]);

        // Patient A attempting to delete Consultation B -> 403
        Sanctum::actingAs($this->patientUserA);
        $this->deleteJson("/api/consultations/{$consultationB->id}/delete")->assertStatus(403);

        // Patient A attempting to delete Reply B -> 403
        $this->deleteJson("/api/consultations/{$replyB->id}/reply")->assertStatus(403);

        // 6. Report User Scoping (Admin only)
        // PJTB attempting to view User Report -> 403
        $resReportUser = $this->actingAs($this->pjtbUser)->get('/admin/reports?type=user');
        $resReportUser->assertStatus(403);

        // PJTB attempting to print User Report -> 403
        $resPrintUser = $this->actingAs($this->pjtbUser)->get('/admin/reports/print?type=user');
        $resPrintUser->assertStatus(403);

        // PJTB attempting to export CSV User Report -> 403
        $resExportUser = $this->actingAs($this->pjtbUser)->get(route('admin.reports.export', ['type' => 'user']));
        $resExportUser->assertStatus(403);

        // Admin attempting to view User Report -> 200
        $resAdminReport = $this->actingAs($this->adminUser)->get('/admin/reports?type=user');
        $resAdminReport->assertStatus(200);
    }
}
