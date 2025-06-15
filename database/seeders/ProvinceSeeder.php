<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('provinces')->insert([
            [
                'code' => '32',
                'name' => 'Jawa Barat',
                'description' => 'Provinsi Jawa Barat di Indonesia',
            ],
            [
                'code' => '31',
                'name' => 'DKI Jakarta',
                'description' => 'Provinsi ibu kota Indonesia',
            ],
            [
                'code' => '33',
                'name' => 'Jawa Tengah',
                'description' => 'Provinsi di tengah Pulau Jawa',
            ],
            [
                'code' => '34',
                'name' => 'DI Yogyakarta',
                'description' => 'Daerah Istimewa di Pulau Jawa',
            ],
            [
                'code' => '35',
                'name' => 'Jawa Timur',
                'description' => 'Provinsi di bagian timur Pulau Jawa',
            ],
        ]);
    }
}
