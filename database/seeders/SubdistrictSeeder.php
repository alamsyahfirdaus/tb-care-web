<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubdistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('subdistricts')->insert([
            // Kabupaten Tasikmalaya 
            ['code' => '320601', 'name' => 'Cipatujah', 'district_id' => 18],
            ['code' => '320602', 'name' => 'Karangnunggal', 'district_id' => 18],
            ['code' => '320603', 'name' => 'Cikalong', 'district_id' => 18],
            ['code' => '320604', 'name' => 'Pancatengah', 'district_id' => 18],
            ['code' => '320605', 'name' => 'Cikatomas', 'district_id' => 18],
            ['code' => '320606', 'name' => 'Cibalong', 'district_id' => 18],
            ['code' => '320607', 'name' => 'Bantarkalong', 'district_id' => 18],
            ['code' => '320608', 'name' => 'Bojongasih', 'district_id' => 18],
            ['code' => '320609', 'name' => 'Culamega', 'district_id' => 18],
            ['code' => '320610', 'name' => 'Taraju', 'district_id' => 18],
            ['code' => '320611', 'name' => 'Salawu', 'district_id' => 18],
            ['code' => '320612', 'name' => 'Sodonghilir', 'district_id' => 18],
            ['code' => '320613', 'name' => 'Tanjungjaya', 'district_id' => 18],
            ['code' => '320614', 'name' => 'Salopa', 'district_id' => 18],
            ['code' => '320615', 'name' => 'Cineam', 'district_id' => 18],
            ['code' => '320616', 'name' => 'Manonjaya', 'district_id' => 18],
            ['code' => '320617', 'name' => 'Sukaraja', 'district_id' => 18],
            ['code' => '320618', 'name' => 'Jatiwaras', 'district_id' => 18],
            ['code' => '320619', 'name' => 'Singaparna', 'district_id' => 18],
            ['code' => '320620', 'name' => 'Cigalontang', 'district_id' => 18],
            ['code' => '320621', 'name' => 'Leuwisari', 'district_id' => 18],
            ['code' => '320622', 'name' => 'Karangjaya', 'district_id' => 18],
            ['code' => '320623', 'name' => 'Cisayong', 'district_id' => 18],
            ['code' => '320624', 'name' => 'Rajapolah', 'district_id' => 18],
            ['code' => '320625', 'name' => 'Jamanis', 'district_id' => 18],
            ['code' => '320626', 'name' => 'Ciawi', 'district_id' => 18],
            ['code' => '320627', 'name' => 'Sukaresik', 'district_id' => 18],
            ['code' => '320628', 'name' => 'Padakembang', 'district_id' => 18],
            ['code' => '320629', 'name' => 'Sariwangi', 'district_id' => 18],
            ['code' => '320630', 'name' => 'Sukaratu', 'district_id' => 18],
            ['code' => '320631', 'name' => 'Sukahening', 'district_id' => 18],
            ['code' => '320632', 'name' => 'Gunung Tanjung', 'district_id' => 18],
            ['code' => '320633', 'name' => 'Mangunreja', 'district_id' => 18],
            ['code' => '320634', 'name' => 'Puspahiang', 'district_id' => 18],
            ['code' => '320635', 'name' => 'Sukarame', 'district_id' => 18],
            ['code' => '320636', 'name' => 'Kadipaten', 'district_id' => 18],
            ['code' => '320637', 'name' => 'Bojonggambir', 'district_id' => 18],

            // Kota Tasikmalaya 
            ['code' => '327801', 'name' => 'Cihideung', 'district_id' => 27],
            ['code' => '327802', 'name' => 'Cipedes', 'district_id' => 27],
            ['code' => '327803', 'name' => 'Tawang', 'district_id' => 27],
            ['code' => '327804', 'name' => 'Kawalu', 'district_id' => 27],
            ['code' => '327805', 'name' => 'Cibeureum', 'district_id' => 27],
            ['code' => '327806', 'name' => 'Indihiang', 'district_id' => 27],
            ['code' => '327807', 'name' => 'Tamansari', 'district_id' => 27],
            ['code' => '327808', 'name' => 'Mangkubumi', 'district_id' => 27],
            ['code' => '327809', 'name' => 'Bungursari', 'district_id' => 27],
            ['code' => '327810', 'name' => 'Purbaratu', 'district_id' => 27],
        ]);
    }
}
