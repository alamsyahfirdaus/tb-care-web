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
        $subdistrictsMap = [
            'Cihideung'   => ['Argasari', 'Cilembang', 'Nagarawangi', 'Tugujaya', 'Tuguraja', 'Yudanagara'],
            'Cipedes'     => ['Cipedes', 'Nagarasari', 'Panglayungan', 'Sukamanah'],
            'Tawang'      => ['Cikalang', 'Empangsari', 'Kahuripan', 'Lengkongsari', 'Tawangsari'],
            'Kawalu'      => ['Talagasari', 'Cilamajang', 'Karsamenak', 'Gunungtandala', 'Karanganyar', 'Urug', 'Cibeuti', 'Saguling', 'Leuwiliang'],
            'Cibeureum'   => ['Ciakar', 'Ciherang', 'Kersanagara', 'Kotabaru', 'Margabakti', 'Setianegara'],
            'Indihiang'   => ['Indihiang', 'Panyingkiran', 'Parakannyasag', 'Sirnagalih', 'Sukamaju Kaler', 'Sukamaju Kidul'],
            'Tamansari'   => ['Setiamulya', 'Setiawargi', 'Sukahurip', 'Sukamaju', 'Sukalaksana', 'Sumelap', 'Tamanjaya', 'Mugarsari'],
            'Mangkubumi'  => ['Cigantang', 'Cipari', 'Cipawitra', 'Karikil', 'Linggajaya', 'Mangkubumi', 'Sambongjaya', 'Sambongpari'],
            'Bungursari'  => ['Bungursari', 'Cibunigeulis', 'Sukajaya', 'Sukalaksana', 'Sukamulya', 'Sukarindik'],
            'Purbaratu'   => ['Purbaratu', 'Singkup', 'Sukaasih', 'Sukajaya', 'Sukamenak', 'Sukanagara'],
        ];

        foreach ($subdistrictsMap as $subdistrictName => $villages) {
            $subdistrictId = DB::table('subdistricts')->where('name', $subdistrictName)->value('id');
            if (!$subdistrictId) {
                continue;
            }

            foreach ($villages as $villageName) {
                $exists = DB::table('villages')
                    ->where('subdistrict_id', $subdistrictId)
                    ->where('name', $villageName)
                    ->exists();

                if (!$exists) {
                    DB::table('villages')->insert([
                        'name'           => $villageName,
                        'subdistrict_id' => $subdistrictId,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }
        }
    }
}
