<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicationRecordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('medication_records')->insert([
            [
                'patient_treatment_id' => 1,
                // 'taken_at'             => Carbon::parse('2025-06-09 08:15:00'),
                'photo'                => '1_20250609.jpg',
                'is_verified'          => true,
                'late'                 => false,
                'notes'                => 'Minum obat tepat waktu, kondisi baik.',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]
        ]);
    }
}
