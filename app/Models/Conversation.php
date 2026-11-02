<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'participant_one_id',
        'participant_two_id',
        'last_message_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function participantOne()
    {
        return $this->belongsTo(User::class, 'participant_one_id');
    }

    public function participantTwo()
    {
        return $this->belongsTo(User::class, 'participant_two_id');
    }

    public function lastMessage()
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    /**
     * Check if a given user is one of the participants.
     */
    public function isParticipant(User $user): bool
    {
        return (int) $this->participant_one_id === (int) $user->id
            || (int) $this->participant_two_id === (int) $user->id;
    }

    /**
     * Retrieve the other participant in this 2-way conversation.
     */
    public function getOtherParticipant(User $user): ?User
    {
        if ((int) $this->participant_one_id === (int) $user->id) {
            return $this->participantTwo;
        }

        if ((int) $this->participant_two_id === (int) $user->id) {
            return $this->participantOne;
        }

        return null;
    }

    /**
     * Scope to query conversation between two users strictly checking ordered participant IDs.
     */
    public function scopeBetween($query, $userA, $userB)
    {
        $idA = is_object($userA) ? (int) $userA->id : (int) $userA;
        $idB = is_object($userB) ? (int) $userB->id : (int) $userB;

        $p1 = min($idA, $idB);
        $p2 = max($idA, $idB);

        return $query->where('participant_one_id', $p1)
                     ->where('participant_two_id', $p2);
    }

    /**
     * Get or create a conversation between two users strictly enforcing ID ordering.
     */
    public static function getOrCreateBetween(User $userA, User $userB): self
    {
        $idA = (int) $userA->id;
        $idB = (int) $userB->id;

        $p1 = min($idA, $idB);
        $p2 = max($idA, $idB);

        return self::firstOrCreate([
            'participant_one_id' => $p1,
            'participant_two_id' => $p2,
        ]);
    }

    /**
     * Count unread messages for the specified user.
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
