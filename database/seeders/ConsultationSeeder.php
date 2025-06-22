<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsultationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('consultations')->insert([
            [
                'user_id'       => 1,
                'title'         => 'Batuk berkepanjangan',
                'message'       => 'Saya sudah batuk selama lebih dari 3 minggu, apakah saya perlu tes TB?',
                'is_answered'    => false, // pending
                'created_at'    => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id'   => 2,
                'title'     => 'Efek samping obat',
                'message'   => 'Setelah minum obat TB, saya merasa mual dan pusing. Apakah ini normal?',
                'is_answered'    => true, // answered
                'created_at' => Carbon::now()->subDays(1),
                'updated_at' => Carbon::now()->subDays(1),
            ],
        ]);
    }
}
