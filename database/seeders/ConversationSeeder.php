<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $patientUser = User::where('user_type_id', 2)->first()
            ?? User::where('username', 'firdaus123')->first();

        $officerUser = User::where('user_type_id', 3)->first()
            ?? User::where('user_type_id', 1)->first();

        if (!$patientUser || !$officerUser) {
            return;
        }

        $conversation = Conversation::getOrCreateBetween($patientUser, $officerUser);

        $initialMsg = Message::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'message'         => 'Saya sudah batuk selama lebih dari 3 minggu, apakah saya perlu tes TB?',
            ],
            [
                'sender_id'       => $patientUser->id,
                'message_type'    => 'text',
                'attachment_path' => null,
                'read_at'         => Carbon::now()->subHours(2),
                'created_at'      => Carbon::now()->subHours(3),
                'updated_at'      => Carbon::now()->subHours(3),
            ]
        );

        $replyMsg = Message::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'message'         => 'Terima kasih atas pertanyaannya. Batuk lebih dari 2 minggu perlu diperiksa ke Puskesmas untuk skrining TB.',
            ],
            [
                'sender_id'       => $officerUser->id,
                'message_type'    => 'text',
                'attachment_path' => null,
                'read_at'         => null,
                'created_at'      => Carbon::now()->subHours(2),
                'updated_at'      => Carbon::now()->subHours(2),
            ]
        );

        $conversation->update([
            'last_message_id' => $replyMsg->id,
            'last_message_at' => $replyMsg->created_at,
        ]);
    }
}
