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
}
