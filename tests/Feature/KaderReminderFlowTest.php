<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\KaderArea;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientReminder;
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
use Tests\TestCase;

class KaderReminderFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-08 08:30:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function setupKaderAndPatient(string $villageName = 'Setiamulya')
    {
        $province = Province::first() ?? Province::create(['name' => 'Jawa Barat']);
        $district = District::first() ?? District::create(['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::first() ?? Subdistrict::create(['name' => 'Tamansari', 'district_id' => $district->id]);
        $village = Village::create(['name' => $villageName . '_' . uniqid(), 'subdistrict_id' => $subdistrict->id]);
        $puskesmas = Puskesmas::first() ?? Puskesmas::create(['name' => 'Puskesmas Tamansari', 'subdistrict_id' => $subdistrict->id]);
        $treatmentType = TreatmentType::first() ?? TreatmentType::create(['treatment_type' => 'Kategori 1', 'treatment_duration' => 6, 'duration_unit' => 'month']);

        $kaderUser = User::create([
            'user_type_id' => 3,
            'name'         => 'Dede Teti',
            'username'     => 'kader_' . uniqid(),
            'email'        => 'kader_' . uniqid() . '@example.com',
            'password'     => bcrypt('password'),
            'is_active'    => true,
        ]);

        $officer = Officer::create([
            'user_id'         => $kaderUser->id,
            'officer_type_id' => 4, // Kader
            'puskesmas_id'    => $puskesmas->id,
            'district_id'     => $district->id,
        ]);

        KaderArea::create([
            'officer_id'     => $officer->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $village->id,
            'rw'             => '001',
            'rt'             => '001',
        ]);

        $patientUser = User::create([
            'user_type_id' => 2,
            'name'         => 'Alamsyah Firdaus',
            'username'     => 'alamsyah_' . uniqid(),
            'email'        => 'alamsyah_' . uniqid() . '@example.com',
            'password'     => bcrypt('password'),
            'phone'        => '0897654321',
            'is_active'    => true,
        ]);

        $patient = Patient::create([
            'user_id'      => $patientUser->id,
            'puskesmas_id' => $puskesmas->id,
            'village_id'   => $village->id,
            'nik'          => '3278053107980010',
            'address'      => 'RT 01 RW 01',
            'rt'           => '001',
            'rw'           => '001',
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'start_date'        => '2026-08-31',
            'end_date'          => '2027-05-05',
            'treatment_status'  => 'Berjalan',
            'medication_time'   => '07:00:00',
            'treatment_days'    => 180,
        ]);

        return [$kaderUser, $patient, $treatment];
    }

    public function test_medication_reminder_succeeds_and_creates_logs_and_notifications(): void
    {
        [$kaderUser, $patient] = $this->setupKaderAndPatient();

        $response = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id' => $patient->id,
            'type'       => 'medication',
            'message'    => 'Pengingat jadwal minum obat TB hari ini pukul 07:00 WIB.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Pengingat minum obat berhasil dikirim kepada pasien.',
        ]);

        $data = $response->json('data');
        $this->assertEquals($patient->id, $data['patient_id']);
        $this->assertEquals('medication', $data['type']);
        $this->assertNotEmpty($data['delivery_status']);

        // Pastikan PatientReminder tercatat
        $this->assertDatabaseHas('patient_reminders', [
            'sender_id'  => $kaderUser->id,
            'patient_id' => $patient->id,
            'type'       => 'medication',
        ]);

        // Pastikan In-App Notification untuk pasien tercipta
        $this->assertDatabaseHas('notifications', [
            'notifiable_id'   => $patient->user_id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_medication_reminder_rate_limiting_debounce(): void
    {
        [$kaderUser, $patient] = $this->setupKaderAndPatient();

        // Kirim pertama kali
        $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id' => $patient->id,
            'type'       => 'medication',
            'message'    => 'Pengingat pertama',
        ])->assertStatus(200);

        // Kirim segera dalam 30 detik (debounce double-click)
        $res2 = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id' => $patient->id,
            'type'       => 'medication',
            'message'    => 'Pengingat kedua instan',
        ]);

        $res2->assertStatus(429);
        $res2->assertJson([
            'success' => false,
            'code'    => 'RATE_LIMITED',
        ]);
    }

    public function test_visit_reminder_succeeds_when_patient_has_valid_visit(): void
    {
        [$kaderUser, $patient, $treatment] = $this->setupKaderAndPatient();

        // Buat jadwal kontrol valid untuk hari ini atau mendatang
        $visit = TreatmentVisit::create([
            'patient_treatment_id' => $treatment->id,
            'visit_date'           => '2026-11-08',
            'visit_time'           => '15:30:00',
            'visit_status'         => 'Terjadwal',
        ]);

        $response = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id'  => $patient->id,
            'type'        => 'visit',
            'schedule_id' => $visit->id,
            'message'     => 'Pengingat jadwal kontrol Minggu, 8 November 2026 Jam 15.30 WIB.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Pengingat kontrol berhasil dikirim kepada pasien.',
        ]);
    }

    public function test_visit_reminder_can_be_sent_repeatedly_without_cooldown(): void
    {
        [$kaderUser, $patient, $treatment] = $this->setupKaderAndPatient();

        TreatmentVisit::create([
            'patient_treatment_id' => $treatment->id,
            'visit_date'           => '2026-11-12',
            'visit_time'           => '09:00:00',
            'visit_status'         => 'Terjadwal',
        ]);

        // Pengiriman 1
        $res1 = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id' => $patient->id,
            'type'       => 'visit',
            'message'    => 'Pengingat kontrol pertama.',
        ]);
        $res1->assertStatus(200);

        // Pengiriman 2 segera setelahnya (Kader boleh mengirim pengingat kontrol berulang kali tanpa batas)
        $res2 = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id' => $patient->id,
            'type'       => 'visit',
            'message'    => 'Pengingat kontrol kedua.',
        ]);
        $res2->assertStatus(200);
        $res2->assertJson([
            'success' => true,
            'message' => 'Pengingat kontrol berhasil dikirim kepada pasien.',
        ]);
    }

    public function test_visit_reminder_rejected_if_patient_has_no_visit_schedule(): void
    {
        [$kaderUser, $patient] = $this->setupKaderAndPatient();

        // Pastikan tidak ada TreatmentVisit
        TreatmentVisit::whereHas('patientTreatment', function ($q) use ($patient) {
            $q->where('patient_id', $patient->id);
        })->delete();

        $response = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id' => $patient->id,
            'type'       => 'visit',
            'message'    => 'Pengingat kontrol tapi tidak ada jadwal.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Pasien tidak memiliki jadwal kontrol.',
        ]);
    }

    public function test_visit_reminder_rejected_if_visit_is_cancelled(): void
    {
        [$kaderUser, $patient, $treatment] = $this->setupKaderAndPatient();

        // Buat jadwal kontrol berstatus Dibatalkan
        $cancelledVisit = TreatmentVisit::create([
            'patient_treatment_id' => $treatment->id,
            'visit_date'           => '2026-11-08',
            'visit_time'           => '15:30:00',
            'visit_status'         => 'Dibatalkan',
        ]);

        $response = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id'  => $patient->id,
            'type'        => 'visit',
            'schedule_id' => $cancelledVisit->id,
            'message'     => 'Pengingat kontrol untuk jadwal batal.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Jadwal kontrol telah dibatalkan.',
        ]);
    }

    public function test_visit_reminder_rejected_if_schedule_id_not_found(): void
    {
        [$kaderUser, $patient] = $this->setupKaderAndPatient();

        $response = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id'  => $patient->id,
            'type'        => 'visit',
            'schedule_id' => 999999, // ID tidak ada
            'message'     => 'Pengingat dengan ID tidak valid.',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Jadwal kontrol yang dipilih tidak ditemukan untuk pasien ini.',
        ]);
    }

    public function test_kader_cannot_send_reminder_to_patient_outside_territory(): void
    {
        [$kaderUser] = $this->setupKaderAndPatient('DesaKader');
        // Buat pasien di desa lain
        [, $outsidePatient] = $this->setupKaderAndPatient('DesaLain');

        $response = $this->actingAs($kaderUser, 'sanctum')->postJson('/api/kader/reminders/send', [
            'patient_id' => $outsidePatient->id,
            'type'       => 'medication',
            'message'    => 'Pengingat ke pasien luar wilayah.',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
        ]);
    }
}
