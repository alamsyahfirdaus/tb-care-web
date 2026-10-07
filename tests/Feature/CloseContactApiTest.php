<?php

namespace Tests\Feature;

use App\Models\CloseContact;
use App\Models\Patient;
use App\Models\Puskesmas;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class CloseContactApiTest extends TestCase
{
    private function createPatient(): array
    {
        $rand = rand(10000, 99999);
        $user = User::create([
            'name'         => 'Pasien ' . $rand,
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
            'treatment_start_date' => Carbon::today()->format('Y-m-d'),
        ]);

        return compact('user', 'patient');
    }

    public function test_unauthenticated_request_is_rejected()
    {
        $this->getJson('/api/contacts')->assertStatus(401);
        $this->getJson('/api/contacts/count')->assertStatus(401);
        $this->postJson('/api/contacts', [])->assertStatus(401);
    }

    public function test_patient_can_list_own_contacts_only()
    {
        $data1 = $this->createPatient();
        $data2 = $this->createPatient();

        // Buat kontak untuk pasien 1
        $contact1 = CloseContact::create([
            'contact_code'     => 'KONT-TEST-001',
            'patient_id'       => $data1['patient']->id,
            'name'             => 'Ahmad Anak Pasien 1',
            'relationship'     => 'Anak',
            'gender'           => 'L',
            'age'              => 15,
            'screening_result' => 'Belum Skrining',
            'tpt_status'       => 'Tidak Perlu',
        ]);

        // Buat kontak untuk pasien 2
        $contact2 = CloseContact::create([
            'contact_code'     => 'KONT-TEST-002',
            'patient_id'       => $data2['patient']->id,
            'name'             => 'Budi Anak Pasien 2',
            'relationship'     => 'Anak',
            'gender'           => 'L',
            'age'              => 12,
            'screening_result' => 'Sehat / Tidak Bergejala',
            'tpt_status'       => 'Tidak Perlu',
        ]);

        // Pasien 1 request daftar kontak
        $response1 = $this->actingAs($data1['user'], 'sanctum')->getJson('/api/contacts');
        $response1->assertStatus(200);
        $response1->assertJsonPath('success', true);
        $response1->assertJsonPath('count', 1);
        $response1->assertJsonFragment(['name' => 'Ahmad Anak Pasien 1']);
        $response1->assertJsonMissing(['name' => 'Budi Anak Pasien 2']);

        // Pasien 1 request count
        $countResponse = $this->actingAs($data1['user'], 'sanctum')->getJson('/api/contacts/count');
        $countResponse->assertStatus(200);
        $countResponse->assertJsonPath('count', 1);

        // Pasien 2 request daftar kontak
        $response2 = $this->actingAs($data2['user'], 'sanctum')->getJson('/api/contacts');
        $response2->assertStatus(200);
        $response2->assertJsonPath('count', 1);
        $response2->assertJsonFragment(['name' => 'Budi Anak Pasien 2']);
        $response2->assertJsonMissing(['name' => 'Ahmad Anak Pasien 1']);

        // Cleanup
        $contact1->delete();
        $contact2->delete();
        $data1['patient']->delete();
        $data1['user']->delete();
        $data2['patient']->delete();
        $data2['user']->delete();
    }

    public function test_patient_can_create_contact_with_date_of_birth_and_auto_code()
    {
        $data = $this->createPatient();

        $payload = [
            'name'          => 'Siti Rahmawati',
            'relationship'  => 'Istri',
            'gender'        => 'P',
            'date_of_birth' => Carbon::now()->subYears(30)->format('Y-m-d'),
            'nik'           => '3278012345678901',
            'phone'         => '081234567890',
            'address'       => 'Jl. Sukasari No. 12',
        ];

        $response = $this->actingAs($data['user'], 'sanctum')->postJson('/api/contacts', $payload);
        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.name', 'Siti Rahmawati');
        $response->assertJsonPath('data.relationship', 'Istri');
        $response->assertJsonPath('data.gender', 'P');
        $response->assertJsonPath('data.gender_label', 'Perempuan');
        $response->assertJsonPath('data.age', 30);
        $response->assertJsonPath('data.screening_result', 'Belum Skrining');

        $createdId = $response->json('data.id');
        $contact = CloseContact::find($createdId);
        $this->assertNotNull($contact);
        $this->assertEquals($data['patient']->id, $contact->patient_id);
        $this->assertStringStartsWith('KONT-', $contact->contact_code);

        // Cleanup
        $contact->delete();
        $data['patient']->delete();
        $data['user']->delete();
    }

    public function test_validation_errors_when_required_fields_missing()
    {
        $data = $this->createPatient();

        $response = $this->actingAs($data['user'], 'sanctum')->postJson('/api/contacts', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'relationship', 'gender']);

        // Cleanup
        $data['patient']->delete();
        $data['user']->delete();
    }

    public function test_authorization_prevents_accessing_other_patient_contact()
    {
        $data1 = $this->createPatient();
        $data2 = $this->createPatient();

        $contact2 = CloseContact::create([
            'contact_code'     => 'KONT-TEST-SEC01',
            'patient_id'       => $data2['patient']->id,
            'name'             => 'Rahasia Pasien 2',
            'relationship'     => 'Anak',
            'gender'           => 'L',
            'age'              => 8,
            'screening_result' => 'Belum Skrining',
            'tpt_status'       => 'Tidak Perlu',
        ]);

        // Pasien 1 mencoba SHOW kontak Pasien 2
        $showResponse = $this->actingAs($data1['user'], 'sanctum')->getJson('/api/contacts/' . $contact2->id);
        $showResponse->assertStatus(403);

        // Pasien 1 mencoba UPDATE kontak Pasien 2
        $updateResponse = $this->actingAs($data1['user'], 'sanctum')->putJson('/api/contacts/' . $contact2->id, [
            'name'         => 'Hacked Name',
            'relationship' => 'Anak',
            'gender'       => 'L',
        ]);
        $updateResponse->assertStatus(403);

        // Pasien 1 mencoba DELETE kontak Pasien 2
        $deleteResponse = $this->actingAs($data1['user'], 'sanctum')->deleteJson('/api/contacts/' . $contact2->id);
        $deleteResponse->assertStatus(403);

        // Cleanup
        $contact2->delete();
        $data1['patient']->delete();
        $data1['user']->delete();
        $data2['patient']->delete();
        $data2['user']->delete();
    }

    public function test_patient_can_update_and_delete_own_contact()
    {
        $data = $this->createPatient();

        CloseContact::where('contact_code', 'like', 'KONT-TEST-CRUD%')->delete();
        $contact = CloseContact::create([
            'contact_code'     => 'KONT-TEST-CRUD-' . uniqid(),
            'patient_id'       => $data['patient']->id,
            'name'             => 'Nama Awal',
            'relationship'     => 'Saudara',
            'gender'           => 'L',
            'age'              => 20,
            'screening_result' => 'Belum Skrining',
            'tpt_status'       => 'Tidak Perlu',
        ]);

        // Update
        $updateResponse = $this->actingAs($data['user'], 'sanctum')->putJson('/api/contacts/' . $contact->id, [
            'name'         => 'Nama Setelah Diedit',
            'relationship' => 'Kakak',
            'gender'       => 'L',
            'age'          => 22,
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('data.name', 'Nama Setelah Diedit');
        $updateResponse->assertJsonPath('data.relationship', 'Kakak');
        $updateResponse->assertJsonPath('data.age', 22);

        // Delete
        $deleteResponse = $this->actingAs($data['user'], 'sanctum')->deleteJson('/api/contacts/' . $contact->id);
        $deleteResponse->assertStatus(200);
        $this->assertNull(CloseContact::find($contact->id));

        // Cleanup
        $data['patient']->delete();
        $data['user']->delete();
    }

    public function test_patient_can_screen_close_contact_and_sync_status()
    {
        $data = $this->createPatient();

        $contact = CloseContact::create([
            'contact_code'     => 'KONT-TEST-SCR-' . uniqid(),
            'patient_id'       => $data['patient']->id,
            'name'             => 'Adik Pasien',
            'relationship'     => 'Adik',
            'gender'           => 'P',
            'age'              => 14,
            'screening_result' => 'Belum Skrining',
            'tpt_status'       => 'Tidak Perlu',
        ]);

        $question = \App\Models\ScreeningQuestion::first();
        $questionId = $question ? $question->id : 1;

        $payload = [
            'close_contact_id' => $contact->id,
            'category_id'      => 2, // anak < 15 th
            'answers'          => [
                [
                    'question_id' => $questionId,
                    'answer'      => 1,
                ],
            ],
        ];

        $response = $this->actingAs($data['user'], 'sanctum')->postJson('/api/screening/submit', $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('data.close_contact_id', $contact->id);

        $screeningId = $response->json('data.id');
        $this->assertNotNull($screeningId);

        // Periksa tabel close_contacts terupdate
        $contact->refresh();
        $this->assertNotEquals('Belum Skrining', $contact->screening_result);
        $this->assertNotNull($contact->screening_date);

        // Periksa GET /api/contacts/{id} memuat latest_screening
        $detailResponse = $this->actingAs($data['user'], 'sanctum')->getJson('/api/contacts/' . $contact->id);
        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonPath('data.latest_screening.id', $screeningId);
        $detailResponse->assertJsonPath('data.screening_result', $contact->screening_result);

        // Periksa GET /api/screening/{id}
        $scrResponse = $this->actingAs($data['user'], 'sanctum')->getJson('/api/screening/' . $screeningId);
        $scrResponse->assertStatus(200);
        $scrResponse->assertJsonPath('data.id', $screeningId);
        $scrResponse->assertJsonPath('data.close_contact_id', $contact->id);
        $scrResponse->assertJsonPath('data.person_name', 'Adik Pasien');

        // Cleanup
        \App\Models\ScreeningAnswer::where('screening_id', $screeningId)->delete();
        \App\Models\Screening::where('id', $screeningId)->delete();
        $contact->delete();
        $data['patient']->delete();
        $data['user']->delete();
    }

    public function test_patient_cannot_submit_screening_for_other_patients_contact()
    {
        $data1 = $this->createPatient();
        $data2 = $this->createPatient();

        CloseContact::where('contact_code', 'like', 'KONT-TEST-SEC02%')->delete();
        $contact2 = CloseContact::create([
            'contact_code'     => 'KONT-TEST-SEC02-' . uniqid(),
            'patient_id'       => $data2['patient']->id,
            'name'             => 'Anggota Milik Pasien 2',
            'relationship'     => 'Anak',
            'gender'           => 'L',
            'age'              => 10,
        ]);

        $question = \App\Models\ScreeningQuestion::first();
        $questionId = $question ? $question->id : 1;

        $payload = [
            'close_contact_id' => $contact2->id,
            'answers'          => [
                [
                    'question_id' => $questionId,
                    'answer'      => 0,
                ],
            ],
        ];

        // Pasien 1 mencoba submit skrining untuk kontak milik Pasien 2
        $response = $this->actingAs($data1['user'], 'sanctum')->postJson('/api/screening/submit', $payload);
        $response->assertStatus(403);

        // Cleanup
        $contact2->delete();
        $data1['patient']->delete();
        $data1['user']->delete();
        $data2['patient']->delete();
        $data2['user']->delete();
    }
}
