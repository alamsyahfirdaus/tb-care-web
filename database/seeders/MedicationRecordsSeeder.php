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
                'photo' => 'photos/medication_1.jpg',
                'taken_at' => Carbon::parse('2025-06-01 07:00:00'),
            ],
            // [
            //     'patient_treatment_id' => 1,
            //     'photo' => 'photos/medication_2.jpg',
            //     'taken_at' => Carbon::parse('2025-06-02 07:05:00'),
            // ],
            // [
            //     'patient_treatment_id' => 2,
            //     'photo' => 'photos/medication_3.jpg',
            //     'taken_at' => Carbon::parse('2025-05-15 08:00:00'),
            // ],
            // [
            //     'patient_treatment_id' => 3,
            //     'photo' => 'photos/medication_4.jpg',
            //     'taken_at' => Carbon::parse('2025-04-01 06:45:00'),
            // ],
        ]);
    }
}
