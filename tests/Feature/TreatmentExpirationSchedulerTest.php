<?php

namespace Tests\Feature;

use App\Models\District;
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
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TreatmentExpirationSchedulerTest extends TestCase
{
    use DatabaseTransactions;

    protected $puskesmas;
    protected $treatmentType;
    protected $pjtbUser;

    protected function setUp(): void
    {
        parent::setUp();

        $province = Province::first() ?? Province::create(['name' => 'Provinsi Test']);
        $district = District::where('province_id', $province->id)->first()
            ?? District::create(['name' => 'Kabupaten Test', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::where('district_id', $district->id)->first()
            ?? Subdistrict::create(['name' => 'Kecamatan Test', 'district_id' => $district->id]);
        $village = Village::where('subdistrict_id', $subdistrict->id)->first()
            ?? Village::create(['name' => 'Desa Test', 'subdistrict_id' => $subdistrict->id]);

        $this->puskesmas = Puskesmas::create([
            'name'           => 'Puskesmas Expiration Test ' . uniqid(),
            'subdistrict_id' => $subdistrict->id,
        ]);

        $this->treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        $this->pjtbUser = User::create([
            'name'         => 'PJTB Test Officer',
            'username'     => 'pjtb_exp_' . uniqid(),
            'email'        => 'pjtb_exp_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
        ]);

        Officer::create([
            'user_id'         => $this->pjtbUser->id,
            'officer_type_id' => 3,
            'puskesmas_id'    => $this->puskesmas->id,
        ]);
    }

    private function createPatientWithTreatment(string $startDate, ?string $endDate, string $status = 'Berjalan'): array
    {
        $user = User::create([
            'name'         => 'Pasien ' . uniqid(),
            'username'     => 'pasien_' . uniqid(),
            'email'        => 'pasien_' . uniqid() . '@test.com',
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
        ]);

        $patient = Patient::create([
            'user_id'      => $user->id,
            'nik'          => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id' => $this->puskesmas->id,
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $this->treatmentType->id,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'treatment_status'  => $status,
        ]);

        return [$patient, $treatment, $user];
    }

    public function test_scheduler_transitions_expired_berjalan_treatment_to_perlu_tindak_lanjut(): void
    {
        $todayWib = Carbon::today('Asia/Jakarta');

        // 1. Pasien masa depan: belum berakhir -> Tetap Berjalan
        [$p1, $trFuture] = $this->createPatientWithTreatment(
            $todayWib->copy()->subDays(30)->toDateString(),
            $todayWib->copy()->addDays(150)->toDateString(),
            'Berjalan'
        );

        // 2. Pasien tanggal akhir = HARI INI -> Tetap Berjalan sampai akhir hari
        [$p2, $trToday] = $this->createPatientWithTreatment(
            $todayWib->copy()->subDays(180)->toDateString(),
            $todayWib->toDateString(),
            'Berjalan'
        );

        // 3. Pasien tanggal akhir = KEMARIN (sudah terlewati) -> Berubah jadi perlu_tindak_lanjut
        [$p3, $trExpired] = $this->createPatientWithTreatment(
            $todayWib->copy()->subDays(185)->toDateString(),
            $todayWib->copy()->subDays(1)->toDateString(),
            'Berjalan'
        );

        // 4. Pasien tanggal akhir lampau tapi status SEMBUH -> Tetap Sembuh
        [$p4, $trSembuh] = $this->createPatientWithTreatment(
            $todayWib->copy()->subDays(200)->toDateString(),
            $todayWib->copy()->subDays(20)->toDateString(),
            'Sembuh'
        );

        // 5. Pasien tanggal akhir lampau tapi status PENGOBATAN LENGKAP -> Tetap Pengobatan Lengkap
        [$p5, $trLengkap] = $this->createPatientWithTreatment(
            $todayWib->copy()->subDays(200)->toDateString(),
            $todayWib->copy()->subDays(20)->toDateString(),
            'Pengobatan Lengkap'
        );

        // 6. Pasien tanggal akhir lampau tapi status GAGAL -> Tetap Gagal
        [$p6, $trGagal] = $this->createPatientWithTreatment(
            $todayWib->copy()->subDays(200)->toDateString(),
            $todayWib->copy()->subDays(20)->toDateString(),
            'Gagal'
        );

        // 7. Pasien tanggal akhir lampau tapi status MENINGGAL -> Tetap Meninggal
        [$p7, $trMeninggal] = $this->createPatientWithTreatment(
            $todayWib->copy()->subDays(200)->toDateString(),
            $todayWib->copy()->subDays(20)->toDateString(),
            'Meninggal'
        );

        // 8. Pasien tanpa end_date -> Tidak diubah
        [$p8, $trNoEnd] = $this->createPatientWithTreatment(
            $todayWib->copy()->subDays(10)->toDateString(),
            null,
            'Berjalan'
        );

        // Jalankan Artisan Command
        $exitCode = Artisan::call('patients:check-treatment-expiration');
        $this->assertEquals(0, $exitCode);

        // Verifikasi hasil
        $this->assertEquals('Berjalan', $trFuture->fresh()->treatment_status);
        $this->assertEquals('Berjalan', $trToday->fresh()->treatment_status);
        $this->assertEquals('perlu_tindak_lanjut', $trExpired->fresh()->treatment_status);
        $this->assertEquals('Sembuh', $trSembuh->fresh()->treatment_status);
        $this->assertEquals('Pengobatan Lengkap', $trLengkap->fresh()->treatment_status);
        $this->assertEquals('Gagal', $trGagal->fresh()->treatment_status);
        $this->assertEquals('Meninggal', $trMeninggal->fresh()->treatment_status);
        $this->assertEquals('Berjalan', $trNoEnd->fresh()->treatment_status);

        // 9. Uji Idempotensi: Jalankan sekali lagi, status trExpired tetap perlu_tindak_lanjut dan tidak error
        $exitCode2 = Artisan::call('patients:check-treatment-expiration');
        $this->assertEquals(0, $exitCode2);
        $this->assertEquals('perlu_tindak_lanjut', $trExpired->fresh()->treatment_status);
    }

    public function test_petugas_can_manually_update_status_to_and_from_perlu_tindak_lanjut(): void
    {
        [$patient, $treatment] = $this->createPatientWithTreatment(
            Carbon::today('Asia/Jakarta')->subDays(200)->toDateString(),
            Carbon::today('Asia/Jakarta')->subDays(20)->toDateString(),
            'Berjalan'
        );

        Sanctum::actingAs($this->pjtbUser);

        // Petugas mengubah status ke 'perlu_tindak_lanjut'
        $response1 = $this->postJson('/api/treatments/status', [
            'id'               => $treatment->id,
            'treatment_status' => 'perlu_tindak_lanjut',
        ]);
        $response1->assertStatus(200);
        $this->assertEquals('perlu_tindak_lanjut', $treatment->fresh()->treatment_status);

        // Petugas memeriksa kondisi pasien dan mengubah status dari 'perlu_tindak_lanjut' ke 'Sembuh'
        $response2 = $this->postJson('/api/treatments/status', [
            'id'               => $treatment->id,
            'treatment_status' => 'Sembuh',
        ]);
        $response2->assertStatus(200);
        $this->assertEquals('Sembuh', $treatment->fresh()->treatment_status);
    }

    public function test_patients_index_filters_by_perlu_tindak_lanjut(): void
    {
        [$p1, $tr1] = $this->createPatientWithTreatment(
            Carbon::today('Asia/Jakarta')->subDays(200)->toDateString(),
            Carbon::today('Asia/Jakarta')->subDays(20)->toDateString(),
            'perlu_tindak_lanjut'
        );

        [$p2, $tr2] = $this->createPatientWithTreatment(
            Carbon::today('Asia/Jakarta')->subDays(50)->toDateString(),
            Carbon::today('Asia/Jakarta')->addDays(130)->toDateString(),
            'Berjalan'
        );

        Sanctum::actingAs($this->pjtbUser);

        $response = $this->getJson('/api/patients?treatment_status=perlu_tindak_lanjut');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $p1Found = collect($data)->firstWhere('id', $p1->id);
        $this->assertNotNull($p1Found);

        $firstTreatment = $p1Found['treatments'][0] ?? null;
        $this->assertNotNull($firstTreatment);
        $this->assertEquals('perlu_tindak_lanjut', $firstTreatment['treatment_status']);
    }
}
