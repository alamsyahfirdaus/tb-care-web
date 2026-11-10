<?php

namespace Tests\Feature;

use App\Models\MedicationRecord;
use App\Models\Patient;
use App\Models\PatientTreatment;
use App\Models\Puskesmas;
use App\Models\TreatmentType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MedicationEpisodeAndVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function createPatientWithMultipleEpisodes()
    {
        $puskesmas = Puskesmas::first() ?? Puskesmas::create([
            'name'    => 'Puskesmas Tamansari',
            'address' => 'Jl. Tamansari No. 1',
        ]);

        $user = User::create([
            'name'         => 'Pasien Multi Episode',
            'username'     => 'pasien_multi_' . uniqid(),
            'email'        => 'pasien_multi_' . uniqid() . '@tbcare.id',
            'phone'        => '0812' . rand(10000000, 99999999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2, // Pasien
        ]);

        $patient = Patient::create([
            'user_id'              => $user->id,
            'nik'                  => '3278' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'address'              => 'Jl. Tamansari',
            'treatment_start_date' => Carbon::now()->subMonths(10)->format('Y-m-d'),
        ]);

        $treatmentType = TreatmentType::first() ?? TreatmentType::create([
            'treatment_type'     => 'Kategori 1',
            'treatment_duration' => 6,
            'duration_unit'      => 'month',
        ]);

        // Episode 1 (Selesai 4 bulan lalu)
        $episode1 = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now()->subMonths(10)->format('Y-m-d'),
            'start_date'        => Carbon::now()->subMonths(10)->format('Y-m-d'),
            'end_date'          => Carbon::now()->subMonths(4)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '07:00:00',
            'prescription'      => json_encode(['Isoniazid', 'Rifampisin']),
            'treatment_status'  => 'Selesai',
        ]);

        // Medication records for Episode 1 (Dikonfirmasi oleh kader)
        $record1 = new MedicationRecord([
            'patient_treatment_id' => $episode1->id,
            'is_verified'          => true,
            'status'               => 'Sudah Minum',
            'confirmation_source'  => 'kader',
        ]);
        $record1->created_at = Carbon::now()->subMonths(9)->setTime(7, 15);
        $record1->updated_at = Carbon::now()->subMonths(9)->setTime(7, 15);
        $record1->save();

        $record2 = new MedicationRecord([
            'patient_treatment_id' => $episode1->id,
            'is_verified'          => true,
            'status'               => 'Sudah Minum',
            'confirmation_source'  => 'kader',
        ]);
        $record2->created_at = Carbon::now()->subMonths(9)->addDay()->setTime(7, 10);
        $record2->updated_at = Carbon::now()->subMonths(9)->addDay()->setTime(7, 10);
        $record2->save();

        // Episode 2 (Berjalan, dimulai 3 hari lalu)
        $episode2 = PatientTreatment::create([
            'patient_id'        => $patient->id,
            'treatment_type_id' => $treatmentType->id,
            'diagnosis_date'    => Carbon::now()->subDays(5)->format('Y-m-d'),
            'start_date'        => Carbon::now()->subDays(3)->format('Y-m-d'),
            'end_date'          => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'treatment_days'    => 180,
            'medication_time'   => '08:00:00',
            'prescription'      => json_encode(['Etambutol', 'Pirazinamid']),
            'treatment_status'  => 'Berjalan',
        ]);

        // Medication record for Episode 2: Diminum oleh pasien sendiri kemarin
        $record3 = new MedicationRecord([
            'patient_treatment_id' => $episode2->id,
            'is_verified'          => false,
            'status'               => 'Sudah Minum',
            'confirmation_source'  => 'pasien',
        ]);
        $record3->created_at = Carbon::now()->subDay()->setTime(8, 5);
        $record3->updated_at = Carbon::now()->subDay()->setTime(8, 5);
        $record3->save();

        return [
            'user'     => $user,
            'patient'  => $patient,
            'episode1' => $episode1,
            'episode2' => $episode2,
        ];
    }

    /**
     * Test scoping medication history by episode treatment_id.
     */
    public function test_medication_history_strictly_scoped_by_treatment_id()
    {
        $data = $this->createPatientWithMultipleEpisodes();

        // 1. Query for Episode 1 specifically
        $response1 = $this->actingAs($data['user'], 'sanctum')
            ->getJson("/api/treatments/{$data['patient']->id}/history?treatment_id={$data['episode1']->id}");

        $response1->assertStatus(200);
        $response1->assertJsonPath('treatment_id', $data['episode1']->id);
        $this->assertEquals(2, count($response1->json('raw_records')));

        // 2. Query for Episode 2 specifically
        $response2 = $this->actingAs($data['user'], 'sanctum')
            ->getJson("/api/treatments/{$data['patient']->id}/history?treatment_id={$data['episode2']->id}");

        $response2->assertStatus(200);
        $response2->assertJsonPath('treatment_id', $data['episode2']->id);
        // Only Episode 2 record (1 raw record)
        $this->assertEquals(1, count($response2->json('raw_records')));

        // 3. Query without treatment_id defaults to active Episode 2
        $responseDefault = $this->actingAs($data['user'], 'sanctum')
            ->getJson("/api/treatments/{$data['patient']->id}/history");

        $responseDefault->assertStatus(200);
        $responseDefault->assertJsonPath('treatment_id', $data['episode2']->id);
        $this->assertEquals(1, count($responseDefault->json('raw_records')));
    }

    /**
     * Test that today's unconfirmed medication is marked as Belum Konfirmasi (when time has passed)
     * and Belum Jatuh Tempo (when time has not passed), and neither is counted as missed.
     */
    public function test_today_unconfirmed_dose_is_marked_not_confirmed_and_not_counted_as_missed()
    {
        $data = $this->createPatientWithMultipleEpisodes();
        $episode = $data['episode2'];

        // Set medication_time to 1 minute past midnight so it is definitely past scheduled time today
        $episode->update(['medication_time' => '00:01:00']);

        $detailed = $episode->calculateDetailedAdherence();

        $today = Carbon::today()->format('Y-m-d');
        $todayEntry = collect($detailed['daily_records'])->firstWhere('date', $today);

        $this->assertNotNull($todayEntry, 'Today entry must exist in daily records');
        $this->assertEquals('Belum Konfirmasi', $todayEntry['status']);
        $this->assertEquals('not_confirmed', $todayEntry['status_key']);
        $this->assertStringContainsString('23:59 WIB', $todayEntry['notes']);

        // Assert today's unconfirmed dose is NOT counted in missed_days
        $missedDates = collect($detailed['missed_days'])->pluck('date')->all();
        $this->assertNotContains($today, $missedDates);

        // Also verify that if medication time is in the future today (e.g., 23:59), it is Belum Jatuh Tempo
        $episode->update(['medication_time' => '23:59:00']);
        $detailedFuture = $episode->calculateDetailedAdherence();
        $futureTodayEntry = collect($detailedFuture['daily_records'])->firstWhere('date', $today);
        $this->assertEquals('Belum Jatuh Tempo', $futureTodayEntry['status']);
        $this->assertEquals('not_due', $futureTodayEntry['status_key']);
    }

    /**
     * Test verification status logic:
     * - Patient submission shows 'Sudah Minum' (never Terverifikasi).
     * - Kader field verification shows 'Terverifikasi'.
     */
    public function test_medication_verification_status_distinguishes_patient_and_kader()
    {
        $data = $this->createPatientWithMultipleEpisodes();

        // 1. Patient record in Episode 2 (confirmation_source = 'pasien')
        $detailed2 = $data['episode2']->calculateDetailedAdherence();
        $yesterday = Carbon::now()->subDay()->format('Y-m-d');
        $patientRecord = collect($detailed2['daily_records'])->firstWhere('date', $yesterday);

        $this->assertNotNull($patientRecord);
        $this->assertEquals('Sudah Minum', $patientRecord['status']);
        $this->assertEquals('Sudah Minum', $patientRecord['verification_status']);
        $this->assertEquals(0, (int)$patientRecord['is_verified']);

        // 2. Kader verified record in Episode 1 (confirmation_source = 'kader')
        $detailed1 = $data['episode1']->calculateDetailedAdherence();
        $kaderRecord = collect($detailed1['daily_records'])->firstWhere('status', 'Sudah Minum');

        $this->assertNotNull($kaderRecord);
        $this->assertEquals('Terverifikasi', $kaderRecord['verification_status']);
        $this->assertEquals(1, (int)$kaderRecord['is_verified']);
    }
}
