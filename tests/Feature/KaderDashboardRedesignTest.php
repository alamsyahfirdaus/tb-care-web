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
use Tests\TestCase;

class KaderDashboardRedesignTest extends TestCase
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

    public function test_kader_dashboard_returns_four_statistics_and_sections(): void
    {
        // 1. Setup Master Wilayah
        $province = Province::first() ?? Province::create(['name' => 'Jawa Barat']);
        $district = District::first() ?? District::create(['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::first() ?? Subdistrict::create(['name' => 'Tamansari', 'district_id' => $district->id]);
        $village1 = Village::first() ?? Village::create(['name' => 'Sukahurip', 'subdistrict_id' => $subdistrict->id]);
        $village2 = Village::where('id', '!=', $village1->id)->first() ?? Village::create(['name' => 'Setiawargi', 'subdistrict_id' => $subdistrict->id]);
        $puskesmas = Puskesmas::first() ?? Puskesmas::create(['name' => 'Puskesmas Tamansari', 'subdistrict_id' => $subdistrict->id]);
        $treatmentType = TreatmentType::first() ?? TreatmentType::create(['treatment_type' => 'Kategori 1', 'treatment_duration' => 6, 'duration_unit' => 'month']);

        // 2. Setup Kader User (Officer Type 4: Kader Puskesmas)
        $kaderUser = User::create([
            'user_type_id' => 3,
            'name'         => 'Siti Kader',
            'username'     => 'sitikader_' . uniqid(),
            'email'        => 'siti_' . uniqid() . '@kader.id',
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
            'village_id'     => $village1->id,
            'rw'             => '01',
            'rt'             => '01',
        ]);

        // 3. Pasien 1: Pengobatan berjalan, belum minum obat hari ini, ada jadwal kontrol hari ini
        $u1 = User::create([
            'user_type_id' => 2,
            'name'         => 'anes maulida sukmara',
            'username'     => 'anes_' . uniqid(),
            'email'        => 'anes_' . uniqid() . '@mail.id',
            'password'     => bcrypt('password'),
            'is_active'    => true,
        ]);
        $p1 = Patient::create([
            'user_id'      => $u1->id,
            'puskesmas_id' => $puskesmas->id,
            'village_id'   => $village1->id,
            'nik'          => '3278010001' . rand(10, 99),
        ]);
        $t1 = PatientTreatment::create([
            'patient_id'       => $p1->id,
            'treatment_status' => 'Berjalan',
            'start_date'       => '2026-10-01',
            'treatment_days'   => 180,
            'medication_time'  => '14:30:00',
        ]);
        TreatmentVisit::create([
            'patient_treatment_id' => $t1->id,
            'visit_date'           => '2026-11-08',
            'visit_time'           => '09:00:00',
            'visit_status'         => 'Terjadwal',
            'notes'                => 'Pemeriksaan dahak lanjutan',
        ]);

        // 4. Pasien 2: Pengobatan berjalan, SUDAH minum obat hari ini (ada medication record Diminum)
        $u2 = User::create([
            'user_type_id' => 2,
            'name'         => 'dede sumiati',
            'username'     => 'dede_' . uniqid(),
            'email'        => 'dede_' . uniqid() . '@mail.id',
            'password'     => bcrypt('password'),
            'is_active'    => true,
        ]);
        $p2 = Patient::create([
            'user_id'      => $u2->id,
            'puskesmas_id' => $puskesmas->id,
            'village_id'   => $village1->id,
            'nik'          => '3278010002' . rand(10, 99),
        ]);
        $t2 = PatientTreatment::create([
            'patient_id'       => $p2->id,
            'treatment_status' => 'Berjalan',
            'start_date'       => '2026-10-01',
            'treatment_days'   => 180,
            'medication_time'  => '07:00:00',
        ]);
        MedicationRecord::create([
            'patient_treatment_id' => $t2->id,
            'status'               => 'Diminum',
            'is_verified'          => 1,
            'created_at'           => '2026-11-08 07:05:00',
        ]);

        // 5. Pasien 3: Di desa berbeda (Setiawargi), tidak boleh masuk dashboard Kader ini
        $u3 = User::create([
            'user_type_id' => 2,
            'name'         => 'budi luar wilayah',
            'username'     => 'budi_' . uniqid(),
            'email'        => 'budi_' . uniqid() . '@mail.id',
            'password'     => bcrypt('password'),
            'is_active'    => true,
        ]);
        $p3 = Patient::create([
            'user_id'      => $u3->id,
            'puskesmas_id' => $puskesmas->id,
            'village_id'   => $village2->id, // Desa 2
            'nik'          => '3278010003' . rand(10, 99),
        ]);
        PatientTreatment::create([
            'patient_id'       => $p3->id,
            'treatment_status' => 'Berjalan',
            'start_date'       => '2026-10-01',
            'treatment_days'   => 180,
            'medication_time'  => '08:00:00',
        ]);

        // Request API Dashboard
        $response = $this->actingAs($kaderUser)->getJson('/api/kader/dashboard');

        $response->assertStatus(200);
        $data = $response->json();

        // Verifikasi 4 Statistik:
        // Total Pasien = 2 (p1 dan p2, p3 di luar desa binaan)
        $this->assertEquals(2, $data['statistics']['total_patients']);
        // Belum Minum Obat = 1 (p1)
        $this->assertEquals(1, $data['statistics']['belum_minum_obat']);
        $this->assertEquals(1, $data['statistics']['unconfirmed_today']);
        // Sudah Minum = 1 (p2)
        $this->assertEquals(1, $data['statistics']['sudah_minum']);
        $this->assertEquals(1, $data['statistics']['confirmed_today']);
        // Jadwal Kontrol = 1 (p1)
        $this->assertEquals(1, $data['statistics']['jadwal_kontrol']);
        $this->assertEquals(1, $data['statistics']['control_schedules_today']);

        // Verifikasi List Pasien Belum Minum Obat
        $this->assertCount(1, $data['unconfirmed_patients']);
        $unconfirmed = $data['unconfirmed_patients'][0];
        $this->assertEquals($p1->id, $unconfirmed['id']);
        $this->assertEquals('Anes Maulida Sukmara', $unconfirmed['name']); // Title Case
        $this->assertEquals('14:30', $unconfirmed['medication_time']);
        $this->assertEquals('Belum konfirmasi minum obat', $unconfirmed['status_text']);

        // Verifikasi List Jadwal Kontrol Hari Ini
        $this->assertCount(1, $data['today_control_schedules']);
        $schedule = $data['today_control_schedules'][0];
        $this->assertEquals($p1->id, $schedule['patient_id']);
        $this->assertEquals('Anes Maulida Sukmara', $schedule['patient_name']);
        $this->assertEquals('09:00', $schedule['visit_time']);
        $this->assertEquals('09:00 WIB', $schedule['visit_time_formatted']);
        $this->assertEquals('Puskesmas Tamansari', $schedule['puskesmas_name']);
        $this->assertEquals('Terjadwal', $schedule['visit_status']);
    }

    public function test_cancelled_visits_are_excluded_from_control_schedules(): void
    {
        $province = Province::first() ?? Province::create(['name' => 'Jawa Barat']);
        $district = District::first() ?? District::create(['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::first() ?? Subdistrict::create(['name' => 'Tamansari', 'district_id' => $district->id]);
        $village1 = Village::first() ?? Village::create(['name' => 'Sukahurip', 'subdistrict_id' => $subdistrict->id]);
        $puskesmas = Puskesmas::first() ?? Puskesmas::create(['name' => 'Puskesmas Tamansari', 'subdistrict_id' => $subdistrict->id]);

        $kaderUser = User::create([
            'user_type_id' => 3,
            'name'         => 'Kader Test',
            'username'     => 'kader_' . uniqid(),
            'email'        => 'kader_' . uniqid() . '@kader.id',
            'password'     => bcrypt('password'),
            'is_active'    => true,
        ]);

        $officer = Officer::create([
            'user_id'         => $kaderUser->id,
            'officer_type_id' => 4,
            'puskesmas_id'    => $puskesmas->id,
            'district_id'     => $district->id,
        ]);

        KaderArea::create([
            'officer_id'     => $officer->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $village1->id,
            'rw'             => '01',
            'rt'             => '01',
        ]);

        $u1 = User::create([
            'user_type_id' => 2,
            'name'         => 'Pasien Batal',
            'username'     => 'batal_' . uniqid(),
            'email'        => 'batal_' . uniqid() . '@mail.id',
            'password'     => bcrypt('password'),
            'is_active'    => true,
        ]);
        $p1 = Patient::create([
            'user_id'      => $u1->id,
            'puskesmas_id' => $puskesmas->id,
            'village_id'   => $village1->id,
            'nik'          => '3278010009' . rand(10, 99),
        ]);
        $t1 = PatientTreatment::create([
            'patient_id'       => $p1->id,
            'treatment_status' => 'Berjalan',
            'start_date'       => '2026-10-01',
            'treatment_days'   => 180,
            'medication_time'  => '14:30:00',
        ]);

        // Kunjungan dibatalkan
        TreatmentVisit::create([
            'patient_treatment_id' => $t1->id,
            'visit_date'           => '2026-11-08',
            'visit_time'           => '10:00:00',
            'visit_status'         => 'Dibatalkan',
            'notes'                => 'Pasien berhalangan',
        ]);

        $response = $this->actingAs($kaderUser)->getJson('/api/kader/dashboard');
        $response->assertStatus(200);
        $data = $response->json();

        // Kunjungan dibatalkan TIDAK dihitung dalam jadwal_kontrol
        $this->assertEquals(0, $data['statistics']['jadwal_kontrol']);
        $this->assertCount(0, $data['today_control_schedules']);
    }

    public function test_verifying_medication_updates_dashboard_statistics(): void
    {
        $province = Province::first() ?? Province::create(['name' => 'Jawa Barat']);
        $district = District::first() ?? District::create(['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::first() ?? Subdistrict::create(['name' => 'Tamansari', 'district_id' => $district->id]);
        $village1 = Village::first() ?? Village::create(['name' => 'Sukahurip', 'subdistrict_id' => $subdistrict->id]);
        $puskesmas = Puskesmas::first() ?? Puskesmas::create(['name' => 'Puskesmas Tamansari', 'subdistrict_id' => $subdistrict->id]);

        $kaderUser = User::create([
            'user_type_id' => 3,
            'name'         => 'Kader Test 2',
            'username'     => 'kader2_' . uniqid(),
            'email'        => 'kader2_' . uniqid() . '@kader.id',
            'password'     => bcrypt('password'),
            'is_active'    => true,
        ]);

        $officer = Officer::create([
            'user_id'         => $kaderUser->id,
            'officer_type_id' => 4,
            'puskesmas_id'    => $puskesmas->id,
            'district_id'     => $district->id,
        ]);

        KaderArea::create([
            'officer_id'     => $officer->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $village1->id,
            'rw'             => '01',
            'rt'             => '01',
        ]);

        $u1 = User::create([
            'user_type_id' => 2,
            'name'         => 'Pasien Verifikasi',
            'username'     => 'verif_' . uniqid(),
            'email'        => 'verif_' . uniqid() . '@mail.id',
            'password'     => bcrypt('password'),
            'is_active'    => true,
        ]);
        $p1 = Patient::create([
            'user_id'      => $u1->id,
            'puskesmas_id' => $puskesmas->id,
            'village_id'   => $village1->id,
            'nik'          => '3278010011' . rand(10, 99),
        ]);
        PatientTreatment::create([
            'patient_id'       => $p1->id,
            'treatment_status' => 'Berjalan',
            'start_date'       => '2026-10-01',
            'treatment_days'   => 180,
            'medication_time'  => '08:00:00',
        ]);

        // Sebelum verifikasi: belum minum = 1, sudah minum = 0
        $resp1 = $this->actingAs($kaderUser)->getJson('/api/kader/dashboard');
        $this->assertEquals(1, $resp1->json('statistics.belum_minum_obat'));
        $this->assertEquals(0, $resp1->json('statistics.sudah_minum'));

        // Lakukan verifikasi minum tanpa konfirmasi oleh kader
        $verifyResp = $this->actingAs($kaderUser)->postJson("/api/kader/patients/{$p1->id}/verify-medication", [
            'date'   => '2026-11-08',
            'action' => 'minum_tanpa_konfirmasi',
        ]);
        $verifyResp->assertStatus(200);

        // Setelah verifikasi: belum minum = 0, sudah minum = 1
        $resp2 = $this->actingAs($kaderUser)->getJson('/api/kader/dashboard');
        $this->assertEquals(0, $resp2->json('statistics.belum_minum_obat'));
        $this->assertEquals(1, $resp2->json('statistics.sudah_minum'));
        $this->assertCount(0, $resp2->json('unconfirmed_patients'));
    }
}
