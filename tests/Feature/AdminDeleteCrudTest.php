<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Screening;
use App\Models\ScreeningAnswer;
use App\Models\ClinicalExamination;
use App\Models\PatientTreatment;
use App\Models\CloseContact;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Models\EducationalMaterial;
use App\Models\SystemNotification;
use App\Models\TreatmentType;
use App\Models\ActivityLog;
use App\Models\PatientMedicationSchedule;
use Tests\TestCase;

class AdminDeleteCrudTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::find(1);
    }

    /**
     * 1. Test Delete Patient (Focus #1)
     */
    public function test_delete_patient_cascades_all_relations_and_logs_activity()
    {
        $testUser = User::create([
            'name' => 'Pasien Uji Hapus ' . time(),
            'username' => 'testdel_pat_' . time(),
            'email' => 'testdel_pat_' . time() . '@test.com',
            'password' => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active' => true,
        ]);

        $pkm = Puskesmas::first();
        $sub = Subdistrict::first();
        $vil = Village::first();

        $patient = Patient::create([
            'user_id' => $testUser->id,
            'nik' => '999999' . rand(1000000000, 9999999999),
            'address' => 'Jl. Uji Hapus No. 1',
            'subdistrict_id' => $sub->id ?? null,
            'village_id' => $vil->id ?? null,
            'puskesmas_id' => $pkm->id ?? 1,
            'treatment_start_date' => now()->format('Y-m-d'),
        ]);

        $tt = TreatmentType::first();
        $treatment = PatientTreatment::create([
            'patient_id' => $patient->id,
            'treatment_type_id' => $tt ? $tt->id : null,
            'start_date' => now()->format('Y-m-d'),
            'treatment_status' => 'Berjalan',
        ]);

        $exam = ClinicalExamination::create([
            'examination_code' => 'EXAM-DEL-' . time() . '-' . rand(10, 99),
            'patient_id' => $patient->id,
            'puskesmas_id' => $pkm->id ?? 1,
            'examination_date' => now()->format('Y-m-d'),
            'examination_type' => 'TCM Sputum',
            'result' => 'Positif',
        ]);

        $contact = CloseContact::create([
            'contact_code' => 'CONT-DEL-' . time() . '-' . rand(10, 99),
            'patient_id' => $patient->id,
            'name' => 'Kontak Uji Delete',
            'relationship' => 'Keluarga',
            'nik' => '888888' . rand(1000000000, 9999999999),
            'gender' => 'P',
            'screening_result' => 'Belum',
            'tpt_status' => 'Belum',
        ]);

        $sched = PatientMedicationSchedule::create([
            'patient_id' => $patient->id,
            'reminder_time' => '08:00:00',
            'is_active' => true,
        ]);

        $scr = Screening::create([
            'code' => 'SCR-DEL-' . time() . '-' . rand(10, 99),
            'patient_id' => $patient->id,
            'user_id' => $testUser->id,
            'person_name' => $testUser->name,
            'puskesmas_id' => $pkm->id ?? 1,
            'total_score' => 5,
            'risk_level' => 'Risiko Tinggi',
            'status' => 'Selesai',
            'screened_at' => now(),
        ]);

        $patientId = $patient->id;
        $userId = $testUser->id;
        $treatmentId = $treatment->id;
        $examId = $exam->id;
        $contactId = $contact->id;
        $schedId = $sched->id;
        $scrId = $scr->id;

        // Perform DELETE request with encrypted ID
        $response = $this->actingAs($this->admin)->delete(route('admin.patients.destroy', $patient->encrypted_id));

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.patients.index'));
        $response->assertSessionHas('success');

        // Verify Database: Patient & User are gone
        $this->assertNull(Patient::find($patientId));
        $this->assertNull(User::find($userId));
        $this->assertNull(PatientTreatment::find($treatmentId));
        $this->assertNull(ClinicalExamination::find($examId));
        $this->assertNull(CloseContact::find($contactId));
        $this->assertNull(PatientMedicationSchedule::find($schedId));

        // Verify Screening unlinked
        $scrAfter = Screening::find($scrId);
        $this->assertNotNull($scrAfter);
        $this->assertNull($scrAfter->patient_id);
        $this->assertNull($scrAfter->user_id);
        $scrAfter->delete();

        // Verify ActivityLog
        $lastLog = ActivityLog::where('module', 'Pasien')->where('activity', 'Hapus Pasien')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 2. Test Delete User (Protect Admin 1 & Delete Regular)
     */
    public function test_delete_user_protects_admin_and_deletes_regular()
    {
        // A. Attempt to delete Admin ID 1
        $adminEnc = User::find(1)->encrypted_id;
        $responseAdmin = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $adminEnc));
        $responseAdmin->assertStatus(302);
        $responseAdmin->assertSessionHas('error');
        $this->assertNotNull(User::find(1));

        // B. Delete Regular Officer User
        $testUser = User::create([
            'name' => 'User Hapus Test ' . time(),
            'username' => 'delusr_' . time(),
            'email' => 'delusr_' . time() . '@test.com',
            'password' => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active' => true,
        ]);
        $testUserId = $testUser->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $testUser->encrypted_id));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $this->assertNull(User::find($testUserId));

        $lastLog = ActivityLog::where('module', 'Pengguna')->where('activity', 'Hapus Pengguna')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 3. Test Delete Screening
     */
    public function test_delete_screening_and_cascades_answers()
    {
        $pkm = Puskesmas::first();
        $scr = Screening::create([
            'code' => 'SCR-T-DEL-' . time(),
            'person_name' => 'Orang Uji Skrining Hapus',
            'puskesmas_id' => $pkm->id ?? 1,
            'total_score' => 2,
            'risk_level' => 'Risiko Rendah',
            'status' => 'Selesai',
            'screened_at' => now(),
        ]);

        $answer = ScreeningAnswer::create([
            'screening_id' => $scr->id,
            'screening_question_id' => 1,
            'answer' => 'Ya',
            'score' => 2,
        ]);

        $scrId = $scr->id;
        $ansId = $answer->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.screenings.destroy', $scr->encrypted_id));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.screenings.index'));
        $response->assertSessionHas('success');

        $this->assertNull(Screening::find($scrId));
        $this->assertNull(ScreeningAnswer::find($ansId));

        $lastLog = ActivityLog::where('module', 'Skrining TB')->where('activity', 'Hapus Skrining TB')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 4. Test Delete Examination
     */
    public function test_delete_clinical_examination()
    {
        $pkm = Puskesmas::first();
        $patient = Patient::first();

        $exam = ClinicalExamination::create([
            'examination_code' => 'EXAM-T-DEL-' . time(),
            'patient_id' => $patient->id ?? 1,
            'puskesmas_id' => $pkm->id ?? 1,
            'examination_date' => now()->format('Y-m-d'),
            'examination_type' => 'Foto Thoraks',
            'result' => 'Normal',
        ]);
        $examId = $exam->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.examinations.destroy', $exam->encrypted_id));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.examinations.index'));
        $response->assertSessionHas('success');

        $this->assertNull(ClinicalExamination::find($examId));

        $lastLog = ActivityLog::where('module', 'Pemeriksaan')->where('activity', 'Hapus Pemeriksaan TB')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 5. Test Delete Treatment
     */
    public function test_delete_treatment_and_cascades_medication_and_visits()
    {
        $patient = Patient::first();
        $tt = TreatmentType::first();

        $treatment = PatientTreatment::create([
            'patient_id' => $patient->id ?? 1,
            'treatment_type_id' => $tt ? $tt->id : null,
            'start_date' => now()->format('Y-m-d'),
            'treatment_status' => 'Berjalan',
        ]);
        $trId = $treatment->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.treatments.destroy', $treatment->encrypted_id));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.treatments.index'));
        $response->assertSessionHas('success');

        $this->assertNull(PatientTreatment::find($trId));

        $lastLog = ActivityLog::where('module', 'Pengobatan')->where('activity', 'Hapus Pengobatan')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 6. Test Delete Close Contact
     */
    public function test_delete_close_contact()
    {
        $patient = Patient::first();

        $contact = CloseContact::create([
            'contact_code' => 'CONT-DEL-' . time() . '-' . rand(100, 999),
            'patient_id' => $patient->id ?? 1,
            'name' => 'Kontak Uji Hapus ' . time(),
            'relationship' => 'Rekan Kerja',
            'nik' => '777777' . rand(1000000000, 9999999999),
            'gender' => 'L',
            'screening_result' => 'Belum',
            'tpt_status' => 'Belum',
        ]);
        $cId = $contact->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.contacts.destroy', $contact->encrypted_id));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.contacts.index'));
        $response->assertSessionHas('success');

        $this->assertNull(CloseContact::find($cId));

        $lastLog = ActivityLog::where('module', 'Kontak Erat')->where('activity', 'Hapus Kontak Erat')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 7. Test Delete Puskesmas (Dependency Protection & Standalone Delete)
     */
    public function test_delete_puskesmas_protects_dependencies_and_deletes_standalone()
    {
        // A. Puskesmas in use
        $inUsePkm = Puskesmas::whereHas('patients')->orWhereHas('screenings')->first();
        if ($inUsePkm) {
            $resp = $this->actingAs($this->admin)->delete(route('admin.puskesmas.destroy', $inUsePkm->encrypted_id));
            $resp->assertStatus(302);
            $resp->assertSessionHas('error');
            $this->assertNotNull(Puskesmas::find($inUsePkm->id));
        }

        // B. Standalone Puskesmas
        $sub = Subdistrict::first();
        $standalone = Puskesmas::create([
            'name' => 'Puskesmas Uji Hapus ' . time(),
            'code' => 'PKM-DEL-' . time(),
            'subdistrict_id' => $sub->id ?? null,
            'address' => 'Jl. Standalone',
        ]);
        $pkmId = $standalone->id;

        $resp2 = $this->actingAs($this->admin)->delete(route('admin.puskesmas.destroy', $standalone->encrypted_id));
        $resp2->assertStatus(302);
        $resp2->assertRedirect(route('admin.puskesmas.index'));
        $resp2->assertSessionHas('success');

        $this->assertNull(Puskesmas::find($pkmId));

        $lastLog = ActivityLog::where('module', 'Faskes')->where('activity', 'Hapus Puskesmas')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 8. Test Delete Region (Subdistrict & Village)
     */
    public function test_delete_region_subdistrict_and_village_protects_dependencies()
    {
        // A. Subdistrict in use
        $inUseSub = Subdistrict::whereHas('villages')->first();
        if ($inUseSub) {
            $resp = $this->actingAs($this->admin)->delete(route('admin.regions.subdistricts.destroy', $inUseSub->encrypted_id));
            $resp->assertStatus(302);
            $resp->assertSessionHas('error');
            $this->assertNotNull(Subdistrict::find($inUseSub->id));
        }

        // B. Standalone Subdistrict
        $standaloneSub = Subdistrict::create([
            'district_id' => 1,
            'name' => 'Kecamatan Uji Hapus ' . time(),
        ]);
        $subId = $standaloneSub->id;

        $respSub = $this->actingAs($this->admin)->delete(route('admin.regions.subdistricts.destroy', $standaloneSub->encrypted_id));
        $respSub->assertStatus(302);
        $respSub->assertSessionHas('success');
        $this->assertNull(Subdistrict::find($subId));

        // C. Standalone Village
        $standaloneVil = Village::create([
            'subdistrict_id' => 1,
            'name' => 'Desa Uji Hapus ' . time(),
        ]);
        $vilId = $standaloneVil->id;

        $respVil = $this->actingAs($this->admin)->delete(route('admin.regions.villages.destroy', $standaloneVil->encrypted_id));
        $respVil->assertStatus(302);
        $respVil->assertSessionHas('success');
        $this->assertNull(Village::find($vilId));
    }

    /**
     * 9. Test Delete Educational Material
     */
    public function test_delete_educational_material()
    {
        $mat = EducationalMaterial::create([
            'title_material' => 'Materi Uji Hapus ' . time(),
            'material_type' => 'video',
            'description' => 'Konten uji hapus',
            'is_publish' => true,
        ]);
        $matId = $mat->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.education.destroy', $mat->encrypted_id));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.education.index'));
        $response->assertSessionHas('success');

        $this->assertNull(EducationalMaterial::find($matId));

        $lastLog = ActivityLog::where('module', 'Edukasi')->where('activity', 'Hapus Edukasi')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 10. Test Delete System Notification
     */
    public function test_delete_system_notification()
    {
        $notif = SystemNotification::create([
            'title' => 'Notifikasi Uji Hapus ' . time(),
            'message' => 'Pesan uji coba hapus',
            'type' => 'Broadcast',
            'sent_by' => 1,
        ]);
        $nId = $notif->id;

        $response = $this->actingAs($this->admin)->delete(route('admin.notifications.destroy', $notif->encrypted_id));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.notifications.index'));
        $response->assertSessionHas('success');

        $this->assertNull(SystemNotification::find($nId));

        $lastLog = ActivityLog::where('module', 'Notifikasi')->where('activity', 'Hapus Notifikasi')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 11. Test Delete Master Data Treatment Type
     */
    public function test_delete_master_data_treatment_type_protects_in_use()
    {
        // A. Regimen in use
        $inUseRegimen = TreatmentType::whereHas('patientTreatments')->first();
        if ($inUseRegimen) {
            $resp = $this->actingAs($this->admin)->delete(route('admin.master.treatments.destroy', $inUseRegimen->encrypted_id));
            $resp->assertStatus(302);
            $resp->assertSessionHas('error');
            $this->assertNotNull(TreatmentType::find($inUseRegimen->id));
        }

        // B. Standalone Regimen
        $standalone = TreatmentType::create([
            'treatment_type' => 'Regimen Uji Hapus ' . time(),
            'treatment_duration' => 6,
            'duration_unit' => 'month',
            'description' => 'Untuk test hapus',
        ]);
        $ttId = $standalone->id;

        $resp2 = $this->actingAs($this->admin)->delete(route('admin.master.treatments.destroy', $standalone->encrypted_id));
        $resp2->assertStatus(302);
        $resp2->assertSessionHas('success');

        $this->assertNull(TreatmentType::find($ttId));

        $lastLog = ActivityLog::where('module', 'Master Data')->where('activity', 'Hapus Regimen Pengobatan')->latest('id')->first();
        $this->assertNotNull($lastLog);
    }

    /**
     * 12. Negative Test: Tampered / Corrupted Encrypted ID
     */
    public function test_delete_with_tampered_encrypted_id_aborts_404()
    {
        $tamperedEncId = 'eyJpdiI6IlFvM0tEZmR3QXRmUGJ2UWJ...InvalidCorrupted...';
        $response = $this->actingAs($this->admin)->delete('/admin/patients/' . $tamperedEncId);
        $response->assertStatus(404);
    }
}
