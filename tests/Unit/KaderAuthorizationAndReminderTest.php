<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\CloseContactController;
use App\Http\Controllers\Api\KaderController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\TreatmentVisitController;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\TreatmentVisit;
use App\Models\User;
use App\Policies\PatientPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class KaderAuthorizationAndReminderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function makeMockKaderUser(): User
    {
        $user = new User(['id' => 88, 'user_type_id' => 3, 'name' => 'Kader Siti']);
        $officer = Mockery::mock(Officer::class)->makePartial();
        $officer->officer_type_id = 4; // Kader
        $officer->puskesmas_id = 1;
        $user->setRelation('officer', $officer);
        return $user;
    }

    protected function makeMockPetugasUser(): User
    {
        $user = new User(['id' => 99, 'user_type_id' => 3, 'name' => 'Petugas Budi']);
        $officer = Mockery::mock(Officer::class)->makePartial();
        $officer->officer_type_id = 3; // PJTB
        $officer->puskesmas_id = 1;
        $user->setRelation('officer', $officer);
        return $user;
    }

    /**
     * Test: PatientPolicy explicitly forbids Kader from create, update, and delete.
     */
    public function test_patient_policy_forbids_kader_mutations()
    {
        $policy = new PatientPolicy();
        $kaderUser = $this->makeMockKaderUser();
        $petugasUser = $this->makeMockPetugasUser();

        $patient = Mockery::mock(Patient::class)->makePartial();
        $patient->shouldReceive('isAccessibleBy')->andReturn(true);

        // Kader cannot create, update, or delete
        $this->assertFalse($policy->create($kaderUser));
        $this->assertFalse($policy->update($kaderUser, $patient));
        $this->assertFalse($policy->delete($kaderUser, $patient));

        // Petugas can create, update, delete
        $this->assertTrue($policy->create($petugasUser));
        $this->assertTrue($policy->update($petugasUser, $patient));
        $this->assertTrue($policy->delete($petugasUser, $patient));
    }

    /**
     * Test: PatientController store, destroy, and schedule return 403 for Kader.
     */
    public function test_patient_controller_rejects_kader_with_403()
    {
        $kaderUser = $this->makeMockKaderUser();
        Auth::setUser($kaderUser);

        $controller = new PatientController();

        // 1. Store
        $reqStore = Request::create('/api/patients/store', 'POST', ['name' => 'New Patient']);
        $resStore = $controller->store($reqStore);
        $this->assertEquals(403, $resStore->getStatusCode());
        $this->assertStringContainsString('Kader tidak memiliki wewenang', $resStore->getContent());

        // 2. Destroy
        $resDestroy = $controller->destroy(123);
        $this->assertEquals(403, $resDestroy->getStatusCode());
        $this->assertStringContainsString('Kader tidak memiliki wewenang', $resDestroy->getContent());

        // 3. Medication Schedule
        $reqSchedule = Request::create('/api/patients/123/medication-schedule', 'POST', ['reminder_time' => '07:00']);
        $resSchedule = $controller->saveMedicationSchedule($reqSchedule, 123);
        $this->assertEquals(403, $resSchedule->getStatusCode());
        $this->assertStringContainsString('Kader tidak memiliki wewenang', $resSchedule->getContent());
    }

    /**
     * Test: TreatmentController store, destroy, and status return 403 for Kader.
     */
    public function test_treatment_controller_rejects_kader_with_403()
    {
        $kaderUser = $this->makeMockKaderUser();
        Auth::setUser($kaderUser);

        $controller = new TreatmentController();

        // 1. Store
        $reqStore = Request::create('/api/treatments/store', 'POST', [
            'patient_id'        => 1,
            'treatment_type_id' => 1,
            'medication_time'   => '07:00',
            'treatment_status'  => 'Berjalan',
        ]);
        $resStore = $controller->store($reqStore);
        $this->assertEquals(403, $resStore->getStatusCode());
        $this->assertStringContainsString('tidak memiliki wewenang', $resStore->getContent());

        // 2. Destroy
        $resDestroy = $controller->destroy(1);
        $this->assertEquals(403, $resDestroy->getStatusCode());
        $this->assertStringContainsString('tidak memiliki wewenang', $resDestroy->getContent());

        // 3. Update Treatment Status
        $reqStatus = Request::create('/api/treatments/status', 'POST', [
            'id'               => 1,
            'treatment_status' => 'Sembuh',
        ]);

        $resStatus = $controller->updateTreatmentStatus($reqStatus);
        $this->assertEquals(403, $resStatus->getStatusCode());
        $this->assertStringContainsString('tidak memiliki wewenang', $resStatus->getContent());
    }

    /**
     * Test: TreatmentVisitController store, destroy, and cancel return 403 for Kader.
     */
    public function test_treatment_visit_controller_rejects_kader_with_403()
    {
        $kaderUser = $this->makeMockKaderUser();
        Auth::setUser($kaderUser);

        $controller = new TreatmentVisitController();

        // 1. Store
        $reqStore = Request::create('/api/visits/store', 'POST', [
            'patient_treatment_id' => 1,
            'visit_date'           => date('Y-m-d', strtotime('+1 day')),
            'visit_time'           => '09:00',
        ]);
        $resStore = $controller->store($reqStore);
        $this->assertEquals(403, $resStore->getStatusCode());
        $this->assertStringContainsString('tidak memiliki wewenang', $resStore->getContent());

        // 2. Destroy
        $resDestroy = $controller->destroy(1);
        $this->assertEquals(403, $resDestroy->getStatusCode());
        $this->assertStringContainsString('tidak memiliki wewenang', $resDestroy->getContent());

        // 3. Cancel Visit
        $reqCancel = Request::create('/api/visits/1/cancel', 'POST', ['notes' => 'Pasien berhalangan']);
        $resCancel = $controller->cancelVisit($reqCancel, 1);
        $this->assertEquals(403, $resCancel->getStatusCode());
        $this->assertStringContainsString('tidak memiliki wewenang', $resCancel->getContent());
    }

    /**
     * Test: CloseContactController store, update, and destroy return 403 for Kader.
     */
    public function test_close_contact_controller_rejects_kader_with_403()
    {
        $kaderUser = $this->makeMockKaderUser();
        Auth::setUser($kaderUser);

        $controller = new CloseContactController();

        $req = Request::create('/api/contacts', 'POST', ['name' => 'Kontak']);
        $resStore = $controller->store($req);
        $this->assertEquals(403, $resStore->getStatusCode());

        $resUpdate = $controller->update($req, 1);
        $this->assertEquals(403, $resUpdate->getStatusCode());

        $resDestroy = $controller->destroy($req, 1);
        $this->assertEquals(403, $resDestroy->getStatusCode());
    }

    /**
     * Test: KaderController forbids Patient role from accessing Kader dashboard/reminders.
     */
    public function test_kader_controller_forbids_patient_role()
    {
        $patientUser = new User(['id' => 5, 'user_type_id' => 2, 'name' => 'Pasien Ahmad']);
        Auth::setUser($patientUser);

        $controller = new KaderController();
        $req = Request::create('/api/kader/dashboard', 'GET');
        $res = $controller->dashboard($req);

        $this->assertEquals(403, $res->getStatusCode());
        $this->assertStringContainsString('Halaman ini khusus untuk Kader', $res->getContent());
    }
}
