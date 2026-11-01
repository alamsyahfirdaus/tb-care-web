<?php

namespace Tests\Feature;

use App\Models\EducationalMaterial;
use App\Models\SystemNotification;
use App\Models\User;
use Tests\TestCase;

class EducationNotificationTest extends TestCase
{
    private function createOfficerUser(): User
    {
        $rand = rand(10000, 99999);
        return User::create([
            'name'         => 'Petugas ' . $rand,
            'username'     => 'officer_' . $rand,
            'email'        => 'officer_' . $rand . '@tbcare.id',
            'phone'        => '0813' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3, // Petugas PJTB
            'is_active'    => true,
        ]);
    }

    private function createPatientWithFcm(): User
    {
        $rand = rand(10000, 99999);
        return User::create([
            'name'         => 'Pasien FCM ' . $rand,
            'username'     => 'pasien_' . $rand,
            'email'        => 'pasien_' . $rand . '@tbcare.id',
            'phone'        => '0812' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2, // Pasien
            'fcm_token'    => 'fcm_test_token_' . $rand,
            'is_active'    => true,
        ]);
    }

    public function test_draft_material_does_not_trigger_notification()
    {
        $officer = $this->createOfficerUser();
        $initialNotifCount = SystemNotification::count();

        $material = EducationalMaterial::create([
            'title_material' => 'Draft Materi ' . rand(100, 999),
            'description'    => 'Deskripsi draft',
            'material_type'  => 'image',
            'is_publish'     => 0,
            'created_by'     => $officer->id,
        ]);

        $this->assertFalse((bool) $material->notification_sent);
        $this->assertEquals($initialNotifCount, SystemNotification::count());
    }

    public function test_publishing_material_triggers_notification_and_prevents_duplicate()
    {
        $officer = $this->createOfficerUser();
        $patient = $this->createPatientWithFcm();

        $initialNotifCount = SystemNotification::count();

        $material = EducationalMaterial::create([
            'title_material' => 'Panduan Minum Obat ' . rand(100, 999),
            'description'    => 'Deskripsi edukasi',
            'material_type'  => 'video',
            'video_url'      => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_publish'     => 1,
            'created_by'     => $officer->id,
        ]);

        $result = $material->sendPublishNotification();
        $this->assertTrue($result);

        $material->refresh();
        $this->assertTrue((bool) $material->notification_sent);

        // Record harus tersimpan di SystemNotification
        $this->assertEquals($initialNotifCount + 1, SystemNotification::count());
        $latestNotif = SystemNotification::latest('id')->first();
        $this->assertEquals('Edukasi', $latestNotif->type);
        $this->assertStringContainsString($material->title_material, $latestNotif->message);

        // Tes pemanggilan ulang (idempotency / cegah duplikasi)
        $secondResult = $material->sendPublishNotification();
        $this->assertFalse($secondResult);
        $this->assertEquals($initialNotifCount + 1, SystemNotification::count());
    }

    public function test_api_education_supports_limit_parameter()
    {
        $patient = $this->createPatientWithFcm();

        for ($i = 0; $i < 5; $i++) {
            EducationalMaterial::create([
                'title_material' => "Materi Test $i " . rand(100, 999),
                'description'    => 'Deskripsi materi',
                'material_type'  => 'image',
                'is_publish'     => 1,
                'created_by'     => $patient->id,
            ]);
        }

        $response = $this->actingAs($patient, 'sanctum')->getJson('/api/education?limit=3');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_api_education_show_returns_material_detail()
    {
        $patient = $this->createPatientWithFcm();

        $material = EducationalMaterial::create([
            'title_material' => 'Detail Materi Test ' . rand(100, 999),
            'description'    => 'Deskripsi lengkap',
            'material_type'  => 'video',
            'video_url'      => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_publish'     => 1,
            'created_by'     => $patient->id,
        ]);

        $response = $this->actingAs($patient, 'sanctum')->getJson("/api/education/{$material->id}/show");
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $material->id);
        $response->assertJsonPath('data.title_material', $material->title_material);
    }

    public function test_api_education_returns_raw_image_filename_consistent_with_medication_proof()
    {
        $patient = $this->createPatientWithFcm();

        $material = EducationalMaterial::create([
            'title_material' => 'Etika Batuk Test',
            'description'    => 'Panduan batuk',
            'material_type'  => 'image',
            'image_path'     => 'f4yU8tmJg0hRk84AaPdH.jpg',
            'is_publish'     => 1,
            'created_by'     => $patient->id,
        ]);

        $response = $this->actingAs($patient, 'sanctum')->getJson("/api/education/{$material->id}/show");
        $response->assertStatus(200);
        $response->assertJsonPath('data.photo', 'f4yU8tmJg0hRk84AaPdH.jpg');
    }

    public function test_api_image_serves_file_from_public_images_with_and_without_auth()
    {
        $testFile = public_path('images/test_img_' . rand(1000, 9999) . '.jpg');
        file_put_contents($testFile, 'dummy-image-content');
        $fileName = basename($testFile);

        try {
            // Tanpa Auth (Public / CachedNetworkImage)
            $resNoAuth = $this->get("/api/image/{$fileName}");
            $resNoAuth->assertStatus(200);

            // Dengan Auth Sanctum
            $patient = $this->createPatientWithFcm();
            $resWithAuth = $this->actingAs($patient, 'sanctum')->get("/api/image/{$fileName}");
            $resWithAuth->assertStatus(200);
        } finally {
            if (file_exists($testFile)) {
                unlink($testFile);
            }
        }
    }
}
