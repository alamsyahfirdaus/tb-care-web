<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
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
                'user_id' => 2, // Sesuaikan dengan ID user yang bertipe Pasien
                'address' => 'Jalan Sukasari No. 10',
                'subdistrict_id' => 41, // Sesuaikan dengan ID kecamatan yang sudah ada
                'occupation' => 'Buruh',
                'height' => 170,
                'weight' => 60,
                'blood_type' => 'O',
                'diagnosis_date' => '2024-05-20',
                'puskesmas_id' => 1, // ID puskesmas yang valid
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ]
        ]);
    }
}
