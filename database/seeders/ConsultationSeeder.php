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
        $patientUserId = DB::table('users')->where('user_type_id', 2)->value('id') 
            ?? DB::table('users')->where('username', 'firdaus123')->value('id');

        if (!$patientUserId) {
            return;
        }

        $consultations = [
            [
                'user_id'     => $patientUserId,
                'title'       => 'Batuk berkepanjangan',
                'message'     => 'Saya sudah batuk selama lebih dari 3 minggu, apakah saya perlu tes TB?',
                'is_answered' => false,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ],
            [
                'user_id'     => $patientUserId,
                'title'       => 'Efek samping obat',
                'message'     => 'Setelah minum obat TB, saya merasa mual dan pusing. Apakah ini normal?',
                'is_answered' => true,
                'created_at'  => Carbon::now()->subDays(1),
                'updated_at'  => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($consultations as $c) {
            DB::table('consultations')->updateOrInsert(
                ['title' => $c['title'], 'user_id' => $c['user_id']],
                $c
            );
        }
    }
}
