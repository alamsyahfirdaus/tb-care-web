<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membersihkan percakapan kosong (conversations tanpa record di messages).
     */
    public function up(): void
    {
        $emptyConversations = DB::select('
            SELECT c.id, c.participant_one_id, c.participant_two_id, c.created_at
            FROM conversations c
            LEFT JOIN messages m ON m.conversation_id = c.id
            WHERE m.id IS NULL
        ');

        $count = count($emptyConversations);
        if ($count > 0) {
            $ids = array_map(fn($row) => $row->id, $emptyConversations);
            DB::table('conversations')->whereIn('id', $ids)->delete();
            Log::info("Cleanup Empty Conversations: {$count} percakapan kosong berhasil dibersihkan.", ['ids' => $ids]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op karena percakapan kosong yang dibersihkan memang tidak memiliki data riwayat pesan.
    }
};
