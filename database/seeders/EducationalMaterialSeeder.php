<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationalMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('educational_materials')->insert([
            [
                'title_material' => 'Apa itu Tuberkulosis?',
                'description' => 'Penjelasan umum mengenai penyakit TBC, penyebab, dan penyebarannya.',
                'material_type' => 'url',
                'thumbnail' => 'thumbnails/tbc1.jpg',
                'material_url' => 'https://www.youtube.com/watch?v=contohTBC1',
                'material_file' => null,
                'is_publish' => true,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title_material' => 'Cara Minum Obat TBC dengan Benar',
                'description' => 'Panduan konsumsi obat TBC selama masa pengobatan.',
                'material_type' => 'file',
                'thumbnail' => 'thumbnails/obat.jpg',
                'material_file' => 'materials/minum-obat-tbc.pdf',
                'material_url' => null,
                'is_publish' => true,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title_material' => 'Mencegah Penularan TBC di Rumah',
                'description' => 'Tips dan langkah pencegahan penularan TBC dalam lingkungan keluarga.',
                'material_type' => 'url',
                'thumbnail' => 'thumbnails/pencegahan.jpg',
                'material_url' => 'https://www.youtube.com/watch?v=contohTBC2',
                'material_file' => null,
                'is_publish' => true,
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
