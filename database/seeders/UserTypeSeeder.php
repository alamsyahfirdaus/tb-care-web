<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('user_types')->insert(
            [
                [
                    'id' => 1,
                    'name' => 'Administrator',
                    'description' => 'Akses penuh terhadap sistem dan pengaturan.'
                ],
                [
                    'id' => 2,
                    'name' => 'Pasien',
                    'description' => 'Pengguna yang menjalani pengobatan TB.'
                ],
                [
                    'id' => 3,
                    'name' => 'Petugas',
                    'description' => 'Pengguna dari instansi kesehatan (Dinkes, PJTB, Kader) yang membantu penanganan pasien.'
                ],
            ]
        );
    }
}
