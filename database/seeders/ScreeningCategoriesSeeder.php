<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreeningCategoriesSeeder extends Seeder
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
                'name' => 'Usia ≥ 15 Tahun',
                'min_age' => 15,
                'max_age' => null,
                'description' => 'Formulir untuk remaja dan dewasa'
            ],
            [
                'name' => 'Usia < 15 Tahun',
                'min_age' => 0,
                'max_age' => 14,
                'description' => 'Formulir untuk anak-anak'
            ],
        ]);
    }
}
