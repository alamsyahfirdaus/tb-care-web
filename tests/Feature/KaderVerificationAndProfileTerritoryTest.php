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
use App\Models\User;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KaderVerificationAndProfileTerritoryTest extends TestCase
{
    use DatabaseTransactions;

    protected $kaderUser;
    protected $otherKaderUser;
    protected $village;
    protected $patientUser;
    protected $patient;
    protected $treatment;

    protected function setUp(): void
    {
        parent::setUp();

        $province = Province::first() ?? Province::create(['name' => 'Jawa Barat']);
        $district = District::first() ?? District::create(['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::first() ?? Subdistrict::create(['name' => 'Tamansari', 'district_id' => $district->id]);
        $this->village = Village::first() ?? Village::create(['name' => 'Sukahurip', 'subdistrict_id' => $subdistrict->id]);
        $puskesmas = Puskesmas::first() ?? Puskesmas::create(['name' => 'Puskesmas Tamansari', 'subdistrict_id' => $subdistrict->id]);
        $treatmentType = TreatmentType::first() ?? TreatmentType::create(['treatment_type' => 'Kategori 1', 'treatment_duration' => 6, 'duration_unit' => 'bulan']);

        // Kader User & Officer
        $this->kaderUser = User::create([
            'name'         => 'Dede Tetin',
            'username'     => 'dedetetin_' . uniqid(),
            'phone'        => '0857' . rand(10000000, 99999999),
            'email'        => 'dede_' . uniqid() . '@test.com',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 3,
            'gender'       => 'P',
        ]);

        $officer = Officer::create([
            'user_id'         => $this->kaderUser->id,
            'officer_type_id' => 4,
            'puskesmas_id'    => $puskesmas->id,
            'district_id'     => $district->id,
        ]);

        KaderArea::create([
            'officer_id'     => $officer->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $this->village->id,
            'rw'             => '01',
            'rt'             => '01',
        ]);

        // Other Kader in another village
        $otherVillage = Village::where('id', '!=', $this->village->id)->first() ?? Village::create(['name' => 'Desa Lain', 'subdistrict_id' => $subdistrict->id]);
        $this->otherKaderUser = User::create([
            'name'         => 'Kader Luar',
            'username'     => 'kaderluar_' . uniqid(),
            'phone'        => '0858' . rand(10000000, 99999999),
            'email'        => 'kaderluar_' . uniqid() . '@test.com',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 3,
            'gender'       => 'P',
        ]);
        $otherOfficer = Officer::create([
            'user_id'         => $this->otherKaderUser->id,
            'officer_type_id' => 4,
            'puskesmas_id'    => $puskesmas->id,
            'district_id'     => $district->id,
        ]);
        KaderArea::create([
            'officer_id'     => $otherOfficer->id,
            'subdistrict_id' => $subdistrict->id,
            'village_id'     => $otherVillage->id,
        ]);

        // Patient
        $this->patientUser = User::create([
            'name'         => 'Imat',
            'username'     => 'imat123',
            'phone'        => '0821' . rand(10000000, 99999999),
            'email'        => 'imat_' . uniqid() . '@test.com',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 2,
            'gender'       => 'L',
        ]);

        $this->patient = Patient::create([
            'user_id'              => $this->patientUser->id,
            'nik'                  => '3278050101880013',
            'puskesmas_id'         => $puskesmas->id,
            'village_id'           => $this->village->id,
            'subdistrict_id'       => $subdistrict->id,
            'address'              => 'RT 01 / RW 01',
            'rt'                   => '01',
            'rw'                   => '01',
            'treatment_start_date' => Carbon::today('Asia/Jakarta')->subDays(10)->format('Y-m-d'),
        ]);

        $this->treatment = PatientTreatment::create([
            'patient_id'        => $this->patient->id,
            'treatment_type_id' => $treatmentType->id,
            'start_date'        => Carbon::today('Asia/Jakarta')->subDays(10)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '06:00',
            'treatment_status'  => 'Berjalan',
        ]);
    }

    /**
     * Test: Profile endpoint returns read-only Wilayah Kader.
     */
    public function test_kader_profile_includes_territory_info()
    {
        Sanctum::actingAs($this->kaderUser, ['*']);

        $res = $this->getJson('/api/profile/show');
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $this->assertTrue($res->json('data.is_kader'));
        $this->assertNotEmpty($res->json('data.kader_territory_formatted'));
        $this->assertStringContainsString($this->village->name, $res->json('data.kader_territory_formatted'));

        // Kader cannot update territory via Profile Update
        $updateRes = $this->putJson('/api/profile/update', [
            'village_id' => 999,
        ]);
        $updateRes->assertStatus(403);
    }

    /**
     * Test: Kader can verify missed dose as Minum Obat Tanpa Konfirmasi (no photo required).
     */
    public function test_kader_verify_medication_minum_tanpa_konfirmasi()
    {
        Sanctum::actingAs($this->kaderUser, ['*']);

        $targetDate = Carbon::today('Asia/Jakarta')->subDays(2)->format('Y-m-d');

        $res = $this->postJson("/api/kader/patients/{$this->patient->id}/verify-medication", [
            'date'   => $targetDate,
            'action' => 'minum_tanpa_konfirmasi',
            'notes'  => 'Sudah dikonfirmasi saat kunjungan rumah.',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.status', 'Diminum');
        $res->assertJsonPath('data.confirmation_source', 'kader');
        $res->assertJsonPath('data.verified_by', $this->kaderUser->id);

        // Check database
        $this->assertDatabaseHas('medication_records', [
            'patient_treatment_id' => $this->treatment->id,
            'status'               => 'Diminum',
            'confirmation_source'  => 'kader',
            'verified_by'          => $this->kaderUser->id,
            'photo'                => null, // NO photo required
        ]);
    }

    /**
     * Test: Kader can verify missed dose as Tidak Minum Obat.
     */
    public function test_kader_verify_medication_tidak_minum()
    {
        Sanctum::actingAs($this->kaderUser, ['*']);

        $targetDate = Carbon::today('Asia/Jakarta')->subDays(3)->format('Y-m-d');

        $res = $this->postJson("/api/kader/patients/{$this->patient->id}/verify-medication", [
            'date'   => $targetDate,
            'action' => 'tidak_minum',
            'notes'  => 'Pasien lupa minum obat.',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.status', 'Terlewat');
        $res->assertJsonPath('data.confirmation_source', 'kader');

        // Check database
        $this->assertDatabaseHas('medication_records', [
            'patient_treatment_id' => $this->treatment->id,
            'status'               => 'Terlewat',
            'confirmation_source'  => 'kader',
            'verified_by'          => $this->kaderUser->id,
        ]);
    }

    /**
     * Test: IDOR protection - Kader outside patient's territory is rejected.
     */
    public function test_kader_outside_scope_cannot_verify_medication()
    {
        Sanctum::actingAs($this->otherKaderUser, ['*']);

        $targetDate = Carbon::today('Asia/Jakarta')->subDays(1)->format('Y-m-d');

        $res = $this->postJson("/api/kader/patients/{$this->patient->id}/verify-medication", [
            'date'   => $targetDate,
            'action' => 'minum_tanpa_konfirmasi',
        ]);

        $res->assertStatus(403);
    }
}
