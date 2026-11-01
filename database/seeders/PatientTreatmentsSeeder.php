<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientTreatmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $patientId = DB::table('patients')->value('id') ?? 1;
        $treatmentTypeId = DB::table('treatment_types')->value('id') ?? 1;

        DB::table('patient_treatments')->updateOrInsert(
            [
                'patient_id'        => $patientId,
                'treatment_type_id' => $treatmentTypeId,
                'start_date'        => Carbon::parse('2024-12-05'),
            ],
            [
                'diagnosis_date'   => Carbon::parse('2024-12-01'),
                'end_date'         => Carbon::parse('2025-06-05'),
                'treatment_days'   => 100,
                'medication_time'  => '07:00:00',
                'prescription'     => json_encode(['Rifampisin', 'Isoniazid', 'Pirazinamid']),
                'treatment_status' => 'Berjalan',
            ]
        );
    }
}
