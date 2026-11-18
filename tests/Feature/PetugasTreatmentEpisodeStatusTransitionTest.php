<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\TreatmentType;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetugasTreatmentEpisodeStatusTransitionTest extends TestCase
{
    use DatabaseTransactions;

    protected $puskesmas;
    protected $subdistrict;
    protected $village;
    protected $pjtbUser;
    protected $pjtbOfficer;
    protected $patientUser;
    protected $patient;
    protected $treatmentType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->puskesmas = Puskesmas::first() ?? Puskesmas::create([
            'name' => 'Puskesmas Test',
            'code' => 'P' . rand(1000, 9999),
        ]);

        $this->subdistrict = Subdistrict::first();
        $this->village = Village::first();
        $this->treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type' => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit' => 'month',
        ]);

        // Setup PJTB User (user_type_id = 3, officer_type_id = 3)
        $this->pjtbUser = User::create([
            'name'         => 'PJTB Test Officer',
            'username'     => 'pjtb_test_' . uniqid(),
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);

        $this->pjtbOfficer = Officer::create([
            'user_id'         => $this->pjtbUser->id,
            'officer_type_id' => 3,
            'puskesmas_id'    => $this->puskesmas->id,
            'district_id'     => District::first()?->id,
        ]);

        // Setup Patient User
        $this->patientUser = User::create([
            'name'         => 'Pasien Test Transition',
            'username'     => 'pasien_' . uniqid(),
            'phone'        => '0815' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $this->patient = Patient::create([
            'user_id'              => $this->patientUser->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'address'              => 'Jl. Test No. 1',
            'village_id'           => $this->village?->id,
            'subdistrict_id'       => $this->subdistrict?->id,
            'puskesmas_id'         => $this->puskesmas->id,
            'treatment_start_date' => '2026-01-01',
        ]);
    }

    /** @test */
    public function adding_new_treatment_automatically_transitions_previous_berjalan_treatment_to_perlu_tindak_lanjut()
    {
        Sanctum::actingAs($this->pjtbUser);

        // 1. Episode 1 yang masih Berjalan
        $oldTreatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-06-30',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Berjalan',
            'prescription'      => json_encode(['Rifampisin 450mg']),
        ]);

        $this->assertEquals('Berjalan', $oldTreatment->treatment_status);

        // 2. Petugas menambahkan episode 2 baru
        $response = $this->postJson('/api/treatments/store', [
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-11-10',
            'end_date'          => '2027-05-10',
            'medication_time'   => '21:00',
            'treatment_status'  => 'Berjalan',
            'prescription'      => ['Rifampisin 450mg', 'Isoniazid 300mg'],
        ]);

        $response->assertStatus(201);

        // 3. Verifikasi: status episode sebelumnya berubah menjadi 'Perlu Tindak Lanjut'
        $oldTreatment->refresh();
        $this->assertEquals('Perlu Tindak Lanjut', $oldTreatment->treatment_status);

        // 4. Verifikasi: episode baru berstatus 'Berjalan'
        $newTreatmentId = $response->json('data.id');
        $newTreatment = PatientTreatment::find($newTreatmentId);
        $this->assertNotNull($newTreatment);
        $this->assertEquals('Berjalan', $newTreatment->treatment_status);

        // 5. Verifikasi: kedua episode tetap ada di database (riwayat tidak terhapus)
        $this->assertDatabaseHas('patient_treatments', ['id' => $oldTreatment->id]);
        $this->assertDatabaseHas('patient_treatments', ['id' => $newTreatment->id]);
        $this->assertEquals(2, PatientTreatment::where('patient_id', $this->patient->id)->count());

        // 6. Verifikasi: treatment_start_date pasien disinkronkan ke episode berjalan baru
        $this->assertEquals('2026-11-10', Carbon::parse($this->patient->fresh()->treatment_start_date)->format('Y-m-d'));
    }

    /** @test */
    public function adding_new_treatment_when_no_prior_berjalan_treatment_does_not_modify_other_treatments()
    {
        Sanctum::actingAs($this->pjtbUser);

        // Pasien memiliki episode lama yang sudah Sembuh
        $completedTreatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2025-01-01',
            'end_date'          => '2025-06-30',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Sembuh',
            'prescription'      => json_encode(['Rifampisin 450mg']),
        ]);

        // Tambah pengobatan baru
        $response = $this->postJson('/api/treatments/store', [
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-11-10',
            'end_date'          => '2027-05-10',
            'medication_time'   => '08:00',
            'treatment_status'  => 'Berjalan',
        ]);

        $response->assertStatus(201);

        // Status episode Sembuh TIDAK berubah
        $completedTreatment->refresh();
        $this->assertEquals('Sembuh', $completedTreatment->treatment_status);
        $this->assertEquals(2, PatientTreatment::where('patient_id', $this->patient->id)->count());
    }

    /** @test */
    public function update_treatment_status_endpoint_updates_specific_episode()
    {
        Sanctum::actingAs($this->pjtbUser);

        $treatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-06-30',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Perlu Tindak Lanjut',
        ]);

        $response = $this->postJson('/api/treatments/status', [
            'id'               => $treatment->id,
            'treatment_status' => 'Sembuh',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Sembuh', $treatment->fresh()->treatment_status);
    }

    /** @test */
    public function deleting_the_only_treatment_of_patient_is_rejected()
    {
        Sanctum::actingAs($this->pjtbUser);

        // Hanya 1 treatment
        $onlyTreatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-06-30',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Berjalan',
        ]);

        $response = $this->deleteJson("/api/treatments/{$onlyTreatment->id}/delete");
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Periode pengobatan terakhir pasien tidak dapat dihapus. Pasien harus memiliki minimal satu periode pengobatan.'
        ]);
        $this->assertDatabaseHas('patient_treatments', ['id' => $onlyTreatment->id]);
    }

    /** @test */
    public function deleting_older_treatment_when_newer_exists_is_rejected()
    {
        Sanctum::actingAs($this->pjtbUser);

        $olderTreatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-06-30',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Perlu Tindak Lanjut',
        ]);

        $newerTreatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-11-10',
            'end_date'          => '2027-05-10',
            'treatment_days'    => 180,
            'medication_time'   => '21:00:00',
            'treatment_status'  => 'Berjalan',
        ]);

        // Coba hapus older treatment
        $response = $this->deleteJson("/api/treatments/{$olderTreatment->id}/delete");
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Hanya periode pengobatan terbaru yang dapat dihapus.'
        ]);
        $this->assertDatabaseHas('patient_treatments', ['id' => $olderTreatment->id]);
    }

    /** @test */
    public function deleting_newly_added_treatment_without_medication_records_succeeds_when_patient_has_multiple_treatments()
    {
        Sanctum::actingAs($this->pjtbUser);

        // Episode lama
        $oldTreatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-06-30',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Perlu Tindak Lanjut',
        ]);

        // Episode baru (terbaru)
        $newTreatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-11-17',
            'end_date'          => '2027-05-17',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Berjalan',
        ]);

        $response = $this->deleteJson("/api/treatments/{$newTreatment->id}/delete");
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'message' => 'Periode pengobatan berhasil dihapus.'
        ]);
        $this->assertDatabaseMissing('patient_treatments', ['id' => $newTreatment->id]);
        $this->assertDatabaseHas('patient_treatments', ['id' => $oldTreatment->id]);
    }

    /** @test */
    public function deleting_treatment_with_medication_records_is_rejected_for_data_safety()
    {
        Sanctum::actingAs($this->pjtbUser);

        // Episode lama
        PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-01-01',
            'end_date'          => '2026-06-30',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Perlu Tindak Lanjut',
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => '2026-11-17',
            'end_date'          => '2027-05-17',
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Berjalan',
        ]);

        // Tambah dummy medication record
        \App\Models\MedicationRecord::create([
            'patient_treatment_id' => $treatment->id,
            'is_verified'          => true,
            'status'               => 'diminum',
        ]);

        $response = $this->deleteJson("/api/treatments/{$treatment->id}/delete");
        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Tidak dapat menghapus periode pengobatan yang sudah memiliki riwayat minum obat.'
        ]);
        $this->assertDatabaseHas('patient_treatments', ['id' => $treatment->id]);
    }
}
