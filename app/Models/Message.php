<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message_type',
        'message',
        'attachment_path',
        'attachment_mime_type',
        'attachment_size',
        'read_at',
    ];

    protected $casts = [
        'read_at'         => 'datetime',
        'attachment_size' => 'integer',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }

        return url('/api/image/' . $this->attachment_path);
    }
}
