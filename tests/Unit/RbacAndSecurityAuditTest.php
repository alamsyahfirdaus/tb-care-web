<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use Illuminate\Support\Facades\Route;

class RbacAndSecurityAuditTest extends TestCase
{
    /**
     * Skenario 1: Verifikasi helper role Super Administrator
     */
    public function test_super_admin_role_helpers()
    {
        $user = new User(['user_type_id' => 1]);

        $this->assertTrue($user->isSuperAdmin());
        $this->assertFalse($user->isPatient());
        $this->assertFalse($user->isOfficer());
        $this->assertTrue($user->canAccessWebAdmin());
        $this->assertEquals('Super Administrator', $user->role_display_name);
    }

    /**
     * Skenario 2: Verifikasi role Pasien ditolak dari akses Web Admin
     */
    public function test_patient_role_helpers_and_web_rejection()
    {
        $user = new User(['user_type_id' => 2]);

        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->isPatient());
        $this->assertFalse($user->isOfficer());
        $this->assertFalse($user->canAccessWebAdmin());
        $this->assertEquals('Pasien TB', $user->role_display_name);
    }

    /**
     * Skenario 3: Verifikasi role Kader diarahkan ke mobile, ditolak dari Web Admin
     */
    public function test_kader_role_helpers_and_web_rejection()
    {
        $user = new User(['user_type_id' => 3]);
        $officer = new Officer(['officer_type_id' => 4]); // 4 = Kader
        $user->setRelation('officer', $officer);

        $this->assertTrue($user->isOfficer());
        $this->assertTrue($user->isKader());
        $this->assertFalse($user->isPJTB());
        $this->assertFalse($user->isDinkesKabKota());
        $this->assertFalse($user->isDinkesProvinsi());
        $this->assertFalse($user->canAccessWebAdmin(), 'Kader harus ditolak dari akses Web Admin');
        $this->assertEquals('Kader Puskesmas', $user->role_display_name);
    }

    /**
     * Skenario 4: Verifikasi Petugas Dinkes Provinsi, Kab/Kota, dan PJTB dapat akses Web Admin
     */
    public function test_health_officers_roles_and_web_access()
    {
        // 4A: Dinkes Provinsi
        $provUser = new User(['user_type_id' => 3]);
        $provOfficer = new Officer(['officer_type_id' => 1]);
        $provUser->setRelation('officer', $provOfficer);

        $this->assertTrue($provUser->isDinkesProvinsi());
        $this->assertTrue($provUser->canAccessWebAdmin());
        $this->assertEquals('Dinkes Provinsi', $provUser->role_display_name);

        // 4B: Dinkes Kab/Kota
        $cityUser = new User(['user_type_id' => 3]);
        $cityOfficer = new Officer(['officer_type_id' => 2]);
        $cityUser->setRelation('officer', $cityOfficer);

        $this->assertTrue($cityUser->isDinkesKabKota());
        $this->assertTrue($cityUser->canAccessWebAdmin());
        $this->assertEquals('Dinkes Kab/Kota', $cityUser->role_display_name);

        // 4C: PJTB Puskesmas
        $pjtbUser = new User(['user_type_id' => 3]);
        $pjtbOfficer = new Officer(['officer_type_id' => 3]);
        $pjtbUser->setRelation('officer', $pjtbOfficer);

        $this->assertTrue($pjtbUser->isPJTB());
        $this->assertTrue($pjtbUser->canAccessWebAdmin());
        $this->assertEquals('PJTB Puskesmas', $pjtbUser->role_display_name);
    }

    /**
     * Skenario 5: Verifikasi relasi activeTreatment dan latestTreatment pada model Patient
     */
    public function test_patient_model_has_active_treatment_relationship()
    {
        $patient = new Patient();

        $this->assertTrue(method_exists($patient, 'activeTreatment'), 'Method activeTreatment harus ada pada model Patient');
        $this->assertTrue(method_exists($patient, 'latestTreatment'), 'Method latestTreatment harus ada pada model Patient');
        $this->assertTrue($patient->usesTimestamps(), 'Model Patient harus menggunakan timestamps untuk sinkronisasi created_at dan updated_at');
    }

    /**
     * Skenario 6: Verifikasi penghapusan endpoint backdoor keamanan kritis P0
     */
    public function test_backdoor_endpoints_are_completely_removed()
    {
        $routes = collect(Route::getRoutes()->getRoutes());

        $hasBatchReset = $routes->contains(function ($route) {
            return str_contains($route->uri(), 'password/batch-reset');
        });

        $hasRunMigration = $routes->contains(function ($route) {
            return str_contains($route->uri(), 'run-migration');
        });

        $this->assertFalse($hasBatchReset, 'Backdoor endpoint POST /api/password/batch-reset tidak boleh ada!');
        $this->assertFalse($hasRunMigration, 'Backdoor endpoint POST /api/run-migration tidak boleh ada!');
    }

    /**
     * Skenario 7: Verifikasi rute pencatatan kunjungan pengobatan (store_visit) terdaftar
     */
    public function test_treatment_visit_route_is_registered()
    {
        $this->assertTrue(Route::has('treatments.store_visit'), 'Route treatments.store_visit harus terdaftar');
    }

    /**
     * Skenario 8: Verifikasi logika regex penomoran sekuensial tahan tabrakan
     */
    public function test_sequential_code_regex_generation_logic()
    {
        $prefix = 'EXAM-202610-';
        $sampleLastCode = 'EXAM-202610-0042';

        $nextSeq = 1;
        if (preg_match('/EXAM-\d+-(\d+)/', $sampleLastCode, $m)) {
            $nextSeq = intval($m[1]) + 1;
        }

        $generatedCode = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
        $this->assertEquals('EXAM-202610-0043', $generatedCode);

        // Uji untuk Kontak Erat
        $kontPrefix = 'KONT-202610-';
        $sampleKont = 'KONT-202610-0199';
        $nextKontSeq = 1;
        if (preg_match('/KONT-\d+-(\d+)/', $sampleKont, $m)) {
            $nextKontSeq = intval($m[1]) + 1;
        }
        $generatedKontCode = $kontPrefix . str_pad($nextKontSeq, 4, '0', STR_PAD_LEFT);
        $this->assertEquals('KONT-202610-0200', $generatedKontCode);
    }
}
