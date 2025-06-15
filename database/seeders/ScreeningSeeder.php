<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreeningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('screenings')->insert([
            ['screening_category' => 'Usia'],
            ['screening_category' => 'Faktor Risiko'],
            ['screening_category' => 'Skrining Gejala'],
        ]);
    }
}
