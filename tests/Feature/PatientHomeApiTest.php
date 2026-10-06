<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Puskesmas;
use App\Models\TreatmentType;
use App\Models\TreatmentVisit;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class PatientHomeApiTest extends TestCase
{
    private function createPatientWithTreatment(): array
    {
        $rand = rand(10000, 99999);
        $user = User::create([
            'name'         => 'Test Pasien ' . $rand,
            'username'     => 'patient_' . $rand,
            'email'        => 'patient_' . $rand . '@tbcare.id',
            'phone'        => '0812' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $puskesmas = Puskesmas::first();
        $puskesmasId = $puskesmas ? $puskesmas->id : 1;

        $patient = Patient::create([
            'user_id'              => $user->id,
            'nik'                  => '3278' . str_pad((string) $rand, 12, '0', STR_PAD_LEFT),
            'puskesmas_id'         => $puskesmasId,
            'treatment_start_date' => Carbon::today()->subDays(10)->format('Y-m-d'),
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => 1,
            'treatment_status'  => 'Berjalan',
            'start_date'        => Carbon::today()->subDays(10)->format('Y-m-d'),
            'end_date'          => Carbon::today()->addDays(170)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '08:00',
        ]);

        return compact('user', 'patient', 'treatment');
    }

    public function test_unauthenticated_request_is_rejected()
    {
        $response = $this->getJson('/api/patient/home');
        $response->assertStatus(401);
    }

    public function test_patient_can_get_home_data()
    {
        $data = $this->createPatientWithTreatment();
        $user = $data['user'];
        $patient = $data['patient'];
        $treatment = $data['treatment'];

        $visit = TreatmentVisit::create([
            'patient_treatment_id' => $treatment->id,
            'visit_date'           => Carbon::today()->addDays(3)->format('Y-m-d'),
            'visit_time'           => '09:00:00',
            'visit_status'         => 'Terjadwal',
            'notes'                => 'Kontrol OAT',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/patient/home');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data beranda berhasil dimuat.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'patient' => [
                        'id', 'user_id', 'name', 'nik', 'phone', 'gender', 'photo', 'puskesmas_name',
                    ],
                    'treatment' => [
                        'id', 'treatment_type_name', 'treatment_status', 'start_date', 'current_day', 'total_days', 'progress_percent', 'medication_time',
                    ],
                    'next_visit' => [
                        'id', 'visit_date', 'visit_time', 'visit_status', 'puskesmas_name',
                    ],
                    'upcoming_visits',
                    'medication' => [
                        'reminder_time', 'is_taken_today',
                    ],
                    'consultation',
                    'notifications',
                    'unread_notifications_count',
                    'education',
                ],
            ]);

        $this->assertEquals($user->name, $response->json('data.patient.name'));
        $this->assertEquals(11, $response->json('data.treatment.current_day'));
        $this->assertEquals($visit->id, $response->json('data.next_visit.id'));

        // Clean up
        $visit->delete();
        $treatment->delete();
        $patient->delete();
        $user->delete();
    }

    public function test_idor_protection_patient_cannot_view_other_patient_home()
    {
        $dataA = $this->createPatientWithTreatment();
        $dataB = $this->createPatientWithTreatment();

        // User A attempts to access patient B
        $response = $this->actingAs($dataA['user'], 'sanctum')->getJson("/api/patients/{$dataB['patient']->id}/home");
        $response->assertStatus(403);

        $dataA['treatment']->delete();
        $dataA['patient']->delete();
        $dataA['user']->delete();

        $dataB['treatment']->delete();
        $dataB['patient']->delete();
        $dataB['user']->delete();
    }
}
