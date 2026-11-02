<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateConversationsAndMessagesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('participant_one_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('participant_two_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('last_message_id')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamps();

                $table->unique(['participant_one_id', 'participant_two_id']);
                $table->index(['participant_one_id', 'participant_two_id']);
            });
        }

        if (!Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->string('message_type', 20)->default('text'); // text, image
                $table->text('message')->nullable();
                $table->string('attachment_path', 255)->nullable();
                $table->string('attachment_mime_type', 100)->nullable();
                $table->unsignedInteger('attachment_size')->nullable();
                $table->timestamp('read_at')->nullable()->index();
                $table->timestamps();

                $table->index(['conversation_id', 'created_at']);
                $table->index(['sender_id', 'created_at']);
            });

            // Foreign key for last_message_id on conversations
            Schema::table('conversations', function (Blueprint $table) {
                $table->foreign('last_message_id')->references('id')->on('messages')->nullOnDelete();
            });
        }

        // Migrate historical data from consultations and consultation_replies if available
        $this->migrateOldConsultationData();
    }

    /**
     * Migrate existing consultation and reply records safely into conversations and messages.
     */
    protected function migrateOldConsultationData()
    {
        if (!Schema::hasTable('consultations') || !Schema::hasTable('consultation_replies')) {
            return;
        }

        $oldConsultations = DB::table('consultations')->orderBy('id', 'asc')->get();

        foreach ($oldConsultations as $consultation) {
            $senderId = (int) $consultation->user_id;
            $recipientId = $consultation->recipient_id ? (int) $consultation->recipient_id : null;

            // If recipient is null, try to find other user from replies
            if (!$recipientId) {
                $otherUserReply = DB::table('consultation_replies')
                    ->where('consultation_id', $consultation->id)
                    ->where('user_id', '!=', $senderId)
                    ->orderBy('id', 'asc')
                    ->first();

                if ($otherUserReply) {
                    $recipientId = (int) $otherUserReply->user_id;
                }
            }

            // Skip if no recipient can be identified (standalone incomplete record)
            if (!$recipientId || $senderId === $recipientId) {
                continue;
            }

            $p1 = min($senderId, $recipientId);
            $p2 = max($senderId, $recipientId);

            // Find or create conversation
            $conversation = DB::table('conversations')
                ->where('participant_one_id', $p1)
                ->where('participant_two_id', $p2)
                ->first();

            $conversationId = null;
            if ($conversation) {
                $conversationId = $conversation->id;
            } else {
                $conversationId = DB::table('conversations')->insertGetId([
                    'participant_one_id' => $p1,
                    'participant_two_id' => $p2,
                    'last_message_id'    => null,
                    'last_message_at'    => $consultation->updated_at ?? $consultation->created_at,
                    'created_at'         => $consultation->created_at ?? now(),
                    'updated_at'         => $consultation->updated_at ?? now(),
                ]);
            }

            // Insert initial message
            $initialMessageId = DB::table('messages')->insertGetId([
                'conversation_id'      => $conversationId,
                'sender_id'            => $senderId,
                'message_type'         => $consultation->attachment ? 'image' : 'text',
                'message'              => $consultation->message,
                'attachment_path'      => $consultation->attachment,
                'attachment_mime_type' => $consultation->attachment ? 'image/jpeg' : null,
                'attachment_size'      => null,
                'read_at'              => $consultation->is_answered ? ($consultation->updated_at ?? now()) : null,
                'created_at'           => $consultation->created_at ?? now(),
                'updated_at'           => $consultation->updated_at ?? now(),
            ]);

            $lastMsgId = $initialMessageId;
            $lastMsgAt = $consultation->created_at ?? now();

            // Insert all replies
            $replies = DB::table('consultation_replies')
                ->where('consultation_id', $consultation->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($replies as $reply) {
                $replyMsgId = DB::table('messages')->insertGetId([
                    'conversation_id'      => $conversationId,
                    'sender_id'            => (int) $reply->user_id,
                    'message_type'         => $reply->attachment ? 'image' : 'text',
                    'message'              => $reply->message,
                    'attachment_path'      => $reply->attachment,
                    'attachment_mime_type' => $reply->attachment ? 'image/jpeg' : null,
                    'attachment_size'      => null,
                    'read_at'              => $reply->is_read ? ($reply->updated_at ?? now()) : null,
                    'created_at'           => $reply->created_at ?? now(),
                    'updated_at'           => $reply->updated_at ?? now(),
                ]);
                $lastMsgId = $replyMsgId;
                $lastMsgAt = $reply->created_at ?? now();
            }

            // Update conversation last_message pointer
            DB::table('conversations')
                ->where('id', $conversationId)
                ->update([
                    'last_message_id' => $lastMsgId,
                    'last_message_at' => $lastMsgAt,
                    'updated_at'      => $lastMsgAt,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropForeign(['last_message_id']);
            });
        }
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
}
