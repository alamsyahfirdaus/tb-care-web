<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\EducationalMaterial;
use App\Models\EducationalMaterialRead;
use App\Models\KaderArea;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class NotificationApiAndEducationReadTest extends TestCase
{
    private function createKaderUser(int $villageId, int $subdistrictId, int $puskesmasId): User
    {
        $rand = rand(10000, 99999);
        $user = User::create([
            'name'         => 'Kader Test ' . $rand,
            'username'     => 'kader_' . $rand,
            'email'        => 'kader_' . $rand . '@tbcare.id',
            'phone'        => '0852' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3, // Officer
            'fcm_token'    => 'fcm_kader_' . $rand,
            'is_active'    => true,
        ]);

        $officer = Officer::create([
            'user_id'         => $user->id,
            'officer_type_id' => 4, // Kader
            'puskesmas_id'    => $puskesmasId,
        ]);

        KaderArea::create([
            'officer_id'     => $officer->id,
            'village_id'     => $villageId,
            'subdistrict_id' => $subdistrictId,
        ]);

        return $user;
    }

    private function createPetugasUser(int $puskesmasId): User
    {
        $rand = rand(10000, 99999);
        $user = User::create([
            'name'         => 'Petugas PJTB ' . $rand,
            'username'     => 'pjtb_' . $rand,
            'email'        => 'pjtb_' . $rand . '@tbcare.id',
            'phone'        => '0853' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3, // Officer
            'is_active'    => true,
        ]);

        Officer::create([
            'user_id'         => $user->id,
            'officer_type_id' => 3, // PJTB Puskesmas
            'puskesmas_id'    => $puskesmasId,
        ]);

        return $user;
    }

    private function createPatientUser(int $villageId, int $puskesmasId): array
    {
        $rand = rand(10000, 99999);
        $user = User::create([
            'name'         => 'Pasien Test ' . $rand,
            'username'     => 'pasien_' . $rand,
            'email'        => 'pasien_' . $rand . '@tbcare.id',
            'phone'        => '0812' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2, // Pasien
            'fcm_token'    => 'fcm_pasien_' . $rand,
            'is_active'    => true,
        ]);

        $patient = Patient::create([
            'user_id'              => $user->id,
            'nik'                  => '3278' . $rand . '0001',
            'puskesmas_id'         => $puskesmasId,
            'village_id'           => $villageId,
            'treatment_start_date' => Carbon::today('Asia/Jakarta')->subDays(10)->toDateString(),
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_status'  => 'Berjalan',
            'start_date'        => Carbon::today('Asia/Jakarta')->subDays(10)->toDateString(),
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
        ]);

        return [$user, $patient, $treatment];
    }

    private function getOrCreateTerritory(): array
    {
        $dist = District::firstOrCreate(
            ['name' => 'Kecamatan Test Pusat'],
            ['province_id' => 1]
        );

        $sub = Subdistrict::firstOrCreate(
            ['name' => 'Subdistrict Test'],
            ['district_id' => $dist->id]
        );

        $pkm = Puskesmas::firstOrCreate(
            ['name' => 'Puskesmas Test Notif'],
            ['subdistrict_id' => $sub->id, 'address' => 'Jl. Puskesmas No. 1']
        );

        $vil = Village::firstOrCreate(
            ['name' => 'Desa Binaan Test'],
            ['subdistrict_id' => $sub->id]
        );

        return [$vil->id, $sub->id, $pkm->id];
    }

    public function test_unauthenticated_request_to_notifications_is_rejected()
    {
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(401);
    }

    public function test_publishing_education_dispatches_in_app_notification_to_kader()
    {
        [$villageId, $subdistrictId, $puskesmasId] = $this->getOrCreateTerritory();
        $kader = $this->createKaderUser($villageId, $subdistrictId, $puskesmasId);
        $petugas = $this->createPetugasUser($puskesmasId);

        $material = EducationalMaterial::create([
            'title_material' => 'Materi Edukasi Khusus Kader ' . rand(100, 999),
            'description'    => 'Konten edukasi tuberkulosis',
            'material_type'  => 'video',
            'video_url'      => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_publish'     => 1,
            'created_by'     => $petugas->id,
        ]);

        $published = $material->sendPublishNotification();
        $this->assertTrue($published);

        // Kader menerima notifikasi in-app di database
        $kaderNotifs = $kader->notifications;
        $this->assertNotEmpty($kaderNotifs);

        $notif = $kaderNotifs->first();
        $this->assertEquals('education_published', $notif->data['notification_type']);
        $this->assertEquals($material->id, $notif->data['education_id']);
        $this->assertStringContainsString($material->title_material, $notif->data['message']);

        // Idempotency: Publikasi ulang tidak menduplikasi notifikasi
        $republished = $material->sendPublishNotification();
        $this->assertFalse($republished);
        $this->assertCount(1, $kader->fresh()->notifications);
    }

    public function test_kader_can_list_notifications_and_get_unread_count()
    {
        [$villageId, $subdistrictId, $puskesmasId] = $this->getOrCreateTerritory();
        $kader = $this->createKaderUser($villageId, $subdistrictId, $puskesmasId);
        $petugas = $this->createPetugasUser($puskesmasId);

        $material = EducationalMaterial::create([
            'title_material' => 'Panduan Perilaku Hidup Sehat ' . rand(100, 999),
            'description'    => 'Deskripsi materi',
            'material_type'  => 'image',
            'is_publish'     => 1,
            'created_by'     => $petugas->id,
        ]);

        $material->sendPublishNotification();

        // 1. Cek unread count endpoint
        $resCount = $this->actingAs($kader, 'sanctum')->getJson('/api/notifications/unread-count');
        $resCount->assertStatus(200);
        $resCount->assertJson(['success' => true]);
        $this->assertGreaterThanOrEqual(1, $resCount->json('unread_count'));

        // 2. Cek list notifications endpoint
        $resList = $this->actingAs($kader, 'sanctum')->getJson('/api/notifications');
        $resList->assertStatus(200);
        $resList->assertJsonStructure([
            'success',
            'unread_count',
            'data' => [
                '*' => ['id', 'type', 'title', 'message', 'is_read', 'read_at', 'created_at', 'time_ago', 'payload']
            ]
        ]);

        $items = $resList->json('data');
        $this->assertNotEmpty($items);
        $found = collect($items)->firstWhere('payload.education_id', $material->id);
        $this->assertNotNull($found);
        $this->assertFalse($found['is_read']);
    }

    public function test_marking_notification_as_read_and_mark_all_as_read()
    {
        [$villageId, $subdistrictId, $puskesmasId] = $this->getOrCreateTerritory();
        $kader = $this->createKaderUser($villageId, $subdistrictId, $puskesmasId);
        $petugas = $this->createPetugasUser($puskesmasId);

        $material1 = EducationalMaterial::create([
            'title_material' => 'Materi Notif 1 ' . rand(100, 999),
            'material_type'  => 'image',
            'is_publish'     => 1,
            'created_by'     => $petugas->id,
        ]);
        $material1->sendPublishNotification();

        $material2 = EducationalMaterial::create([
            'title_material' => 'Materi Notif 2 ' . rand(100, 999),
            'material_type'  => 'image',
            'is_publish'     => 1,
            'created_by'     => $petugas->id,
        ]);
        $material2->sendPublishNotification();

        $notif = $kader->unreadNotifications()->first();
        $this->assertNotNull($notif);

        // Mark single as read
        $resRead = $this->actingAs($kader, 'sanctum')->postJson("/api/notifications/{$notif->id}/read");
        $resRead->assertStatus(200);
        $resRead->assertJson(['success' => true]);

        $notif->refresh();
        $this->assertNotNull($notif->read_at);

        // Mark all as read
        $resMarkAll = $this->actingAs($kader, 'sanctum')->postJson('/api/notifications/mark-all-read');
        $resMarkAll->assertStatus(200);
        $resMarkAll->assertJson(['success' => true, 'unread_count' => 0]);

        $this->assertEquals(0, $kader->fresh()->unreadNotifications()->count());
    }

    public function test_opening_education_detail_records_read_status_and_marks_notification_read()
    {
        [$villageId, $subdistrictId, $puskesmasId] = $this->getOrCreateTerritory();
        $kader = $this->createKaderUser($villageId, $subdistrictId, $puskesmasId);
        $petugas = $this->createPetugasUser($puskesmasId);

        $material = EducationalMaterial::create([
            'title_material' => 'Edukasi Kepatuhan Minum Obat ' . rand(100, 999),
            'description'    => 'Pentingnya minum obat secara teratur',
            'material_type'  => 'video',
            'video_url'      => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_publish'     => 1,
            'created_by'     => $petugas->id,
        ]);

        $material->sendPublishNotification();

        // Verifikasi notifikasi masih unread
        $unreadNotif = $kader->unreadNotifications()->where('data->education_id', $material->id)->first();
        $this->assertNotNull($unreadNotif);

        // Belum tercatat di database read status
        $this->assertFalse(EducationalMaterialRead::where('user_id', $kader->id)->where('educational_material_id', $material->id)->exists());

        // Kader membuka detail materi edukasi
        $resDetail = $this->actingAs($kader, 'sanctum')->getJson("/api/education/{$material->id}/show");
        $resDetail->assertStatus(200);
        $resDetail->assertJsonPath('data.is_read', true);

        // 1. Status baca materi tersimpan di backend database
        $this->assertTrue(EducationalMaterialRead::where('user_id', $kader->id)->where('educational_material_id', $material->id)->exists());

        // 2. Notifikasi terkait materi edukasi otomatis ditandai sebagai sudah dibaca
        $unreadNotif->refresh();
        $this->assertNotNull($unreadNotif->read_at);

        // 3. Daftar materi edukasi mengembalikan flag is_read = true
        $resList = $this->actingAs($kader, 'sanctum')->getJson('/api/education');
        $resList->assertStatus(200);
        $found = collect($resList->json('data'))->firstWhere('id', $material->id);
        $this->assertNotNull($found);
        $this->assertTrue($found['is_read']);
    }

    public function test_kader_cannot_access_draft_education_or_perform_crud()
    {
        [$villageId, $subdistrictId, $puskesmasId] = $this->getOrCreateTerritory();
        $kader = $this->createKaderUser($villageId, $subdistrictId, $puskesmasId);
        $petugas = $this->createPetugasUser($puskesmasId);

        $draftMaterial = EducationalMaterial::create([
            'title_material' => 'Draft Rahasia Petugas ' . rand(100, 999),
            'description'    => 'Konten draft belum siap dipublikasi',
            'material_type'  => 'image',
            'is_publish'     => 0,
            'created_by'     => $petugas->id,
        ]);

        // 1. Kader mengakses list draft -> 403 Forbidden
        $resDraftList = $this->actingAs($kader, 'sanctum')->getJson('/api/education?status=draft');
        $resDraftList->assertStatus(403);

        // 2. Kader mengakses detail materi draft -> 403 Forbidden
        $resDraftDetail = $this->actingAs($kader, 'sanctum')->getJson("/api/education/{$draftMaterial->id}/show");
        $resDraftDetail->assertStatus(403);

        // 3. Kader mencoba create materi edukasi -> 403 Forbidden
        $resCreate = $this->actingAs($kader, 'sanctum')->postJson('/api/education/store', [
            'title_material' => 'Materi Palsu dari Kader',
            'material_type'  => 'video',
            'video_url'      => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_publish'     => 1,
        ]);
        $resCreate->assertStatus(403);

        // 4. Kader mencoba update materi edukasi -> 403 Forbidden
        $resUpdate = $this->actingAs($kader, 'sanctum')->postJson('/api/education/store', [
            'id'             => $draftMaterial->id,
            'title_material' => 'Manipulasi Judul oleh Kader',
            'material_type'  => 'image',
        ]);
        $resUpdate->assertStatus(403);

        // 5. Kader mencoba toggle publish -> 403 Forbidden
        $resPublish = $this->actingAs($kader, 'sanctum')->putJson("/api/education/{$draftMaterial->id}/publish");
        $resPublish->assertStatus(403);

        // 6. Kader mencoba delete materi -> 403 Forbidden
        $resDelete = $this->actingAs($kader, 'sanctum')->deleteJson("/api/education/{$draftMaterial->id}/delete");
        $resDelete->assertStatus(403);
    }

    public function test_patient_medication_confirmation_creates_notification_for_assigned_kader()
    {
        [$villageId, $subdistrictId, $puskesmasId] = $this->getOrCreateTerritory();
        $kader = $this->createKaderUser($villageId, $subdistrictId, $puskesmasId);
        [$patientUser, $patient, $treatment] = $this->createPatientUser($villageId, $puskesmasId);

        // Pasien upload bukti minum obat hari ini
        $testImage = UploadedFile::fake()->image('bukti_obat.jpg', 600, 600);

        $resSubmit = $this->actingAs($patientUser, 'sanctum')->postJson('/api/treatments/proof', [
            'patient_treatment_id' => $treatment->id,
            'photo'                => $testImage,
            'notes'                => 'Minum obat tepat waktu pagi ini.',
        ]);
        $resSubmit->assertStatus(201);

        // Kader binaan menerima notifikasi konfirmasi minum obat
        $kaderNotifs = $kader->fresh()->notifications;
        $medNotif = $kaderNotifs->first(function ($n) {
            return ($n->data['type'] ?? '') === 'medication_logged';
        });

        $this->assertNotNull($medNotif);
        $this->assertEquals($patient->id, $medNotif->data['patient_id']);
        $this->assertStringContainsString('telah mencatat minum obat', $medNotif->data['message']);
    }
}
