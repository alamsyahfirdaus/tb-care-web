<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VillageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $villagesBySubdistrict = [
            // Cihideung (38)
            38 => ['Argasari', 'Cilembang', 'Nagarawangi', 'Tugujaya', 'Tuguraja', 'Yudanagara'],
            // Cipedes (39)
            39 => ['Cipedes', 'Nagarasari', 'Panglayungan', 'Sukamanah'],
            // Tawang (40)
            40 => ['Cikalang', 'Empangsari', 'Kahuripan', 'Lengkongsari', 'Tawangsari'],
            // Cibeureum (42)
            42 => ['Ciakar', 'Ciherang', 'Kersanagara', 'Kotabaru', 'Margabakti', 'Setianegara'],
            // Indihiang (43)
            43 => ['Indihiang', 'Panyingkiran', 'Parakannyasag', 'Sirnagalih', 'Sukamaju Kaler', 'Sukamaju Kidul'],
            // Mangkubumi (45)
            45 => ['Cigantang', 'Cipari', 'Cipawitra', 'Karikil', 'Linggajaya', 'Mangkubumi', 'Sambongjaya', 'Sambongpari'],
            // Bungursari (46)
            46 => ['Bungursari', 'Cibunigeulis', 'Sukajaya', 'Sukalaksana', 'Sukamulya', 'Sukarindik'],
            // Purbaratu (47)
            47 => ['Purbaratu', 'Singkup', 'Sukaasih', 'Sukajaya', 'Sukamenak', 'Sukanagara'],
        ];

        foreach ($villagesBySubdistrict as $subdistrictId => $villages) {
            foreach ($villages as $villageName) {
                $exists = DB::table('villages')
                    ->where('subdistrict_id', $subdistrictId)
                    ->where('name', $villageName)
                    ->exists();

                if (!$exists) {
                    DB::table('villages')->insert([
                        'name' => $villageName,
                        'subdistrict_id' => $subdistrictId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
