<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsultationReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $consultationId = DB::table('consultations')->value('id');
        $officerUserId = DB::table('users')->where('user_type_id', 3)->value('id') 
            ?? DB::table('users')->where('user_type_id', 1)->value('id');

        if (!$consultationId || !$officerUserId) {
            return;
        }

        $replies = [
            [
                'consultation_id' => $consultationId,
                'user_id'         => $officerUserId,
                'message'         => 'Terima kasih atas pertanyaannya. Batuk lebih dari 2 minggu perlu diperiksa ke Puskesmas untuk skrining TB.',
                'attachment'      => null,
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ],
            [
                'consultation_id' => $consultationId,
                'user_id'         => $officerUserId,
                'message'         => 'Mual dan pusing merupakan efek samping awal yang umum. Jika berlanjut, segera konsultasikan ke dokter.',
                'attachment'      => 'attachments/efek_samping_obat.pdf',
                'created_at'      => Carbon::now()->subDays(1),
                'updated_at'      => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($replies as $r) {
            DB::table('consultation_replies')->updateOrInsert(
                ['consultation_id' => $r['consultation_id'], 'message' => $r['message']],
                $r
            );
        }
    }
}
