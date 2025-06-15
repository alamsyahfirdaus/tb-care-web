<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('districts')->insert([
            // Kabupaten di Jawa Barat
            ['code' => null, 'name' => 'Kabupaten Bandung', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Bandung Barat', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Bekasi', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Bogor', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Ciamis', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Cianjur', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Cirebon', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Garut', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Indramayu', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Karawang', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Kuningan', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Majalengka', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Pangandaran', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Purwakarta', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Subang', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Sukabumi', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Sumedang', 'province_id' => 1],
            ['code' => null, 'name' => 'Kabupaten Tasikmalaya', 'province_id' => 1],

            // Kota di Jawa Barat
            ['code' => null, 'name' => 'Kota Bandung', 'province_id' => 1],
            ['code' => null, 'name' => 'Kota Banjar', 'province_id' => 1],
            ['code' => null, 'name' => 'Kota Bekasi', 'province_id' => 1],
            ['code' => null, 'name' => 'Kota Bogor', 'province_id' => 1],
            ['code' => null, 'name' => 'Kota Cimahi', 'province_id' => 1],
            ['code' => null, 'name' => 'Kota Cirebon', 'province_id' => 1],
            ['code' => null, 'name' => 'Kota Depok', 'province_id' => 1],
            ['code' => null, 'name' => 'Kota Sukabumi', 'province_id' => 1],
            ['code' => null, 'name' => 'Kota Tasikmalaya', 'province_id' => 1],
        ]);
    }
}
