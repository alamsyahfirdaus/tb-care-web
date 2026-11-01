<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreeningQuestionsSeeder extends Seeder
{
    public function run()
    {
        // ==== INDUK: Faktor Risiko Usia < 15 Tahun ====
        $idFaktorRisikoU15 = DB::table('screening_questions')->insertGetId([
            'screening_category_id' => 1,
            'group'                => 'Faktor Risiko',
            'group_id'             => null,
            'question'             => null,
            'ordering'             => 1
        ]);

        DB::table('screening_questions')->insert([
            ['group_id' => $idFaktorRisikoU15, 'question' => 'Pernah terdiagnosa/berobat TB?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoU15, 'question' => 'Pernah berobat TB tapi tidak tuntas?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoU15, 'question' => 'Kekurangan gizi?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoU15, 'question' => 'Riwayat DM / Kencing Manis?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoU15, 'question' => 'ODHIV?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoU15, 'question' => 'Lansia ≥ 65 tahun?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoU15, 'question' => 'Ibu Hamil?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoU15, 'question' => 'Tinggal di wilayah padat/kumuh/miskin?', 'is_critical' => false],
        ]);

        // ==== INDUK: Skrining Gejala Usia < 15 Tahun ====
        $idGejalaU15 = DB::table('screening_questions')->insertGetId([
            'screening_category_id' => 1,
            'group'                => 'Skrining Gejala',
            'group_id'             => null,
            'question'             => null,
            'ordering'             => 2
        ]);

        DB::table('screening_questions')->insert([
            ['group_id' => $idGejalaU15, 'question' => 'Batuk ≥ 2 minggu?', 'is_critical' => true],
            ['group_id' => $idGejalaU15, 'question' => 'Batuk darah?', 'is_critical' => true],
            ['group_id' => $idGejalaU15, 'question' => 'BB tidak naik dalam 2 bulan atau penurunan BB tanpa sebab jelas?', 'is_critical' => true],
            ['group_id' => $idGejalaU15, 'question' => 'Demam hilang timbul ≥ 2 minggu tanpa sebab jelas?', 'is_critical' => true],
            ['group_id' => $idGejalaU15, 'question' => 'Lesu atau malaise, anak kurang aktif bermain?', 'is_critical' => true],
        ]);

        // ==== INDUK: Faktor Risiko Usia ≥ 15 Tahun ====
        $idFaktorRisikoA15 = DB::table('screening_questions')->insertGetId([
            'screening_category_id' => 2,
            'group'                => 'Faktor Risiko',
            'group_id'             => null,
            'question'             => null,
            'ordering'             => 1
        ]);

        DB::table('screening_questions')->insert([
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Pernah terdiagnosa/berobat TB?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Pernah berobat TB tapi pernah tidak tuntas?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Kekurangan gizi?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Merokok?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Perokok pasif?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Riwayat DM/Kencing Manis?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'ODHIV?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Lansia ≥ 65 tahun?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Ibu hamil?', 'is_critical' => false],
            ['group_id' => $idFaktorRisikoA15, 'question' => 'Tinggal di wilayah padat kumuh miskin?', 'is_critical' => false],
        ]);

        // ==== INDUK: Skrining Gejala Usia ≥ 15 Tahun ====
        $idGejalaA15 = DB::table('screening_questions')->insertGetId([
            'screening_category_id' => 2,
            'group'                => 'Skrining Gejala',
            'group_id'             => null,
            'question'             => null,
            'ordering'             => 2
        ]);

        DB::table('screening_questions')->insert([
            ['group_id' => $idGejalaA15, 'question' => 'Batuk (semua bentuk batuk tanpa melihat durasi)?', 'is_critical' => true],
            ['group_id' => $idGejalaA15, 'question' => 'Batuk darah?', 'is_critical' => true],
            ['group_id' => $idGejalaA15, 'question' => 'Berat badan turun / tidak naik / nafsu makan turun?', 'is_critical' => false],
            ['group_id' => $idGejalaA15, 'question' => 'Demam hilang timbul tanpa sebab yang jelas?', 'is_critical' => false],
            ['group_id' => $idGejalaA15, 'question' => 'Berkeringat malam hari tanpa aktivitas?', 'is_critical' => false],
        ]);
    }
}
