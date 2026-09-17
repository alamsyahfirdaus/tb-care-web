<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\KaderArea;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\PatientMedicationSchedule;
use App\Models\PatientTreatment;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicationScheduleTest extends TestCase
{
    use DatabaseTransactions;

    protected $puskesmas;
    protected $subdistrict;
    protected $village;
    protected $otherVillage;

    protected $patientUserA;
    protected $patientA;

    protected $patientUserB;
    protected $patientB;

    protected $kaderUser;
    protected $kaderOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->puskesmas = Puskesmas::first();
        $this->subdistrict = Subdistrict::first();
        $this->village = Village::first();
        $this->otherVillage = Village::where('id', '!=', $this->village->id)->first() ?? $this->village;

        // Pasien A (RW 05, RT 01)
        $this->patientUserA = User::create([
            'name'         => 'Pasien A Test',
            'username'     => 'pasiena_' . uniqid(),
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $this->patientA = Patient::create([
            'user_id'              => $this->patientUserA->id,
            'nik'                  => '3201' . rand(100000000000, 999999999999),
            'address'              => 'Jl Pasien A',
            'puskesmas_id'         => $this->puskesmas->id,
            'subdistrict_id'       => $this->subdistrict->id,
            'village_id'           => $this->village->id,
            'rw'                   => '05',
            'rt'                   => '01',
            'treatment_start_date' => '2026-09-17',
        ]);

        // Pasien B (RW 09, RT 02)
        $this->patientUserB = User::create([
            'name'         => 'Pasien B Test',
            'username'     => 'pasienb_' . uniqid(),
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 2,
            'is_active'    => true,
        ]);

        $this->patientB = Patient::create([
            'user_id'              => $this->patientUserB->id,
            'nik'                  => '3202' . rand(100000000000, 999999999999),
            'address'              => 'Jl Pasien B',
            'puskesmas_id'         => $this->puskesmas->id,
            'subdistrict_id'       => $this->subdistrict->id,
            'village_id'           => $this->village->id,
            'rw'                   => '09',
            'rt'                   => '02',
            'treatment_start_date' => '2026-09-10',
        ]);

        // Kader (bertugas di RW 05, RT 01)
        $this->kaderUser = User::create([
            'name'         => 'Kader Test Schedule',
            'username'     => 'kadersch_' . uniqid(),
            'phone'        => '0813' . rand(10000000, 99999999),
            'password'     => bcrypt('password'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);

        $this->kaderOfficer = Officer::create([
            'user_id'         => $this->kaderUser->id,
            'officer_type_id' => 4, // Kader Puskesmas
            'puskesmas_id'    => $this->puskesmas->id,
            'district_id'     => District::first()?->id,
        ]);

        KaderArea::create([
            'officer_id'     => $this->kaderOfficer->id,
            'subdistrict_id' => $this->subdistrict->id,
            'village_id'     => $this->village->id,
            'rw'             => '05',
            'rt'             => '01',
        ]);
    }

    /**
     * Test 1: GET jadwal ketika belum ada -> response data bernilai null.
     */
    public function test_get_schedule_returns_null_when_not_set()
    {
        Sanctum::actingAs($this->patientUserA);

        $response = $this->getJson("/api/patients/{$this->patientA->id}/medication-schedule");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => null,
            ]);
    }

    /**
     * Test 2: Pasien menyimpan jadwal minum obat baru (08:00).
     */
    public function test_patient_can_create_medication_schedule()
    {
        Sanctum::actingAs($this->patientUserA);

        $response = $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", [
            'reminder_time' => '08:00',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Jadwal minum obat berhasil disimpan.',
                'data'    => [
                    'patient_id'    => $this->patientA->id,
                    'reminder_time' => '08:00:00',
                    'is_active'     => true,
                ],
            ]);

        $this->assertDatabaseHas('patient_medication_schedules', [
            'patient_id'    => $this->patientA->id,
            'reminder_time' => '08:00:00',
            'is_active'     => 1,
        ]);
    }

    /**
     * Test 3: Pasien mengubah jadwal (08:00 -> 07:30) mengupdate baris yang sama (tanpa duplikasi).
     */
    public function test_patient_updating_schedule_does_not_create_duplicate_row()
    {
        Sanctum::actingAs($this->patientUserA);

        // 1. Simpan pertama
        $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", [
            'reminder_time' => '08:00',
        ])->assertStatus(200);

        $countBefore = PatientMedicationSchedule::where('patient_id', $this->patientA->id)->count();
        $this->assertEquals(1, $countBefore);

        // 2. Ubah waktu
        $updateResponse = $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", [
            'reminder_time' => '07:30',
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'patient_id'    => $this->patientA->id,
                    'reminder_time' => '07:30:00',
                    'is_active'     => true,
                ],
            ]);

        $countAfter = PatientMedicationSchedule::where('patient_id', $this->patientA->id)->count();
        $this->assertEquals(1, $countAfter);

        $this->assertDatabaseHas('patient_medication_schedules', [
            'patient_id'    => $this->patientA->id,
            'reminder_time' => '07:30:00',
            'is_active'     => 1,
        ]);
    }

    /**
     * Test 4: Validasi gagal jika reminder_time kosong.
     */
    public function test_validation_fails_when_reminder_time_is_missing()
    {
        Sanctum::actingAs($this->patientUserA);

        $response = $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reminder_time']);
    }

    /**
     * Test 5: Validasi gagal jika format reminder_time tidak valid.
     */
    public function test_validation_fails_for_invalid_time_format()
    {
        Sanctum::actingAs($this->patientUserA);

        $response = $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", [
            'reminder_time' => 'invalid_time',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reminder_time']);
    }

    /**
     * Test 6: IDOR Protection - Pasien A tidak boleh melihat atau mengubah jadwal Pasien B (403 Forbidden).
     */
    public function test_patient_cannot_access_other_patient_schedule()
    {
        Sanctum::actingAs($this->patientUserA);

        // GET Pasien B schedule
        $getResponse = $this->getJson("/api/patients/{$this->patientB->id}/medication-schedule");
        $getResponse->assertStatus(403);

        // POST Pasien B schedule
        $postResponse = $this->postJson("/api/patients/{$this->patientB->id}/medication-schedule", [
            'reminder_time' => '09:00',
        ]);
        $postResponse->assertStatus(403);
    }

    /**
     * Test 7: Kader di wilayah binaan diizinkan melihat dan mengupdate jadwal pasien.
     */
    public function test_kader_in_assigned_area_can_manage_patient_schedule()
    {
        Sanctum::actingAs($this->kaderUser);

        // Kader simpan jadwal untuk Pasien A (Pasien A ada di RW 05 RT 01)
        $postResponse = $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", [
            'reminder_time' => '08:15',
        ]);
        $postResponse->assertStatus(200);

        // Kader membaca jadwal Pasien A
        $getResponse = $this->getJson("/api/patients/{$this->patientA->id}/medication-schedule");
        $getResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'patient_id'    => $this->patientA->id,
                    'reminder_time' => '08:15:00',
                ],
            ]);
    }

    /**
     * Test 8: Kader di luar wilayah binaan ditolak (403 Forbidden).
     */
    public function test_kader_outside_assigned_area_is_forbidden()
    {
        Sanctum::actingAs($this->kaderUser);

        // Pasien B berada di RW 09 RT 02 (di luar area kader RW 05 RT 01)
        $getResponse = $this->getJson("/api/patients/{$this->patientB->id}/medication-schedule");
        $getResponse->assertStatus(403);

        $postResponse = $this->postJson("/api/patients/{$this->patientB->id}/medication-schedule", [
            'reminder_time' => '08:00',
        ]);
        $postResponse->assertStatus(403);
    }

    /**
     * Test 9: Pasien tanpa data patient_treatments tetap bisa membuat dan melihat jadwal.
     */
    public function test_patient_without_treatments_can_manage_schedule()
    {
        Sanctum::actingAs($this->patientUserA);

        // Pastikan tidak ada data treatment
        $this->assertEquals(0, PatientTreatment::where('patient_id', $this->patientA->id)->count());

        // Simpan jadwal
        $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", [
            'reminder_time' => '06:00',
        ])->assertStatus(200);

        // Periksa GET
        $getResponse = $this->getJson("/api/patients/{$this->patientA->id}/medication-schedule");
        $getResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'patient_id'    => $this->patientA->id,
                    'reminder_time' => '06:00:00',
                    'is_active'     => true,
                ],
            ]);
    }

    /**
     * Test 10: patient_treatments dan medication_records tidak berubah/tidak dibuat record palsu.
     */
    public function test_no_dummy_treatments_or_medication_records_created()
    {
        Sanctum::actingAs($this->patientUserA);

        $treatmentsBefore = PatientTreatment::count();
        $recordsBefore = \DB::table('medication_records')->count();

        $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", [
            'reminder_time' => '10:00',
        ])->assertStatus(200);

        $treatmentsAfter = PatientTreatment::count();
        $recordsAfter = \DB::table('medication_records')->count();

        $this->assertEquals($treatmentsBefore, $treatmentsAfter);
        $this->assertEquals($recordsBefore, $recordsAfter);
    }

    /**
     * Test 11: GET /api/patients/{id}/show menyertakan medication_schedule.
     */
    public function test_patient_show_endpoint_includes_medication_schedule()
    {
        Sanctum::actingAs($this->patientUserA);

        // Sebelum diatur: medication_schedule null
        $showResponse1 = $this->getJson("/api/patients/{$this->patientA->id}/show");
        $showResponse1->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id'                  => $this->patientA->id,
                    'medication_schedule' => null,
                ]
            ]);

        // Atur jadwal
        $this->postJson("/api/patients/{$this->patientA->id}/medication-schedule", [
            'reminder_time' => '08:00',
        ])->assertStatus(200);

        // Sesudah diatur: medication_schedule terisi
        $showResponse2 = $this->getJson("/api/patients/{$this->patientA->id}/show");
        $showResponse2->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id'                  => $this->patientA->id,
                    'medication_schedule' => [
                        'patient_id'    => $this->patientA->id,
                        'reminder_time' => '08:00:00',
                        'is_active'     => true,
                    ]
                ]
            ]);
    }
}
