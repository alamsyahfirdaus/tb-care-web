<?php

namespace Tests\Feature;

use App\Models\District;
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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PetugasPatientMenuApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_petugas_patient_index_computes_real_reminder_and_progress_fields(): void
    {
        $province = Province::first() ?? Province::create(['name' => 'Provinsi Petugas Test']);
        $district = District::where('province_id', $province->id)->first()
            ?? District::create(['name' => 'Kabupaten Petugas Test', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::where('district_id', $district->id)->first()
            ?? Subdistrict::create(['name' => 'Kecamatan Petugas Test', 'district_id' => $district->id]);
        $village = Village::where('subdistrict_id', $subdistrict->id)->first()
            ?? Village::create(['name' => 'Desa Petugas Test', 'subdistrict_id' => $subdistrict->id]);
        $puskesmas = Puskesmas::create([
            'name'           => 'Puskesmas Khusus Menu ' . uniqid(),
            'subdistrict_id' => $subdistrict->id,
        ]);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        // PJTB Officer scoped strictly to this new Puskesmas
        $pjtbUser = User::create([
            'name'         => 'PJTB Tester',
            'username'     => 'pjtb_menu_' . uniqid(),
            'email'        => 'pjtb_menu_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
        ]);
        Officer::create([
            'user_id'         => $pjtbUser->id,
            'officer_type_id' => 3,
            'puskesmas_id'    => $puskesmas->id,
        ]);

        // Patient 1: Active treatment, NOT taken today -> needs_reminder = true
        $u1 = User::create([
            'name'         => 'Pasien Belum Minum',
            'username'     => 'p1_' . uniqid(),
            'email'        => 'p1_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
        ]);
        $p1 = Patient::create([
            'user_id'              => $u1->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'subdistrict_id'       => $subdistrict->id,
            'village_id'           => $village->id,
            'treatment_start_date' => Carbon::today('Asia/Jakarta')->subDays(4)->format('Y-m-d'),
        ]);
        PatientTreatment::create([
            'patient_id'        => $p1->id,
            'treatment_type_id' => $treatmentType->id,
            'start_date'        => Carbon::today('Asia/Jakarta')->subDays(4)->format('Y-m-d'),
            'end_date'          => Carbon::today('Asia/Jakarta')->addDays(176)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Berjalan',
        ]);

        // Patient 2: Active treatment, taken today, NO imminent visit -> needs_reminder = false
        $u2 = User::create([
            'name'         => 'Pasien Sudah Minum',
            'username'     => 'p2_' . uniqid(),
            'email'        => 'p2_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
        ]);
        $p2 = Patient::create([
            'user_id'              => $u2->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'subdistrict_id'       => $subdistrict->id,
            'village_id'           => $village->id,
            'treatment_start_date' => Carbon::today('Asia/Jakarta')->subDays(9)->format('Y-m-d'),
        ]);
        $tr2 = PatientTreatment::create([
            'patient_id'        => $p2->id,
            'treatment_type_id' => $treatmentType->id,
            'start_date'        => Carbon::today('Asia/Jakarta')->subDays(9)->format('Y-m-d'),
            'end_date'          => Carbon::today('Asia/Jakarta')->addDays(171)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '07:00:00',
            'treatment_status'  => 'Berjalan',
        ]);
        MedicationRecord::create([
            'patient_treatment_id' => $tr2->id,
            'is_verified'          => true,
            'status'               => 'Diminum',
            'created_at'           => Carbon::now('Asia/Jakarta'),
        ]);

        // Patient 3: Active treatment, taken today, BUT has visit tomorrow -> needs_reminder = true & next_visit set
        $u3 = User::create([
            'name'         => 'Pasien Kontrol Besok',
            'username'     => 'p3_' . uniqid(),
            'email'        => 'p3_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
        ]);
        $p3 = Patient::create([
            'user_id'        => $u3->id,
            'nik'            => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'   => $puskesmas->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $village->id,
        ]);
        $tr3 = PatientTreatment::create([
            'patient_id'        => $p3->id,
            'treatment_type_id' => $treatmentType->id,
            'start_date'        => Carbon::today('Asia/Jakarta')->subDays(1)->format('Y-m-d'),
            'end_date'          => Carbon::today('Asia/Jakarta')->addDays(179)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '21:00:00',
            'treatment_status'  => 'Berjalan',
        ]);
        MedicationRecord::create([
            'patient_treatment_id' => $tr3->id,
            'is_verified'          => false,
            'status'               => 'Diminum',
            'created_at'           => Carbon::now('Asia/Jakarta'),
        ]);
        TreatmentVisit::create([
            'patient_treatment_id' => $tr3->id,
            'visit_date'           => Carbon::tomorrow('Asia/Jakarta')->format('Y-m-d'),
            'visit_time'           => '09:30',
            'visit_status'         => 'Terjadwal',
            'notes'                => 'Cek dahak',
        ]);

        // Patient 4: Sembuh outcome -> needs_reminder = false
        $u4 = User::create([
            'name'         => 'Pasien Sembuh',
            'username'     => 'p4_' . uniqid(),
            'email'        => 'p4_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
        ]);
        $p4 = Patient::create([
            'user_id'        => $u4->id,
            'nik'            => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'   => $puskesmas->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $village->id,
        ]);
        PatientTreatment::create([
            'patient_id'        => $p4->id,
            'treatment_type_id' => $treatmentType->id,
            'start_date'        => Carbon::today('Asia/Jakarta')->subDays(180)->format('Y-m-d'),
            'end_date'          => Carbon::today('Asia/Jakarta')->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => 'Sembuh',
        ]);

        Sanctum::actingAs($pjtbUser);

        $response = $this->getJson('/api/patients');
        $response->assertStatus(200);

        $rows = collect($response->json('data'))->keyBy('id');

        $this->assertTrue($rows[$p1->id]['needs_reminder']);
        $this->assertFalse($rows[$p1->id]['today_medication_taken']);
        $this->assertEquals(4, $rows[$p1->id]['days_elapsed']);

        $this->assertFalse($rows[$p2->id]['needs_reminder']);
        $this->assertTrue($rows[$p2->id]['today_medication_taken']);

        $this->assertTrue($rows[$p3->id]['needs_reminder']);
        $this->assertTrue($rows[$p3->id]['today_medication_taken']);
        $this->assertTrue($rows[$p3->id]['has_upcoming_visit']);
        $this->assertSame('09:30', $rows[$p3->id]['next_visit']['visit_time']);

        $this->assertFalse($rows[$p4->id]['needs_reminder']);

        // Also check Petugas access to /show, /history, and /screening/history
        $this->getJson("/api/patients/{$p1->id}/show")
            ->assertStatus(200)
            ->assertJsonPath('data.needs_reminder', true);

        $this->getJson("/api/kader/patients/{$p1->id}/history")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/screening/history?patient_id={$p1->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
