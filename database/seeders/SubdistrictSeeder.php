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
        $kabTasikId = DB::table('districts')->where('name', 'Kabupaten Tasikmalaya')->value('id') ?? 18;
        $kotaTasikId = DB::table('districts')->where('name', 'Kota Tasikmalaya')->value('id') ?? 27;

        $subdistricts = [
            // Kabupaten Tasikmalaya 
            ['code' => '320601', 'name' => 'Cipatujah', 'district_id' => $kabTasikId],
            ['code' => '320602', 'name' => 'Karangnunggal', 'district_id' => $kabTasikId],
            ['code' => '320603', 'name' => 'Cikalong', 'district_id' => $kabTasikId],
            ['code' => '320604', 'name' => 'Pancatengah', 'district_id' => $kabTasikId],
            ['code' => '320605', 'name' => 'Cikatomas', 'district_id' => $kabTasikId],
            ['code' => '320606', 'name' => 'Cibalong', 'district_id' => $kabTasikId],
            ['code' => '320607', 'name' => 'Bantarkalong', 'district_id' => $kabTasikId],
            ['code' => '320608', 'name' => 'Bojongasih', 'district_id' => $kabTasikId],
            ['code' => '320609', 'name' => 'Culamega', 'district_id' => $kabTasikId],
            ['code' => '320610', 'name' => 'Taraju', 'district_id' => $kabTasikId],
            ['code' => '320611', 'name' => 'Salawu', 'district_id' => $kabTasikId],
            ['code' => '320612', 'name' => 'Sodonghilir', 'district_id' => $kabTasikId],
            ['code' => '320613', 'name' => 'Tanjungjaya', 'district_id' => $kabTasikId],
            ['code' => '320614', 'name' => 'Salopa', 'district_id' => $kabTasikId],
            ['code' => '320615', 'name' => 'Cineam', 'district_id' => $kabTasikId],
            ['code' => '320616', 'name' => 'Manonjaya', 'district_id' => $kabTasikId],
            ['code' => '320617', 'name' => 'Sukaraja', 'district_id' => $kabTasikId],
            ['code' => '320618', 'name' => 'Jatiwaras', 'district_id' => $kabTasikId],
            ['code' => '320619', 'name' => 'Singaparna', 'district_id' => $kabTasikId],
            ['code' => '320620', 'name' => 'Cigalontang', 'district_id' => $kabTasikId],
            ['code' => '320621', 'name' => 'Leuwisari', 'district_id' => $kabTasikId],
            ['code' => '320622', 'name' => 'Karangjaya', 'district_id' => $kabTasikId],
            ['code' => '320623', 'name' => 'Cisayong', 'district_id' => $kabTasikId],
            ['code' => '320624', 'name' => 'Rajapolah', 'district_id' => $kabTasikId],
            ['code' => '320625', 'name' => 'Jamanis', 'district_id' => $kabTasikId],
            ['code' => '320626', 'name' => 'Ciawi', 'district_id' => $kabTasikId],
            ['code' => '320627', 'name' => 'Sukaresik', 'district_id' => $kabTasikId],
            ['code' => '320628', 'name' => 'Padakembang', 'district_id' => $kabTasikId],
            ['code' => '320629', 'name' => 'Sariwangi', 'district_id' => $kabTasikId],
            ['code' => '320630', 'name' => 'Sukaratu', 'district_id' => $kabTasikId],
            ['code' => '320631', 'name' => 'Sukahening', 'district_id' => $kabTasikId],
            ['code' => '320632', 'name' => 'Gunung Tanjung', 'district_id' => $kabTasikId],
            ['code' => '320633', 'name' => 'Mangunreja', 'district_id' => $kabTasikId],
            ['code' => '320634', 'name' => 'Puspahiang', 'district_id' => $kabTasikId],
            ['code' => '320635', 'name' => 'Sukarame', 'district_id' => $kabTasikId],
            ['code' => '320636', 'name' => 'Kadipaten', 'district_id' => $kabTasikId],
            ['code' => '320637', 'name' => 'Bojonggambir', 'district_id' => $kabTasikId],

            // Kota Tasikmalaya 
            ['code' => '327801', 'name' => 'Cihideung', 'district_id' => $kotaTasikId],
            ['code' => '327802', 'name' => 'Cipedes', 'district_id' => $kotaTasikId],
            ['code' => '327803', 'name' => 'Tawang', 'district_id' => $kotaTasikId],
            ['code' => '327804', 'name' => 'Kawalu', 'district_id' => $kotaTasikId],
            ['code' => '327805', 'name' => 'Cibeureum', 'district_id' => $kotaTasikId],
            ['code' => '327806', 'name' => 'Indihiang', 'district_id' => $kotaTasikId],
            ['code' => '327807', 'name' => 'Tamansari', 'district_id' => $kotaTasikId],
            ['code' => '327808', 'name' => 'Mangkubumi', 'district_id' => $kotaTasikId],
            ['code' => '327809', 'name' => 'Bungursari', 'district_id' => $kotaTasikId],
            ['code' => '327810', 'name' => 'Purbaratu', 'district_id' => $kotaTasikId],
        ];

        foreach ($subdistricts as $s) {
            DB::table('subdistricts')->updateOrInsert(['code' => $s['code']], $s);
        }
    }
}
