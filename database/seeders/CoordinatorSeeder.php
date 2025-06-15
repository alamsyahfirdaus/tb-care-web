<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoordinatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('coordinators')->insert([
            [
                'coord_type_id' => 1, // contoh: Penanggung Jawab TB
                'user_id' => 4,       // sesuaikan dengan ID dari tabel users
                'puskesmas_id' => 1,  // sesuaikan dengan ID dari tabel puskesmas
            ],
            [
                'coord_type_id' => 2, // contoh: Kader Puskesmas
                'user_id' => 5,       // sesuaikan dengan ID dari tabel users
                'puskesmas_id' => 1,
            ],
            // [
            //     'coord_type_id' => 1,
            //     'user_id' => 6,
            //     'puskesmas_id' => 2,
            // ],
            // [
            //     'coord_type_id' => 2,
            //     'user_id' => 7,
            //     'puskesmas_id' => 2,
            // ],
        ]);
    }
}
