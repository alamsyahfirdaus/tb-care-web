<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Data untuk Screening ID 1 - Usia
        DB::table('categories')->insert([
            ['screening_id' => 1, 'category_name' => 'Usia 15 Tahun ke Atas', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 1, 'category_name' => 'Usia Di Bawah 15 Tahun', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Data untuk Screening ID 2 - Faktor Risiko
        DB::table('categories')->insert([
            ['screening_id' => 2, 'category_name' => 'Apakah Anda pernah didiagnosis atau menjalani pengobatan TBC sebelumnya?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda pernah menjalani pengobatan TBC tetapi tidak menyelesaikannya hingga tuntas?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda mengalami kekurangan gizi atau berat badan yang rendah?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda merupakan perokok aktif?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda sering terpapar asap rokok (sebagai perokok pasif)?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda memiliki riwayat diabetes melitus (DM) atau penyakit kencing manis?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda merupakan orang yang hidup dengan HIV/AIDS (ODHIV)?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 2, 'category_name' => 'Apakah usia Anda saat ini lebih dari 65 tahun (lansia)?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 2, 'category_name' => 'Apakah Anda saat ini sedang dalam kondisi hamil?', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Data untuk Screening ID 3 - Skrining Gejala
        DB::table('categories')->insert([
            ['screening_id' => 3, 'category_name' => 'Apakah Anda mengalami batuk, baik ringan maupun berat?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 3, 'category_name' => 'Apakah Anda mengalami batuk darah?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 3, 'category_name' => 'Apakah berat badan Anda menurun tanpa sebab yang jelas, atau apakah nafsu makan Anda berkurang?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 3, 'category_name' => 'Apakah Anda sering mengalami demam yang hilang timbul tanpa penyebab yang jelas?', 'created_at' => now(), 'updated_at' => now()],
            ['screening_id' => 3, 'category_name' => 'Apakah Anda sering berkeringat di malam hari tanpa adanya aktivitas fisik sebelumnya?', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
