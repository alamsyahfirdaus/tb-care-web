<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NutritionRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // $now = now();

        $rules = [
            // Anak usia 0–60 bulan, BB/TB
            ['min_age' => 0, 'max_age' => 60, 'index' => 'BB/TB', 'category' => 'Gizi Buruk',   'min' => -999, 'max' => -3.0],
            ['min_age' => 0, 'max_age' => 60, 'index' => 'BB/TB', 'category' => 'Gizi Kurang',  'min' => -3.0, 'max' => -2.0],
            ['min_age' => 0, 'max_age' => 60, 'index' => 'BB/TB', 'category' => 'Normal',       'min' => -2.0, 'max' => 1.0],
            ['min_age' => 0, 'max_age' => 60, 'index' => 'BB/TB', 'category' => 'Risiko Lebih', 'min' => 1.0,  'max' => 2.0],
            ['min_age' => 0, 'max_age' => 60, 'index' => 'BB/TB', 'category' => 'Overweight',   'min' => 2.0,  'max' => 3.0],
            ['min_age' => 0, 'max_age' => 60, 'index' => 'BB/TB', 'category' => 'Obesitas',     'min' => 3.0,  'max' => 99.0],

            // Anak usia 60–216 bulan, IMT/U
            ['min_age' => 60, 'max_age' => 216, 'index' => 'IMT/U', 'category' => 'Gizi Buruk',  'min' => -999, 'max' => -3.0],
            ['min_age' => 60, 'max_age' => 216, 'index' => 'IMT/U', 'category' => 'Gizi Kurang', 'min' => -3.0, 'max' => -2.0],
            ['min_age' => 60, 'max_age' => 216, 'index' => 'IMT/U', 'category' => 'Normal',      'min' => -2.0, 'max' => 1.0],
            ['min_age' => 60, 'max_age' => 216, 'index' => 'IMT/U', 'category' => 'Overweight',  'min' => 1.0,  'max' => 2.0],
            ['min_age' => 60, 'max_age' => 216, 'index' => 'IMT/U', 'category' => 'Obesitas',    'min' => 2.0,  'max' => 99.0],

            // Dewasa > 18 tahun, IMT
            ['min_age' => 216, 'max_age' => null, 'index' => 'IMT', 'category' => 'Sangat Kurus', 'min' => 0.0,   'max' => 17.0],
            ['min_age' => 216, 'max_age' => null, 'index' => 'IMT', 'category' => 'Kurus',        'min' => 17.0,  'max' => 18.5],
            ['min_age' => 216, 'max_age' => null, 'index' => 'IMT', 'category' => 'Normal',       'min' => 18.5,  'max' => 25.0],
            ['min_age' => 216, 'max_age' => null, 'index' => 'IMT', 'category' => 'Gemuk',        'min' => 25.0,  'max' => 27.0],
            ['min_age' => 216, 'max_age' => null, 'index' => 'IMT', 'category' => 'Obesitas',     'min' => 27.0,  'max' => 99.0],
        ];

        foreach ($rules as $rule) {
            DB::table('nutrition_rules')->insert([
                'min_age_months' => $rule['min_age'],
                'max_age_months' => $rule['max_age'],
                'index_type'     => $rule['index'],
                'category'       => $rule['category'],
                'z_score_min'    => $rule['min'],
                'z_score_max'    => $rule['max'],
                // 'description'    => null,
                // 'created_at'     => $now,
                // 'updated_at'     => $now,
            ]);
        }
    }
}
