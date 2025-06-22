<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('officers')->insert([
            // Dinkes Provinsi
            [
                'user_id'         => 2, // Admin Dinkes Provinsi
                'officer_type_id' => 1,
                'district_id'     => 1, // Misalnya ID District Provinsi
                'puskesmas_id'    => null,
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ],
            // Dinkes Kab/Kota
            [
                'user_id'         => 3, // Admin Dinkes Kab/Kota
                'officer_type_id' => 2,
                'district_id'     => 2, // Misalnya ID District Kota Tasikmalaya
                'puskesmas_id'    => null,
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ],
            // PJTB Puskesmas
            [
                'user_id'         => 4, // PJTB
                'officer_type_id' => 3,
                'district_id'     => null,
                'puskesmas_id'    => 1, // Misalnya ID Puskesmas Singaparna
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ],
            // Kader Puskesmas
            [
                'user_id'         => 5, // Kader
                'officer_type_id' => 4,
                'district_id'     => null,
                'puskesmas_id'    => 1, // Sama dengan atas (satu puskesmas)
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ],
        ]);
    }
}
