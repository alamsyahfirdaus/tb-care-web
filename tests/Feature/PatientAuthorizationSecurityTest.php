<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\KaderArea;
use App\Models\MedicationRecord;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Province;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\TreatmentType;
use App\Models\TreatmentVisit;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PatientAuthorizationSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setupPatientEnvironment()
    {
        $puskesmas = Puskesmas::first() ?? Puskesmas::create([
            'name'    => 'Puskesmas Cihideung',
            'address' => 'Jl. Cihideung No. 1',
        ]);

        $patientUser = User::create([
            'name'         => 'Pasien Uji ' . uniqid(),
            'username'     => 'pasien_' . uniqid(),
            'email'        => 'pasien_' . uniqid() . '@tbcare.id',
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2, // Pasien
            'is_active'    => 1,
        ]);

        $patient = Patient::create([
            'user_id'              => $patientUser->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'address'              => 'Jl. Sukalaya No. 12',
            'treatment_start_date' => Carbon::now('Asia/Jakarta')->subDays(5)->format('Y-m-d'),
        ]);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1 (2RHZE / 4RH)',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now('Asia/Jakarta')->subDays(7)->format('Y-m-d'),
            'start_date'        => Carbon::now('Asia/Jakarta')->subDays(5)->format('Y-m-d'),
            'end_date'          => Carbon::now('Asia/Jakarta')->addMonths(6)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'prescription'      => json_encode(['Rifampisin', 'Isoniazid', 'Pirazinamid', 'Etambutol']),
            'treatment_status'  => 'Berjalan',
        ]);

        $visit = TreatmentVisit::create([
            'patient_treatment_id' => $treatment->id,
            'visit_date'           => Carbon::now('Asia/Jakarta')->addDays(3)->format('Y-m-d'),
            'visit_time'           => '09:00:00',
            'visit_status'         => 'Terjadwal',
            'notes'                => 'Pemeriksaan dahak lanjutan',
        ]);

        // Petugas PJTB
        $pjtbUser = User::create([
            'name'         => 'Petugas PJTB ' . uniqid(),
            'username'     => 'pjtb_' . uniqid(),
            'email'        => 'pjtb_' . uniqid() . '@tbcare.id',
            'phone'        => '0813' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
        ]);
        Officer::create([
            'user_id'         => $pjtbUser->id,
            'puskesmas_id'    => $puskesmas->id,
            'officer_type_id' => 3,
        ]);

        return [
            'patientUser' => $patientUser,
            'patient'     => $patient,
            'treatment'   => $treatment,
            'visit'       => $visit,
            'pjtbUser'    => $pjtbUser,
            'puskesmas'   => $puskesmas,
        ];
    }

    public function test_patient_cannot_create_or_modify_treatment()
    {
        $env = $this->setupPatientEnvironment();

        // Mencoba membuat pengobatan baru
        $responseCreate = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/store', [
                'patient_id'        => $env['patient']->id,
                'treatment_type_id' => $env['treatment']->treatment_type_id,
                'medication_time'   => '09:00',
                'treatment_status'  => 'Berjalan',
            ]);

        $responseCreate->assertStatus(403);
        $responseCreate->assertJsonPath('message', 'Anda tidak memiliki wewenang untuk menambah atau mengubah data pengobatan.');

        // Mencoba mengedit pengobatan yang sudah ada
        $responseUpdate = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/store', [
                'id'                => $env['treatment']->id,
                'patient_id'        => $env['patient']->id,
                'treatment_type_id' => $env['treatment']->treatment_type_id,
                'medication_time'   => '10:00',
                'treatment_status'  => 'Berjalan',
            ]);

        $responseUpdate->assertStatus(403);
    }

    public function test_patient_cannot_delete_treatment()
    {
        $env = $this->setupPatientEnvironment();

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->deleteJson('/api/treatments/' . $env['treatment']->id . '/delete');

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Anda tidak memiliki wewenang untuk menghapus data pengobatan.');
    }

    public function test_patient_cannot_update_treatment_status()
    {
        $env = $this->setupPatientEnvironment();

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->putJson('/api/treatments/status', [
                'id'               => $env['treatment']->id,
                'treatment_status' => 'Sembuh',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Anda tidak memiliki wewenang untuk mengubah status pengobatan.');
    }

    public function test_patient_cannot_create_or_update_visit_schedule()
    {
        $env = $this->setupPatientEnvironment();

        // Create visit
        $responseCreate = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/visits/store', [
                'patient_treatment_id' => $env['treatment']->id,
                'visit_date'           => Carbon::now('Asia/Jakarta')->addDays(10)->format('Y-m-d'),
                'visit_time'           => '08:00',
            ]);

        $responseCreate->assertStatus(403);
        $responseCreate->assertJsonPath('message', 'Anda tidak memiliki wewenang untuk mengatur jadwal kunjungan.');

        // Update visit
        $responseUpdate = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/visits/store', [
                'id'                   => $env['visit']->id,
                'patient_treatment_id' => $env['treatment']->id,
                'visit_date'           => Carbon::now('Asia/Jakarta')->addDays(12)->format('Y-m-d'),
                'visit_status'         => 'Hadir',
            ]);

        $responseUpdate->assertStatus(403);
    }

    public function test_patient_cannot_delete_or_cancel_visit_schedule()
    {
        $env = $this->setupPatientEnvironment();

        // Cancel visit
        $responseCancel = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/visits/' . $env['visit']->id . '/cancel', [
                'notes' => 'Pasien batalkan sendiri',
            ]);

        $responseCancel->assertStatus(403);
        $responseCancel->assertJsonPath('message', 'Anda tidak memiliki wewenang untuk membatalkan jadwal kunjungan.');

        // Delete visit
        $responseDelete = $this->actingAs($env['patientUser'], 'sanctum')
            ->deleteJson('/api/visits/' . $env['visit']->id . '/delete');

        $responseDelete->assertStatus(403);
        $responseDelete->assertJsonPath('message', 'Anda tidak memiliki wewenang untuk menghapus data kunjungan.');
    }

    public function test_patient_cannot_delete_patient_master_record()
    {
        $env = $this->setupPatientEnvironment();

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->deleteJson('/api/patients/' . $env['patient']->id);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Anda tidak memiliki wewenang untuk menghapus data pasien.');
    }

    public function test_patient_cannot_verify_medication_proof_but_officer_can()
    {
        $env = $this->setupPatientEnvironment();

        $record = MedicationRecord::create([
            'patient_treatment_id' => $env['treatment']->id,
            'is_verified'          => false,
            'status'               => 'Diminum',
            'confirmation_source'  => 'patient',
            'late'                 => 0,
            'notes'                => 'Sudah minum obat pagi ini',
        ]);

        // Pasien mencoba memverifikasi bukti minum obat miliknya sendiri
        $responsePatient = $this->actingAs($env['patientUser'], 'sanctum')
            ->putJson('/api/treatments/verify', [
                'id'    => $record->id,
                'notes' => 'Self verification',
            ]);

        $responsePatient->assertStatus(403);
        $responsePatient->assertJsonPath('message', 'Anda tidak memiliki wewenang memverifikasi bukti minum obat.');

        // Petugas PJTB yang berwenang memverifikasi bukti minum obat
        $responseOfficer = $this->actingAs($env['pjtbUser'], 'sanctum')
            ->putJson('/api/treatments/verify', [
                'id'    => $record->id,
                'notes' => 'Tervalidasi oleh Petugas PJTB Puskesmas',
            ]);

        $responseOfficer->assertStatus(200);
        $responseOfficer->assertJsonPath('message', 'Bukti minum obat berhasil diverifikasi.');

        $this->assertDatabaseHas('medication_records', [
            'id'                => $record->id,
            'is_verified'       => 1,
            'status'            => 'Terverifikasi',
            'verified_by'       => $env['pjtbUser']->id,
            'verification_note' => 'Tervalidasi oleh Petugas PJTB Puskesmas',
        ]);
    }

    public function test_patient_medication_submission_is_pending_verification()
    {
        $env = $this->setupPatientEnvironment();
        $photo = UploadedFile::fake()->image('bukti_obat.jpg');

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $photo,
                'notes'                => 'Diminum pukul 08:00 tepat',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('medication_records', [
            'patient_treatment_id' => $env['treatment']->id,
            'is_verified'          => 0,
            'status'               => 'Diminum',
            'confirmation_source'  => 'patient',
        ]);
    }

    public function test_patient_can_view_screening_history()
    {
        $env = $this->setupPatientEnvironment();

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->getJson('/api/screening/history');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'total',
            'current_page',
            'last_page',
        ]);
    }
}
