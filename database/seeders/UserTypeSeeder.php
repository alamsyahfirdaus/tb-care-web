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
        $types = [
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
        ];

        foreach ($types as $type) {
            DB::table('user_types')->updateOrInsert(['id' => $type['id']], $type);
        }
    }
}
