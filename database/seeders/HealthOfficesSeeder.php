<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HealthOfficesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('health_offices')->insert([
            [
                'office_type_id' => 1, // 1 = Provinsi
                'office_address' => 'Jl. Diponegoro No. 1, Bandung',
                'district_id' => 1, // ID dari district Kota Bandung (sesuaikan dengan seeder districts)
                'office_phone' => '022-1234567',
                'office_email' => 'prov.dinkes@jabar.go.id',
                'user_id' => 2, // Admin Dinkes Provinsi
            ],
            [
                'office_type_id' => 2, // 2 = Kabupaten/Kota
                'office_address' => 'Jl. Siliwangi No. 12, Tasikmalaya',
                'district_id' => 2, // ID dari district Kab. Tasikmalaya
                'office_phone' => '0265-7654321',
                'office_email' => 'kota.dinkes@tasik.go.id',
                'user_id' => 3, // Admin Dinkes Kab/Kota
            ],
        ]);
    }
}
