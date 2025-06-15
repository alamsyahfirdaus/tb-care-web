<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('patients')->insert([
            [
                'nik' => '3273053107980001',
                'user_id' => 6, // Sesuaikan dengan ID user yang bertipe Pasien
                'address' => 'Jalan Sukasari No. 10',
                'subdistrict_id' => 41, // Sesuaikan dengan ID kecamatan yang sudah ada
                'occupation' => 'Buruh',
                'height' => 170,
                'weight' => 60,
                'blood_type' => 'O',
                'diagnosis_date' => '2024-05-20',
                'puskesmas_id' => 1, // ID puskesmas yang valid
            ],
            // [
            //     'nik' => '3273051201990002',
            //     'user_id' => 7,
            //     'address' => 'Perum Griya Asri Blok C',
            //     'subdistrict_id' => 102,
            //     'occupation' => 'Buruh',
            //     'height' => 165,
            //     'weight' => 55,
            //     'blood_type' => 'A',
            //     'diagnosis_date' => '2023-11-05',
            //     'puskesmas_id' => 2,
            // ],
        ]);
    }
}
