<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreeningCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('screening_categories')->insert([
            [
                'name' => 'GEJALA',
                'description' => 'Pertanyaan terkait gejala Tuberkulosis yang dialami pasien.',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'FAKTOR_RISIKO',
                'description' => 'Pertanyaan mengenai faktor risiko seperti merokok, diabetes, dan gizi buruk.',
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'KONTAK',
                'description' => 'Pernyataan terkait riwayat kontak dengan penderita TBC.',
                'order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ANTROPOMETRI',
                'description' => 'Data tinggi badan dan berat badan untuk menghitung status gizi.',
                'order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'RONTGEN',
                'description' => 'Evaluasi hasil pemeriksaan radiologi toraks.',
                'order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'UMUR',
                'description' => 'Klasifikasi atau evaluasi berdasarkan usia pasien.',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
