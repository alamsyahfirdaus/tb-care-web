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
                'medication_time' => '07:00:00',
                'prescription' => json_encode(['Rifampisin', 'Isoniazid', 'Pirazinamid']),
                'treatment_status' => 1, // Berjalan
            ],
            // [
            //     'patient_id' => 2,
            //     'treatment_type_id' => 2, // Kategori 2
            //     'diagnosis_date' => Carbon::parse('2024-10-15'),
            //     'start_date' => Carbon::parse('2024-10-20'),
            //     'end_date' => Carbon::parse('2025-06-20'),
            //     'medication_time' => '08:00:00',
            //     'prescription' => json_encode(['Rifampisin', 'Isoniazid', 'Etambutol']),
            //     'treatment_status' => 2, // Selesai
            // ],
            // [
            //     'patient_id' => 3,
            //     'treatment_type_id' => 3, // RO
            //     'diagnosis_date' => Carbon::parse('2023-01-01'),
            //     'start_date' => Carbon::parse('2023-01-05'),
            //     'end_date' => Carbon::parse('2025-09-01'),
            //     'medication_time' => '06:30:00',
            //     'prescription' => json_encode(['Bedaquiline', 'Linezolid']),
            //     'treatment_status' => 3, // Gagal
            // ],
        ]);
    }
}
