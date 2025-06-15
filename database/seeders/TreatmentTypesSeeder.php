<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatmentTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('treatment_types')->insert([
            [
                'treatment_type' => 'Kategori 1 (Pasien Baru)',
                'treatment_duration' => 6,
                'duration_unit' => 'bulan',
                'description' => 'Digunakan untuk pasien TBC baru tanpa riwayat pengobatan sebelumnya.',
            ],
            [
                'treatment_type' => 'Kategori 2 (Kasus Ulangan)',
                'treatment_duration' => 8,
                'duration_unit' => 'bulan',
                'description' => 'Digunakan untuk pasien yang pernah menjalani pengobatan TBC tetapi kambuh atau gagal.',
            ],
            [
                'treatment_type' => 'TBC Resistan Obat (RO)',
                'treatment_duration' => 20,
                'duration_unit' => 'bulan',
                'description' => 'Digunakan untuk kasus TBC yang resistan terhadap rifampisin atau beberapa obat lainnya.',
            ],
            [
                'treatment_type' => 'Pencegahan (TPT)',
                'treatment_duration' => 6,
                'duration_unit' => 'bulan',
                'description' => 'Terapi pencegahan TBC untuk orang yang memiliki risiko tinggi, misalnya kontak serumah.',
            ],
        ]);
    }
}
