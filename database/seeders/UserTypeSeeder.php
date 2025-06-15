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
        DB::table('user_types')->insert([
            ['id' => 1, 'name' => 'Super Admin',   'description' => 'Akses penuh ke seluruh sistem TB Care'],
            ['id' => 2, 'name' => 'Admin Dinkes',  'description' => 'Pengelola data Dinas Kesehatan'],
            ['id' => 3, 'name' => 'Koordinator',   'description' => 'Petugas penghubung Puskesmas dan Dinkes'],
            ['id' => 4, 'name' => 'Pasien',        'description' => 'Pengguna yang menjalani pengobatan TB'],
        ]);
    }
}
