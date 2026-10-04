<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\TreatmentVisit;
use App\Models\User;
use App\Models\Puskesmas;
use Carbon\Carbon;
use Tests\TestCase;

class TreatmentVisitApiTest extends TestCase
{
    private function createPatientWithTreatment(): array
    {
        $uniquePhone = '0813' . str_pad((string) rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $uniqueNik = '3278' . str_pad((string) rand(100000000000, 999999999999), 12, '0', STR_PAD_LEFT);

        $user = User::create([
            'name'         => 'Test Patient ' . rand(100, 999),
            'username'     => 'patient_' . rand(1000, 9999),
            'email'        => 'patient_' . rand(1000, 9999) . '@tbcare.id',
            'phone'        => $uniquePhone,
            'password'     => bcrypt('123456'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $puskesmas = Puskesmas::first();
        $puskesmasId = $puskesmas ? $puskesmas->id : 1;

        $patient = Patient::create([
            'user_id'              => $user->id,
            'nik'                  => $uniqueNik,
            'puskesmas_id'         => $puskesmasId,
            'treatment_start_date' => Carbon::today()->format('Y-m-d'),
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'         => $patient->id,
            'treatment_type_id'  => 1,
            'diagnosis_date'     => null,
            'start_date'         => Carbon::today()->format('Y-m-d'),
            'medication_time'    => '07:00:00',
            'treatment_status'   => 'Berjalan',
        ]);

        return [$user, $patient, $treatment];
    }

    public function test_patient_can_get_own_visits()
    {
        [$user, $patient, $treatment] = $this->createPatientWithTreatment();

        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $nextWeek = Carbon::today()->addDays(7)->format('Y-m-d');

        $visit1 = TreatmentVisit::create([
            'patient_treatment_id' => $treatment->id,
            'visit_date'           => $tomorrow,
            'visit_time'           => '09:00',
            'visit_status'         => 'Terjadwal',
            'notes'                => 'Pemeriksaan Rutin',
        ]);

        $visit2 = TreatmentVisit::create([
            'patient_treatment_id' => $treatment->id,
            'visit_date'           => $nextWeek,
            'visit_time'           => '10:00',
            'visit_status'         => 'Terjadwal',
            'notes'                => 'Evaluasi Dahak',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/patient/visits');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'puskesmas_name',
            'data' => [
                '*' => [
                    'id',
                    'patient_treatment_id',
                    'visit_date',
                    'visit_time',
                    'visit_status',
                    'notes',
                    'puskesmas_name',
                ]
            ]
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals($visit1->id, $data[0]['id']);
        $this->assertEquals($visit2->id, $data[1]['id']);
        $this->assertNotEmpty($data[0]['puskesmas_name']);

        // Clean up
        $visit1->delete();
        $visit2->delete();
        $treatment->delete();
        $patient->delete();
        $user->delete();
    }

    public function test_idor_protection_for_patient_visits()
    {
        [$userA, $patientA, $treatmentA] = $this->createPatientWithTreatment();
        [$userB, $patientB, $treatmentB] = $this->createPatientWithTreatment();

        $visitA = TreatmentVisit::create([
            'patient_treatment_id' => $treatmentA->id,
            'visit_date'           => Carbon::tomorrow()->format('Y-m-d'),
            'visit_time'           => '08:30',
            'visit_status'         => 'Terjadwal',
            'notes'                => 'Rahasia Pasien A',
        ]);

        // User B attempts to access User A's visit detail
        $response = $this->actingAs($userB, 'sanctum')->getJson("/api/visits/{$visitA->id}");
        $response->assertStatus(403);

        // User B attempts to access User A's treatment visits list
        $response2 = $this->actingAs($userB, 'sanctum')->getJson("/api/treatments/{$treatmentA->id}/visits");
        $response2->assertStatus(403);

        // User A can access own visit
        $response3 = $this->actingAs($userA, 'sanctum')->getJson("/api/visits/{$visitA->id}");
        $response3->assertStatus(200);
        $this->assertEquals($visitA->id, $response3->json('data.id'));

        // Clean up
        $visitA->delete();
        $treatmentA->delete();
        $patientA->delete();
        $userA->delete();

        $treatmentB->delete();
        $patientB->delete();
        $userB->delete();
    }
}
