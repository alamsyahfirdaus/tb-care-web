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
        $adminId = DB::table('users')->where('username', 'alamsyahfirdaus')->value('id') 
            ?? DB::table('users')->where('user_type_id', 1)->value('id');

        $materials = [
            [
                'title_material'  => 'Cara Cuci Tangan yang Benar',
                'description'     => 'Panduan langkah-langkah mencuci tangan untuk mencegah penyebaran penyakit.',
                'material_type'   => 'video',
                'video_url'       => 'https://www.youtube.com/watch?v=video_cuci_tangan',
                'image_path'      => null,
                'is_publish'      => true,
                'created_by'      => $adminId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'title_material'  => 'Pola Makan Sehat untuk Anak',
                'description'     => 'Infografis tentang makanan sehat dan bergizi untuk anak-anak.',
                'material_type'   => 'image',
                'image_path'      => 'pola_makan_sehat.jpg',
                'video_url'       => null,
                'is_publish'      => true,
                'created_by'      => $adminId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        foreach ($materials as $m) {
            DB::table('educational_materials')->updateOrInsert(
                ['title_material' => $m['title_material']],
                $m
            );
        }
    }
}
