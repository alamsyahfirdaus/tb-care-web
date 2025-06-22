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
        DB::table('consultation_replies')->insert([
            [
                'consultation_id' => 1,
                'user_id'         => 1,
                'message'         => 'Terima kasih atas pertanyaannya. Batuk lebih dari 2 minggu perlu diperiksa ke Puskesmas untuk skrining TB.',
                'attachment'      => null,
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ],
            [
                'consultation_id' => 1,
                'user_id'         => 1,
                'message'         => 'Mual dan pusing merupakan efek samping awal yang umum. Jika berlanjut, segera konsultasikan ke dokter.',
                'attachment'      => 'attachments/efek_samping_obat.pdf',
                'created_at'      => Carbon::now()->subDays(1),
                'updated_at'      => Carbon::now()->subDays(1),
            ],
        ]);
    }
}
