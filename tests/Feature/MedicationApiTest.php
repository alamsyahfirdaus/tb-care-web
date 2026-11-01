<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Puskesmas;
use App\Models\TreatmentType;
use App\Models\User;
use App\Models\Officer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MedicationApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function createPatientWithTreatment(array $prescription = ['Rifampisin', 'Isoniazid', 'Pirazinamid'], $medicationTime = '07:00:00')
    {
        $puskesmas = Puskesmas::first() ?? Puskesmas::create([
            'name'    => 'Puskesmas Cihideung',
            'address' => 'Jl. Cihideung No. 1',
        ]);

        $user = User::create([
            'name'         => 'Pasien Test ' . uniqid(),
            'username'     => 'pasien_' . uniqid(),
            'email'        => 'pasien_' . uniqid() . '@tbcare.id',
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2, // Pasien
        ]);

        $patient = Patient::create([
            'user_id'              => $user->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'address'              => 'Jl. Test No. 1',
            'treatment_start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
        ]);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now()->subDays(12)->format('Y-m-d'),
            'start_date'        => Carbon::now()->subDays(10)->format('Y-m-d'),
            'end_date'          => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => $medicationTime,
            'prescription'      => json_encode($prescription),
            'treatment_status'  => 'Berjalan',
        ]);

        return [
            'user'      => $user,
            'patient'   => $patient,
            'treatment' => $treatment,
        ];
    }

    public function test_unauthenticated_request_is_rejected()
    {
        $response = $this->getJson('/api/treatments/medications');
        $response->assertStatus(401);
    }

    public function test_patient_can_get_own_medications()
    {
        $data = $this->createPatientWithTreatment(['Rifampisin', 'Isoniazid', 'Pirazinamid'], '08:00:00');

        $response = $this->actingAs($data['user'], 'sanctum')->getJson('/api/treatments/medications');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.count', 3);
        $response->assertJsonPath('data.treatment_status', 'Berjalan');
        $response->assertJsonPath('data.medication_time', '08:00');
        $response->assertJsonPath('data.medications.0.name', 'Rifampisin');
        $response->assertJsonPath('data.medications.1.name', 'Isoniazid');
        $response->assertJsonPath('data.medications.2.name', 'Pirazinamid');
    }

    public function test_idor_protection_patient_cannot_view_other_patient_medications()
    {
        $patient1 = $this->createPatientWithTreatment(['Rifampisin', 'Isoniazid'], '07:00:00');
        $patient2 = $this->createPatientWithTreatment(['Etambutol', 'Streptomisin'], '09:00:00');

        // Pasien 1 mencoba meminta obat dengan query param patient_id milik Pasien 2
        $response = $this->actingAs($patient1['user'], 'sanctum')
            ->getJson('/api/treatments/medications?patient_id=' . $patient2['patient']->id);

        $response->assertStatus(200);
        // Karena caller adalah user_type_id = 2, backend SELALU mengambil data caller sendiri
        $response->assertJsonPath('data.count', 2);
        $response->assertJsonPath('data.medications.0.name', 'Rifampisin');
        $response->assertJsonPath('data.medications.1.name', 'Isoniazid');
    }

    public function test_patient_without_prescription_returns_empty_cleanly()
    {
        $data = $this->createPatientWithTreatment([]);

        $response = $this->actingAs($data['user'], 'sanctum')->getJson('/api/treatments/medications');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.count', 0);
        $response->assertJsonPath('data.medications', []);
    }

    public function test_treatment_history_returns_normalized_medications_and_prescriptions_with_newline()
    {
        $puskesmas = Puskesmas::first() ?? Puskesmas::create([
            'name'    => 'Puskesmas Cihideung',
            'address' => 'Jl. Cihideung No. 1',
        ]);

        $user = User::create([
            'name'         => 'Pasien Multi ' . uniqid(),
            'username'     => 'pasien_' . uniqid(),
            'email'        => 'pasien_' . uniqid() . '@tbcare.id',
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
        ]);

        $patient = Patient::create([
            'user_id'              => $user->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'address'              => 'Jl. Test No. 1',
            'treatment_start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
        ]);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        // Simpan format persis seperti data produksi: array berisi 1 string dengan newline
        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now()->subDays(12)->format('Y-m-d'),
            'start_date'        => Carbon::now()->subDays(10)->format('Y-m-d'),
            'end_date'          => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '07:30:00',
            'prescription'      => json_encode(["Isoniazid\nripamfisine\npirazinamid\nethambutol"]),
            'treatment_status'  => 'Berjalan',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/patients/{$patient->id}/treatments");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Riwayat pengobatan berhasil diambil.');
        $response->assertJsonPath('data.0.id', $treatment->id);
        $response->assertJsonPath('data.0.prescription.0', 'Isoniazid');
        $response->assertJsonPath('data.0.prescription.1', 'ripamfisine');
        $response->assertJsonPath('data.0.prescription.2', 'pirazinamid');
        $response->assertJsonPath('data.0.prescription.3', 'ethambutol');
        $response->assertJsonPath('data.0.medications.0.name', 'Isoniazid');
        $response->assertJsonPath('data.0.medications.0.schedule_time', '07:30');
        $response->assertJsonPath('data.0.medications.3.name', 'ethambutol');
    }

    public function test_treatment_history_scopes_medications_per_treatment()
    {
        $puskesmas = Puskesmas::first() ?? Puskesmas::create([
            'name'    => 'Puskesmas Cihideung',
            'address' => 'Jl. Cihideung No. 1',
        ]);

        $user = User::create([
            'name'         => 'Pasien Dua Pengobatan ' . uniqid(),
            'username'     => 'pasien_' . uniqid(),
            'email'        => 'pasien_' . uniqid() . '@tbcare.id',
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
        ]);

        $patient = Patient::create([
            'user_id'              => $user->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'address'              => 'Jl. Test No. 2',
            'treatment_start_date' => Carbon::now()->subMonths(8)->format('Y-m-d'),
        ]);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        // Pengobatan 1 (Lama) -> Amoxicillin saja
        PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now()->subMonths(8)->format('Y-m-d'),
            'start_date'        => Carbon::now()->subMonths(8)->format('Y-m-d'),
            'end_date'          => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'prescription'      => json_encode(['Amoxicillin']),
            'treatment_status'  => 'Selesai',
        ]);

        // Pengobatan 2 (Baru) -> 4 FDC
        PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now()->subDays(10)->format('Y-m-d'),
            'start_date'        => Carbon::now()->subDays(5)->format('Y-m-d'),
            'end_date'          => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '07:00:00',
            'prescription'      => json_encode(['Rifampisin', 'Isoniazid', 'Pyrazinamide', 'Ethambutol']),
            'treatment_status'  => 'Berjalan',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/patients/{$patient->id}/treatments");

        $response->assertStatus(200);
        // Terurut descending by start_date: [0] = Pengobatan baru, [1] = Pengobatan lama
        $response->assertJsonCount(2, 'data');
        $this->assertEquals(['Rifampisin', 'Isoniazid', 'Pyrazinamide', 'Ethambutol'], $response->json('data.0.prescription'));
        $this->assertEquals(['Amoxicillin'], $response->json('data.1.prescription'));
    }

    public function test_treatment_history_with_null_prescription_returns_empty_cleanly()
    {
        $puskesmas = Puskesmas::first() ?? Puskesmas::create([
            'name'    => 'Puskesmas Cihideung',
            'address' => 'Jl. Cihideung No. 1',
        ]);

        $user = User::create([
            'name'         => 'Pasien Null ' . uniqid(),
            'username'     => 'pasien_' . uniqid(),
            'email'        => 'pasien_' . uniqid() . '@tbcare.id',
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
        ]);

        $patient = Patient::create([
            'user_id'              => $user->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'address'              => 'Jl. Test No. 3',
            'treatment_start_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
        ]);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now()->subDays(2)->format('Y-m-d'),
            'start_date'        => Carbon::now()->subDays(2)->format('Y-m-d'),
            'end_date'          => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => null,
            'prescription'      => null, // NULL di database
            'treatment_status'  => 'Berjalan',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/patients/{$patient->id}/treatments");

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.prescription', null);
        $response->assertJsonPath('data.0.medications', []);
    }
}

