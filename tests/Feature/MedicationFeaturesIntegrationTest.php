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
use App\Notifications\PatientMedicationLoggedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MedicationFeaturesIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setupFullTestEnvironment(int $daysSub = 10)
    {
        // 1. Master Data
        $province = Province::first() ?? Province::create(['name' => 'Jawa Barat']);
        $district = District::first() ?? District::create(['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::first() ?? Subdistrict::create(['name' => 'Tamansari', 'district_id' => $district->id]);
        $puskesmas = Puskesmas::first() ?? Puskesmas::create([
            'name'           => 'Puskesmas Cihideung',
            'address'        => 'Jl. Cihideung No. 1',
            'subdistrict_id' => $subdistrict->id,
        ]);

        $village = Village::first() ?? Village::create([
            'subdistrict_id' => $subdistrict->id,
            'name'           => 'Tuguraja',
        ]);

        $puskesmasLain = Puskesmas::create([
            'name'           => 'Puskesmas Tawang Lain ' . uniqid(),
            'address'        => 'Jl. Tawang No. 99',
            'subdistrict_id' => $subdistrict->id,
        ]);

        // 2. Petugas PJTB (officer_type_id = 3)
        $pjtbUser = User::create([
            'name'         => 'PJTB Petugas ' . uniqid(),
            'username'     => 'pjtb_' . uniqid(),
            'email'        => 'pjtb_' . uniqid() . '@tbcare.id',
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
        ]);
        Officer::create([
            'user_id'         => $pjtbUser->id,
            'puskesmas_id'    => $puskesmas->id,
            'officer_type_id' => 3,
        ]);

        // 3. Petugas Kader wilayah pasien (officer_type_id = 4)
        $kaderUser = User::create([
            'name'         => 'Kader Pendamping ' . uniqid(),
            'username'     => 'kader_' . uniqid(),
            'email'        => 'kader_' . uniqid() . '@tbcare.id',
            'phone'        => '0813' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
        ]);
        $kaderOfficer = Officer::create([
            'user_id'         => $kaderUser->id,
            'puskesmas_id'    => $puskesmas->id,
            'officer_type_id' => 4,
        ]);

        KaderArea::create([
            'officer_id'     => $kaderOfficer->id,
            'subdistrict_id' => $subdistrict ? $subdistrict->id : 1,
            'village_id'     => $village->id,
            'rw'             => '05',
            'rt'             => '01',
        ]);

        // 4. Petugas dari Puskesmas lain (tidak boleh menerima notifikasi)
        $otherOfficerUser = User::create([
            'name'         => 'Petugas Luar ' . uniqid(),
            'username'     => 'luar_' . uniqid(),
            'email'        => 'luar_' . uniqid() . '@tbcare.id',
            'phone'        => '0814' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
        ]);
        Officer::create([
            'user_id'         => $otherOfficerUser->id,
            'puskesmas_id'    => $puskesmasLain->id,
            'officer_type_id' => 3,
        ]);

        // 5. Pasien
        $patientUser = User::create([
            'name'         => 'Alamsyah Firdaus ' . uniqid(),
            'username'     => 'pasien_' . uniqid(),
            'email'        => 'pasien_' . uniqid() . '@tbcare.id',
            'phone'        => '0815' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2,
            'is_active'    => 1,
        ]);

        $patient = Patient::create([
            'user_id'              => $patientUser->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'village_id'           => $village->id,
            'rw'                   => '05',
            'rt'                   => '01',
            'address'              => 'Jl. Sukalaya No. 12',
            'treatment_start_date' => Carbon::now('Asia/Jakarta')->subDays($daysSub)->format('Y-m-d'),
        ]);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        $treatment = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now('Asia/Jakarta')->subDays($daysSub + 2)->format('Y-m-d'),
            'start_date'        => Carbon::now('Asia/Jakarta')->subDays($daysSub)->format('Y-m-d'),
            'end_date'          => Carbon::now('Asia/Jakarta')->addMonths(6)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '07:00:00',
            'prescription'      => json_encode(['Rifampisin', 'Isoniazid']),
            'treatment_status'  => 'Berjalan',
        ]);

        return compact(
            'puskesmas',
            'pjtbUser',
            'kaderUser',
            'otherOfficerUser',
            'patientUser',
            'patient',
            'treatment'
        );
    }

    /**
     * Skenario 1: Pasien mencatat minum obat dengan foto dan keterangan (berhasil tersimpan)
     */
    public function test_patient_can_log_medication_with_photo_and_optional_note()
    {
        $env = $this->setupFullTestEnvironment(5);
        $photo = UploadedFile::fake()->image('bukti_minum.jpg', 600, 600)->size(500);

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $photo,
                'notes'                => 'Obat diminum setelah sarapan bubur ayam, tanpa ada efek mual.',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.notes', 'Obat diminum setelah sarapan bubur ayam, tanpa ada efek mual.');
        $this->assertNotNull($response->json('data.photo'));

        $this->assertDatabaseHas('medication_records', [
            'patient_treatment_id' => $env['treatment']->id,
            'notes'                => 'Obat diminum setelah sarapan bubur ayam, tanpa ada efek mual.',
        ]);
    }

    /**
     * Skenario 2: Pasien ditolak jika mengirim bukti tanpa melampirkan foto (Wajib Foto)
     */
    public function test_patient_cannot_log_medication_without_photo()
    {
        $env = $this->setupFullTestEnvironment(5);

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'notes'                => 'Mencoba submit tanpa melampirkan bukti foto',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['photo']);
        $response->assertJsonPath('errors.photo.0', 'Bukti foto wajib dilampirkan sebelum melakukan konfirmasi minum obat.');
    }

    /**
     * Skenario 2b: Pasien mencatat minum obat dengan foto valid tanpa keterangan (notes null, berhasil)
     */
    public function test_patient_can_log_medication_with_photo_without_note()
    {
        $env = $this->setupFullTestEnvironment(5);
        $photo = UploadedFile::fake()->image('bukti_tanpa_catatan.png', 500, 500)->size(300);

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $photo,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('medication_records', [
            'patient_treatment_id' => $env['treatment']->id,
            'notes'                => null,
        ]);
    }

    /**
     * Skenario 2c: File non-gambar atau ukuran melebihi 2MB ditolak
     */
    public function test_non_image_or_oversized_photo_is_rejected()
    {
        $env = $this->setupFullTestEnvironment(5);

        // Uji file non-gambar (PDF)
        $pdfFile = UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf');
        $responsePdf = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $pdfFile,
            ]);
        $responsePdf->assertStatus(422);
        $responsePdf->assertJsonValidationErrors(['photo']);

        // Uji file gambar melebihi 2MB (2048 KB)
        $largePhoto = UploadedFile::fake()->image('foto_besar.jpg')->size(3000);
        $responseLarge = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $largePhoto,
            ]);
        $responseLarge->assertStatus(422);
        $responseLarge->assertJsonValidationErrors(['photo']);
    }

    /**
     * Skenario 3: Validasi panjang keterangan melebihi batas 500 karakter ditolak
     */
    public function test_medication_note_exceeding_max_characters_is_rejected()
    {
        $env = $this->setupFullTestEnvironment(5);
        $photo = UploadedFile::fake()->image('bukti.jpg');

        $tooLongNote = str_repeat('Keterangan panjang obat TB. ', 35); // > 500 karakter

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $photo,
                'notes'                => $tooLongNote,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['notes']);
    }

    /**
     * Skenario 4: Pencegahan pencatatan ganda pada hari yang sama
     */
    public function test_duplicate_medication_record_on_same_day_is_prevented()
    {
        $env = $this->setupFullTestEnvironment(5);
        $photo1 = UploadedFile::fake()->image('bukti1.jpg');
        $photo2 = UploadedFile::fake()->image('bukti2.jpg');

        // Catatan pertama hari ini
        $response1 = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $photo1,
                'notes'                => 'Catatan pertama pagi hari',
            ]);
        $response1->assertStatus(201);

        // Catatan kedua pada hari yang sama harus ditolak
        $response2 = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $photo2,
                'notes'                => 'Mencoba submit lagi siang hari',
            ]);
        $response2->assertStatus(422);
        $response2->assertJsonPath('message', 'Anda sudah mencatat minum obat untuk hari ini.');
    }

    /**
     * Skenario 5: Perhitungan hari terlewat & kepatuhan akurat
     */
    public function test_missed_medication_days_and_adherence_calculation()
    {
        // Pasien mulai pengobatan 4 hari lalu (hari 0, 1, 2, 3, 4 = 5 hari terjadwal jika waktu sudah lewat)
        $env = $this->setupFullTestEnvironment(4);

        // Buat 2 record minum obat (kemarin dan 3 hari lalu)
        $tgl1 = Carbon::now('Asia/Jakarta')->subDays(3)->setTime(7, 15, 0);
        $tgl2 = Carbon::now('Asia/Jakarta')->subDays(1)->setTime(7, 30, 0);

        MedicationRecord::create([
            'patient_treatment_id' => $env['treatment']->id,
            'notes'                => 'Minum tepat waktu',
            'is_verified'          => 1,
            'created_at'           => $tgl1,
            'updated_at'           => $tgl1,
        ]);

        MedicationRecord::create([
            'patient_treatment_id' => $env['treatment']->id,
            'notes'                => 'Minum obat agak terlambat',
            'is_verified'          => 1,
            'created_at'           => $tgl2,
            'updated_at'           => $tgl2,
        ]);

        $adherence = $env['treatment']->calculateDetailedAdherence('all');

        $this->assertNotNull($adherence);
        $this->assertGreaterThanOrEqual(4, $adherence['total_scheduled']);
        $this->assertEquals(2, $adherence['total_taken']);
        $this->assertEquals($adherence['total_scheduled'] - 2, $adherence['total_missed']);
        $this->assertNotEmpty($adherence['missed_days']);

        // Pastikan API history mengembalikan data adherence ini
        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->getJson('/api/treatments/' . $env['patient']->id . '/history');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'adherence' => [
                'total_scheduled',
                'total_taken',
                'total_missed',
                'adherence_percentage',
                'missed_days',
            ],
        ]);
    }

    /**
     * Skenario 6: Filter periode pemantauan kepatuhan pada Web (Satu Daftar Kronologis)
     */
    public function test_web_patient_monitoring_supports_period_filters()
    {
        $env = $this->setupFullTestEnvironment(20);

        // Akses oleh PJTB dengan route patients.show dan berbagai parameter periode
        $encryptedPatientId = encrypt_id($env['patient']->id);

        foreach (['7_days', '30_days', 'this_month', 'all'] as $period) {
            $response = $this->actingAs($env['pjtbUser'])
                ->get(route('patients.show', $encryptedPatientId) . '?period=' . $period);

            $response->assertStatus(200);
            $response->assertSee('Riwayat Minum Obat');
            $response->assertSee('Kepatuhan Minum Obat');
        }
    }

    /**
     * Skenario 7: Pengiriman notifikasi kepada petugas yang berwenang
     */
    public function test_notification_dispatched_only_to_responsible_officers()
    {
        Notification::fake();

        $env = $this->setupFullTestEnvironment(5);
        $photo = UploadedFile::fake()->image('bukti.jpg');

        $response = $this->actingAs($env['patientUser'], 'sanctum')
            ->postJson('/api/treatments/proof', [
                'patient_treatment_id' => $env['treatment']->id,
                'photo'                => $photo,
                'notes'                => 'Minum obat setelah makan malam',
            ]);

        $response->assertStatus(201);

        // PJTB Puskesmas dan Kader binaan harus menerima notifikasi
        Notification::assertSentTo(
            [$env['pjtbUser'], $env['kaderUser']],
            PatientMedicationLoggedNotification::class,
            function ($notification) use ($env) {
                return $notification->patient->id === $env['patient']->id
                    && $notification->notes === 'Minum obat setelah makan malam';
            }
        );

        // Petugas dari faskes lain TIDAK boleh menerima notifikasi
        Notification::assertNotSentTo(
            [$env['otherOfficerUser']],
            PatientMedicationLoggedNotification::class
        );
    }

    /**
     * Skenario 8: Petugas membaca notifikasi dan mark as read
     */
    public function test_officer_can_mark_notification_as_read()
    {
        $env = $this->setupFullTestEnvironment(5);

        // Kirim notifikasi nyata ke database
        $record = MedicationRecord::create([
            'patient_treatment_id' => $env['treatment']->id,
            'notes'                => 'Obat diminum rutin',
            'created_at'           => Carbon::now('Asia/Jakarta'),
            'updated_at'           => Carbon::now('Asia/Jakarta'),
        ]);

        $notification = new PatientMedicationLoggedNotification(
            $env['patient'],
            $record,
            'Obat diminum rutin'
        );

        $env['pjtbUser']->notify($notification);

        $this->assertEquals(1, $env['pjtbUser']->unreadNotifications()->count());

        $dbNotification = $env['pjtbUser']->unreadNotifications()->first();

        // Mark as read via web route
        $response = $this->actingAs($env['pjtbUser'])
            ->get('/notifications/' . $dbNotification->id . '/read');

        $response->assertRedirect();
        $this->assertEquals(0, $env['pjtbUser']->fresh()->unreadNotifications()->count());
    }
}
