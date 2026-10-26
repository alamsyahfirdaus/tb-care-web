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
        $userId = DB::table('users')->where('username', 'firdaus123')->value('id') ?? 2;
        $subdistrictId = DB::table('subdistricts')->value('id') ?? 41;
        $puskesmasId = DB::table('puskesmas')->value('id') ?? 1;

        DB::table('patients')->updateOrInsert(
            ['nik' => '3273053107980001'],
            [
                'user_id'              => $userId,
                'address'              => 'Jalan Sukasari No. 10',
                'subdistrict_id'       => $subdistrictId,
                'occupation'           => 'Buruh',
                'height'               => 170,
                'weight'               => 60,
                'blood_type'           => 'O',
                'diagnosis_date'       => '2024-05-20',
                'treatment_start_date' => '2024-05-21',
                'puskesmas_id'         => $puskesmasId,
                'updated_at'           => Carbon::now(),
                'created_at'           => Carbon::now(),
            ]
        );
    }
}
