<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\District;
use App\Models\KaderArea;
use App\Models\Message;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\Province;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LiveChatAndReminderTest extends TestCase
{
    use DatabaseTransactions;

    protected $province;
    protected $district;
    protected $subdistrict;
    protected $village1;
    protected $village2;
    protected $puskesmas1;
    protected $puskesmas2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subdistrict = Subdistrict::first();
        $this->district    = $this->subdistrict ? $this->subdistrict->district : District::first();
        $this->province    = $this->district ? $this->district->province : Province::first();

        $villages = Village::take(2)->get();
        $this->village1 = $villages[0] ?? Village::first();
        $this->village2 = $villages[1] ?? Village::orderBy('id', 'desc')->first();

        $puskesmasList = Puskesmas::take(2)->get();
        $this->puskesmas1 = $puskesmasList[0] ?? Puskesmas::first();
        $this->puskesmas2 = $puskesmasList[1] ?? Puskesmas::orderBy('id', 'desc')->first();
    }

    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name'              => 'Test User ' . uniqid(),
            'username'          => 'user_' . uniqid(),
            'email'             => 'user_' . uniqid() . '@example.com',
            'password'          => bcrypt('password123'),
            'user_type_id'      => 2,
            'is_active'         => true,
            'email_verified_at' => now(),
        ], $attributes));
    }

    protected function createPatient(User $user, array $attrs = []): Patient
    {
        return Patient::create(array_merge([
            'user_id'              => $user->id,
            'puskesmas_id'         => $this->puskesmas1->id,
            'subdistrict_id'       => $this->subdistrict->id,
            'village_id'           => $this->village1->id,
            'nik'                  => '3201' . rand(1000000000, 9999999999),
            'address'              => 'Jl. Sukamulya No. ' . rand(1, 100),
            'treatment_start_date' => now()->toDateString(),
        ], $attrs));
    }

    protected function createOfficer(User $user, int $officerTypeId, array $attrs = []): Officer
    {
        return Officer::create(array_merge([
            'user_id'         => $user->id,
            'officer_type_id' => $officerTypeId,
            'puskesmas_id'    => $this->puskesmas1->id,
            'district_id'     => $this->district->id,
        ], $attrs));
    }

    /**
     * Test 1: Pasien can get eligible recipients strictly matching territory (Kader in same village, PJTB in same puskesmas).
     */
    public function test_pasien_can_only_see_eligible_kader_in_village_and_pjtb_in_puskesmas()
    {
        // Patient in village 1 and puskesmas 1
        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        // Kader 1: assigned to village 1 (Eligible)
        $kaderUser1 = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer1 = $this->createOfficer($kaderUser1, 4, ['puskesmas_id' => $this->puskesmas1->id]);
        KaderArea::create([
            'officer_id'     => $kaderOfficer1->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        // Kader 2: assigned to village 2 (Ineligible)
        $kaderUser2 = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer2 = $this->createOfficer($kaderUser2, 4, ['puskesmas_id' => $this->puskesmas1->id]);
        KaderArea::create([
            'officer_id'     => $kaderOfficer2->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village2->id,
        ]);

        // PJTB 1: at puskesmas 1 (Eligible)
        $pjtbUser1 = $this->createUser(['user_type_id' => 3]);
        $this->createOfficer($pjtbUser1, 3, ['puskesmas_id' => $this->puskesmas1->id]);

        // PJTB 2: at puskesmas 2 (Ineligible)
        $pjtbUser2 = $this->createUser(['user_type_id' => 3]);
        $this->createOfficer($pjtbUser2, 3, ['puskesmas_id' => $this->puskesmas2->id]);

        $response = $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/conversations/recipients');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($kaderUser1->id, $ids);
        $this->assertContains($pjtbUser1->id, $ids);
        $this->assertNotContains($kaderUser2->id, $ids);
        $this->assertNotContains($pjtbUser2->id, $ids);
    }

    /**
     * Test 2: Starting a conversation enforces territory authorization.
     */
    public function test_starting_conversation_enforces_territory()
    {
        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        // Kader in other village
        $outsideKader = $this->createUser(['user_type_id' => 3]);
        $outsideOfficer = $this->createOfficer($outsideKader, 4);
        KaderArea::create([
            'officer_id'     => $outsideOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village2->id,
        ]);

        // Attempt to start conversation with outside Kader -> must be 403 Forbidden
        $response = $this->actingAs($patientUser, 'sanctum')
            ->postJson('/api/conversations', ['recipient_id' => $outsideKader->id]);

        $response->assertStatus(403);

        // Kader in same village
        $localKader = $this->createUser(['user_type_id' => 3]);
        $localOfficer = $this->createOfficer($localKader, 4);
        KaderArea::create([
            'officer_id'     => $localOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        // Attempt with local Kader -> 200 OK
        $responseSuccess = $this->actingAs($patientUser, 'sanctum')
            ->postJson('/api/conversations', ['recipient_id' => $localKader->id]);

        $responseSuccess->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'other_user']]);

        // Pastikan memilih petugas mengembalikan id 0 dan belum membuat data di tabel conversations
        $this->assertEquals(0, $responseSuccess->json('data.id'));
        $this->assertDatabaseMissing('conversations', [
            'participant_one_id' => min($patientUser->id, $localKader->id),
            'participant_two_id' => max($patientUser->id, $localKader->id),
        ]);
    }

    /**
     * Test 3: Sending text and image messages in a conversation.
     */
    public function test_sending_text_and_image_messages()
    {
        Storage::fake('public');

        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        $kaderUser = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer = $this->createOfficer($kaderUser, 4);
        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        $conversation = Conversation::getOrCreateBetween($patientUser, $kaderUser);

        // 1. Send text message
        $textRes = $this->actingAs($patientUser, 'sanctum')
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'message' => 'Halo Bu Kader, obat saya sisa 2 butir.',
            ]);

        $textRes->assertStatus(201)
            ->assertJsonPath('data.message', 'Halo Bu Kader, obat saya sisa 2 butir.')
            ->assertJsonPath('data.is_sender', true)
            ->assertJsonPath('data.message_type', 'text');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id'       => $patientUser->id,
            'message'         => 'Halo Bu Kader, obat saya sisa 2 butir.',
        ]);

        // 2. Send image message
        $imageFile = UploadedFile::fake()->image('sputum_result.jpg', 600, 600);
        $imageRes = $this->actingAs($patientUser, 'sanctum')
            ->post("/api/conversations/{$conversation->id}/messages", [
                'message' => 'Foto hasil lab',
                'image'   => $imageFile,
            ]);

        $imageRes->assertStatus(201)
            ->assertJsonPath('data.message_type', 'image');

        $this->assertNotNull($imageRes->json('data.attachment_url'));
    }

    /**
     * Test 4: Reject non-image attachments and files over 5MB.
     */
    public function test_reject_non_image_and_oversized_files()
    {
        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        $kaderUser = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer = $this->createOfficer($kaderUser, 4);
        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        $conversation = Conversation::getOrCreateBetween($patientUser, $kaderUser);

        // PDF file must be rejected
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $resPdf = $this->actingAs($patientUser, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->post("/api/conversations/{$conversation->id}/messages", [
                'image' => $pdfFile,
            ]);

        $resPdf->assertStatus(422);

        // Oversized image (> 5MB) must be rejected
        $oversizedImage = UploadedFile::fake()->create('huge.png', 6000, 'image/png'); // 6MB
        $resHuge = $this->actingAs($patientUser, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->post("/api/conversations/{$conversation->id}/messages", [
                'image' => $oversizedImage,
            ]);

        $resHuge->assertStatus(422);
    }

    /**
     * Test 5: IDOR security check - Unauthorized user cannot access other conversation.
     */
    public function test_idor_protection_rejects_unauthorized_conversation_access()
    {
        $userA = $this->createUser();
        $userB = $this->createUser();
        $userC = $this->createUser(); // Outside third party

        $conversation = Conversation::getOrCreateBetween($userA, $userB);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Private confidential message',
        ]);

        // User C attempts to view conversation details
        $resShow = $this->actingAs($userC, 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}");
        $resShow->assertStatus(403);

        // User C attempts to read messages
        $resMsgs = $this->actingAs($userC, 'sanctum')
            ->getJson("/api/conversations/{$conversation->id}/messages");
        $resMsgs->assertStatus(403);

        // User C attempts to inject message
        $resSend = $this->actingAs($userC, 'sanctum')
            ->postJson("/api/conversations/{$conversation->id}/messages", [
                'message' => 'Hacked message',
            ]);
        $resSend->assertStatus(403);
    }

    /**
     * Test 6: Mark as read updates unread status properly.
     */
    public function test_mark_conversation_as_read()
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $conversation = Conversation::getOrCreateBetween($userA, $userB);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Hello User B',
            'read_at'         => null,
        ]);

        $this->assertEquals(1, $conversation->unreadCountFor($userB));
        $this->assertEquals(0, $conversation->unreadCountFor($userA));

        // User B marks as read
        $res = $this->actingAs($userB, 'sanctum')
            ->postJson("/api/conversations/{$conversation->id}/read");

        $res->assertStatus(200);
        $this->assertEquals(0, $conversation->unreadCountFor($userB));
        $this->assertNotNull($msg->fresh()->read_at);
    }

    /**
     * Test 7: Bulk medication reminder sent to multiple patients as 1-on-1 private messages.
     */
    public function test_kader_bulk_reminder_sends_individual_private_messages()
    {
        // Kader assigned to village 1
        $kaderUser = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer = $this->createOfficer($kaderUser, 4);
        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        // Patient 1 in village 1
        $patientUser1 = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser1, ['village_id' => $this->village1->id]);

        // Patient 2 in village 1
        $patientUser2 = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser2, ['village_id' => $this->village1->id]);

        // Patient 3 in village 2 (outside Kader's area)
        $patientUser3 = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser3, ['village_id' => $this->village2->id]);

        // Kader checks reminder recipients: should only see patient 1 and 2
        $recipRes = $this->actingAs($kaderUser, 'sanctum')
            ->getJson('/api/reminders/recipients');
        $recipRes->assertStatus(200);
        $recipUserIds = collect($recipRes->json('data'))->pluck('user_id')->all();
        $this->assertContains($patientUser1->id, $recipUserIds);
        $this->assertContains($patientUser2->id, $recipUserIds);
        $this->assertNotContains($patientUser3->id, $recipUserIds);

        // Kader sends bulk reminder
        $reminderText = "Halo, jangan lupa minum obat TBC hari ini sesuai anjuran.";
        $sendRes = $this->actingAs($kaderUser, 'sanctum')
            ->postJson('/api/reminders/bulk-send', [
                'patient_user_ids' => [$patientUser1->id, $patientUser2->id, $patientUser3->id],
                'message'          => $reminderText,
            ]);

        $sendRes->assertStatus(200)
            ->assertJsonPath('success_count', 2)
            ->assertJsonPath('failed_count', 1);

        // Verify private 1-on-1 messages created for patient 1 and patient 2
        $conv1 = Conversation::where(function ($q) use ($kaderUser, $patientUser1) {
            $q->where('participant_one_id', min($kaderUser->id, $patientUser1->id))
              ->where('participant_two_id', max($kaderUser->id, $patientUser1->id));
        })->first();

        $this->assertNotNull($conv1);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv1->id,
            'sender_id'       => $kaderUser->id,
            'message'         => $reminderText,
        ]);

        $conv2 = Conversation::where(function ($q) use ($kaderUser, $patientUser2) {
            $q->where('participant_one_id', min($kaderUser->id, $patientUser2->id))
              ->where('participant_two_id', max($kaderUser->id, $patientUser2->id));
        })->first();

        $this->assertNotNull($conv2);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv2->id,
            'sender_id'       => $kaderUser->id,
            'message'         => $reminderText,
        ]);

        // Patient 3 must NOT have received message from this kader
        $conv3 = Conversation::where(function ($q) use ($kaderUser, $patientUser3) {
            $q->where('participant_one_id', min($kaderUser->id, $patientUser3->id))
              ->where('participant_two_id', max($kaderUser->id, $patientUser3->id));
        })->first();

        $this->assertNull($conv3);
    }

    /**
     * Test 8: Patient is forbidden from accessing bulk reminder endpoints.
     */
    public function test_patient_forbidden_from_bulk_reminders()
    {
        $patientUser = $this->createUser(['user_type_id' => 2]);

        $resRecipients = $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/reminders/recipients');
        $resRecipients->assertStatus(403);

        $resSend = $this->actingAs($patientUser, 'sanctum')
            ->postJson('/api/reminders/bulk-send', [
                'patient_user_ids' => [1, 2],
                'message'          => 'Test',
            ]);
        $resSend->assertStatus(403);
    }

    /**
     * Test 9: Live polling via since_id returns only newer messages.
     */
    public function test_live_polling_by_since_id()
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $conv = Conversation::getOrCreateBetween($userA, $userB);

        $msg1 = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Message 1',
        ]);

        $msg2 = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userB->id,
            'message_type'    => 'text',
            'message'         => 'Message 2',
        ]);

        // Poll with since_id = msg1->id
        $res = $this->actingAs($userA, 'sanctum')
            ->getJson("/api/conversations/{$conv->id}/messages?since_id={$msg1->id}");

        $res->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $msg2->id)
            ->assertJsonPath('data.0.message', 'Message 2');
    }

    /**
     * Test 10: Memilih petugas atau membuka chat tanpa mengirim pesan tidak memunculkan percakapan di daftar konsultasi.
     */
    public function test_selecting_recipient_does_not_appear_in_conversations_list()
    {
        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        $kaderUser = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer = $this->createOfficer($kaderUser, 4);
        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        // 1. Pasien memilih kader
        $selectRes = $this->actingAs($patientUser, 'sanctum')
            ->postJson('/api/conversations', ['recipient_id' => $kaderUser->id]);
        $selectRes->assertStatus(200);
        $this->assertEquals(0, $selectRes->json('data.id'));

        // 2. Pasien membatalkan dan kembali ke daftar konsultasi
        $listRes = $this->actingAs($patientUser, 'sanctum')
            ->getJson('/api/conversations');
        $listRes->assertStatus(200);

        // Tidak boleh ada percakapan dengan kader yang muncul di daftar
        $otherUserIds = collect($listRes->json('data'))->pluck('other_user.id')->all();
        $this->assertNotContains($kaderUser->id, $otherUserIds);

        // Di database, tidak boleh ada percakapan antara pasien dan kader
        $this->assertDatabaseMissing('conversations', [
            'participant_one_id' => min($patientUser->id, $kaderUser->id),
            'participant_two_id' => max($patientUser->id, $kaderUser->id),
        ]);
    }

    /**
     * Test 11: Mengirim pesan pertama mengaktifkan percakapan dan memunculkannya di daftar kedua pihak.
     */
    public function test_sending_first_message_activates_conversation_and_appears_in_list()
    {
        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        $kaderUser = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer = $this->createOfficer($kaderUser, 4);
        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        // Pasien kirim pesan pertama ke kader via endpoint /api/conversations/0/messages dengan recipient_id
        $sendRes = $this->actingAs($patientUser, 'sanctum')
            ->postJson('/api/conversations/0/messages', [
                'recipient_id' => $kaderUser->id,
                'message'      => 'Halo Bu Kader, saya pasien baru.',
            ]);

        $sendRes->assertStatus(201)
            ->assertJsonPath('data.message', 'Halo Bu Kader, saya pasien baru.')
            ->assertJsonPath('data.is_sender', true);

        $newConvId = $sendRes->json('data.conversation_id');
        $this->assertGreaterThan(0, $newConvId);

        // Sekarang percakapan muncul di daftar konsultasi Pasien
        $listPatient = $this->actingAs($patientUser, 'sanctum')->getJson('/api/conversations');
        $listPatient->assertStatus(200);
        $patientConvIds = collect($listPatient->json('data'))->pluck('id')->all();
        $this->assertContains($newConvId, $patientConvIds);

        // Sekarang percakapan juga muncul di daftar konsultasi Kader
        $listKader = $this->actingAs($kaderUser, 'sanctum')->getJson('/api/conversations');
        $listKader->assertStatus(200);
        $kaderConvIds = collect($listKader->json('data'))->pluck('id')->all();
        $this->assertContains($newConvId, $kaderConvIds);

        // Pesan kedua dikirim pada percakapan yang sama ($id > 0)
        $secondMsgRes = $this->actingAs($kaderUser, 'sanctum')
            ->postJson("/api/conversations/{$newConvId}/messages", [
                'message' => 'Halo, ada yang bisa saya bantu?',
            ]);
        $secondMsgRes->assertStatus(201);

        // Pastikan tidak ada duplikasi percakapan di database
        $totalConvs = Conversation::between($patientUser->id, $kaderUser->id)->count();
        $this->assertEquals(1, $totalConvs);
    }

    /**
     * Test 12: Pengiriman pesan pertama yang gagal validasi tidak membuat percakapan di database.
     */
    public function test_failed_first_message_does_not_create_conversation()
    {
        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        $kaderUser = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer = $this->createOfficer($kaderUser, 4);
        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        // Coba kirim pesan kosong tanpa teks dan tanpa gambar (gagal validasi 422)
        $sendRes = $this->actingAs($patientUser, 'sanctum')
            ->withHeader('Accept', 'application/json')
            ->postJson('/api/conversations/0/messages', [
                'recipient_id' => $kaderUser->id,
                'message'      => '',
            ]);

        $sendRes->assertStatus(422);

        // Database tetap bersih tanpa record percakapan
        $this->assertDatabaseMissing('conversations', [
            'participant_one_id' => min($patientUser->id, $kaderUser->id),
            'participant_two_id' => max($patientUser->id, $kaderUser->id),
        ]);
    }

    /**
     * Test 13: Percakapan kosong (jika ada di database) disembunyikan dari index dan digunakan kembali saat pesan dikirim.
     */
    public function test_empty_conversation_in_db_is_hidden_from_index_and_reused_on_first_message()
    {
        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        $kaderUser = $this->createUser(['user_type_id' => 3]);
        $kaderOfficer = $this->createOfficer($kaderUser, 4);
        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village1->id,
        ]);

        // Simulasikan record percakapan yang belum memiliki pesan di tabel messages
        $emptyConv = Conversation::create([
            'participant_one_id' => min($patientUser->id, $kaderUser->id),
            'participant_two_id' => max($patientUser->id, $kaderUser->id),
            'last_message_id'    => null,
            'last_message_at'    => null,
        ]);

        // Pastikan TIDAK muncul di daftar konsultasi (/api/conversations)
        $listRes = $this->actingAs($patientUser, 'sanctum')->getJson('/api/conversations');
        $listRes->assertStatus(200);
        $ids = collect($listRes->json('data'))->pluck('id')->all();
        $this->assertNotContains($emptyConv->id, $ids);

        // Saat pengguna mengirim pesan pertama, record percakapan ini digunakan kembali
        $sendRes = $this->actingAs($patientUser, 'sanctum')
            ->postJson('/api/conversations/0/messages', [
                'recipient_id' => $kaderUser->id,
                'message'      => 'Pesan pertama mengaktifkan percakapan lama.',
            ]);

        $sendRes->assertStatus(201);
        $this->assertEquals($emptyConv->id, $sendRes->json('data.conversation_id'));

        // Sekarang percakapan muncul di daftar
        $listRes2 = $this->actingAs($patientUser, 'sanctum')->getJson('/api/conversations');
        $ids2 = collect($listRes2->json('data'))->pluck('id')->all();
        $this->assertContains($emptyConv->id, $ids2);
    }

    /**
     * Test 14: Officer list is merged into one list and sorted alphabetically A-Z by full name with proper subtitles.
     */
    public function test_officer_list_is_sorted_az_and_subtitles_contain_district()
    {
        $patientUser = $this->createUser(['user_type_id' => 2]);
        $this->createPatient($patientUser, [
            'village_id'   => $this->village1->id,
            'puskesmas_id' => $this->puskesmas1->id,
        ]);

        // Create officers with specific alphabetical names
        $kaderZ = $this->createUser(['name' => 'Zahra Kader', 'user_type_id' => 3]);
        $offZ = $this->createOfficer($kaderZ, 4, ['puskesmas_id' => $this->puskesmas1->id]);
        KaderArea::create([
            'officer_id'     => $offZ->id,
            'village_id'     => $this->village1->id,
            'subdistrict_id' => $this->subdistrict->id,
        ]);

        $pjtbA = $this->createUser(['name' => 'Agus PJTB', 'user_type_id' => 3]);
        $this->createOfficer($pjtbA, 3, ['puskesmas_id' => $this->puskesmas1->id]);

        $kaderM = $this->createUser(['name' => 'Maya Kader', 'user_type_id' => 3]);
        $offM = $this->createOfficer($kaderM, 4, ['puskesmas_id' => $this->puskesmas1->id]);
        KaderArea::create([
            'officer_id'     => $offM->id,
            'village_id'     => $this->village1->id,
            'subdistrict_id' => $this->subdistrict->id,
        ]);

        $res = $this->actingAs($patientUser, 'sanctum')->getJson('/api/conversations/recipients');
        $res->assertStatus(200);

        $data = collect($res->json('data'));
        $names = $data->pluck('name')->all();

        // Ensure A-Z sorted
        $sortedNames = $names;
        natcasesort($sortedNames);
        $this->assertEquals(array_values($sortedNames), array_values($names));

        // Subtitle check for Kader & PJTB
        $kaderItem = $data->firstWhere('name', 'Zahra Kader');
        $this->assertNotNull($kaderItem);
        $this->assertStringContainsString('Kader', $kaderItem['sub_info']);

        $pjtbItem = $data->firstWhere('name', 'Agus PJTB');
        $this->assertNotNull($pjtbItem);
        $this->assertStringContainsString('PJTB', $pjtbItem['sub_info']);
    }

    /**
     * Test 15: Sender can edit own message.
     */
    public function test_sender_can_edit_own_message()
    {
        $userA = $this->createUser(['user_type_id' => 2]);
        $userB = $this->createUser(['user_type_id' => 3]);
        $conv = Conversation::create([
            'participant_one_id' => min($userA->id, $userB->id),
            'participant_two_id' => max($userA->id, $userB->id),
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Pesan awal sebelum diedit',
        ]);
        $conv->update(['last_message_id' => $msg->id, 'last_message_at' => now()]);

        // Edit message
        $res = $this->actingAs($userA, 'sanctum')
            ->putJson("/api/messages/{$msg->id}", [
                'message' => 'Pesan sudah diperbarui dengan baik',
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.message', 'Pesan sudah diperbarui dengan baik')
            ->assertJsonPath('data.is_edited', true);

        $msg->refresh();
        $this->assertEquals('Pesan sudah diperbarui dengan baik', $msg->message);
        $this->assertNotNull($msg->edited_at);
    }

    /**
     * Test 16: Non-sender cannot edit message (Forbidden 403).
     */
    public function test_non_sender_cannot_edit_message()
    {
        $userA = $this->createUser(['user_type_id' => 2]);
        $userB = $this->createUser(['user_type_id' => 3]);
        $conv = Conversation::create([
            'participant_one_id' => min($userA->id, $userB->id),
            'participant_two_id' => max($userA->id, $userB->id),
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Pesan milik User A',
        ]);

        // User B attempts to edit User A's message
        $res = $this->actingAs($userB, 'sanctum')
            ->putJson("/api/messages/{$msg->id}", [
                'message' => 'User B membajak pesan ini',
            ]);

        $res->assertStatus(403);

        $msg->refresh();
        $this->assertEquals('Pesan milik User A', $msg->message);
        $this->assertNull($msg->edited_at);
    }

    /**
     * Test 17: Deleted message cannot be edited.
     */
    public function test_deleted_message_cannot_be_edited()
    {
        $userA = $this->createUser(['user_type_id' => 2]);
        $userB = $this->createUser(['user_type_id' => 3]);
        $conv = Conversation::create([
            'participant_one_id' => min($userA->id, $userB->id),
            'participant_two_id' => max($userA->id, $userB->id),
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Pesan ini dihapus',
            'deleted_at'      => now(),
            'deleted_by'      => $userA->id,
        ]);

        $res = $this->actingAs($userA, 'sanctum')
            ->putJson("/api/messages/{$msg->id}", [
                'message' => 'Mencoba mengedit pesan terhapus',
            ]);

        $res->assertStatus(422);
    }

    /**
     * Test 18: Sender can soft delete own message and recipient sees masked message.
     */
    public function test_sender_can_soft_delete_own_message()
    {
        $userA = $this->createUser(['user_type_id' => 2]);
        $userB = $this->createUser(['user_type_id' => 3]);
        $conv = Conversation::create([
            'participant_one_id' => min($userA->id, $userB->id),
            'participant_two_id' => max($userA->id, $userB->id),
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Pesan rahasia yang akan dihapus',
        ]);
        $conv->update(['last_message_id' => $msg->id, 'last_message_at' => now()]);

        // Delete message
        $res = $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/messages/{$msg->id}");

        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $msg->refresh();
        $this->assertNotNull($msg->deleted_at);
        $this->assertEquals($userA->id, $msg->deleted_by);

        // Recipient fetches messages: sees "Pesan ini telah dihapus"
        $getRes = $this->actingAs($userB, 'sanctum')
            ->getJson("/api/conversations/{$conv->id}/messages");

        $getRes->assertStatus(200)
            ->assertJsonPath('data.0.message', 'Pesan ini telah dihapus')
            ->assertJsonPath('data.0.is_deleted', true);
    }

    /**
     * Test 19: Non-sender cannot delete message.
     */
    public function test_non_sender_cannot_delete_message()
    {
        $userA = $this->createUser(['user_type_id' => 2]);
        $userB = $this->createUser(['user_type_id' => 3]);
        $conv = Conversation::create([
            'participant_one_id' => min($userA->id, $userB->id),
            'participant_two_id' => max($userA->id, $userB->id),
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Pesan milik User A',
        ]);

        $res = $this->actingAs($userB, 'sanctum')
            ->deleteJson("/api/messages/{$msg->id}");

        $res->assertStatus(403);
        $msg->refresh();
        $this->assertNull($msg->deleted_at);
    }

    /**
     * Test 20: Deleting last message updates conversation pointer and all-deleted hides conversation from index.
     */
    public function test_deleting_last_message_updates_conversation_last_message_or_hides_conversation()
    {
        $userA = $this->createUser(['user_type_id' => 2]);
        $userB = $this->createUser(['user_type_id' => 3]);
        $conv = Conversation::create([
            'participant_one_id' => min($userA->id, $userB->id),
            'participant_two_id' => max($userA->id, $userB->id),
        ]);

        $msg1 = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Message 1',
        ]);

        $msg2 = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $userA->id,
            'message_type'    => 'text',
            'message'         => 'Message 2 (last)',
        ]);

        $conv->update(['last_message_id' => $msg2->id, 'last_message_at' => now()]);

        // Delete msg2
        $this->actingAs($userA, 'sanctum')->deleteJson("/api/messages/{$msg2->id}")->assertStatus(200);

        // Conv last_message_id should now point to msg1
        $conv->refresh();
        $this->assertEquals($msg1->id, $conv->last_message_id);

        // Delete msg1 (now all messages are deleted)
        $this->actingAs($userA, 'sanctum')->deleteJson("/api/messages/{$msg1->id}")->assertStatus(200);

        $conv->refresh();
        $this->assertNull($conv->last_message_id);

        // Conversation should be hidden from index
        $indexRes = $this->actingAs($userA, 'sanctum')->getJson('/api/conversations');
        $indexRes->assertStatus(200);
        $ids = collect($indexRes->json('data'))->pluck('id')->all();
        $this->assertNotContains($conv->id, $ids);
    }
}
