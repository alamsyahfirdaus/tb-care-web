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
        DB::table('patient_treatments')->insert([
            [
                'patient_id' => 1,
                'treatment_type_id' => 1, // Kategori 1
                'diagnosis_date' => Carbon::parse('2024-12-01'),
                'start_date' => Carbon::parse('2024-12-05'),
                'end_date' => Carbon::parse('2025-06-05'),
                'treatment_days' => 100,
                'medication_time' => '07:00:00',
                'prescription' => json_encode(['Rifampisin', 'Isoniazid', 'Pirazinamid']),
                'treatment_status' => 'Berjalan',
            ],
        ]);
    }
}
