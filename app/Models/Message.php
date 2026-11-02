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
        'edited_at',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'read_at'         => 'datetime',
        'edited_at'       => 'datetime',
        'deleted_at'      => 'datetime',
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

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function getIsDeletedAttribute(): bool
    {
        return $this->deleted_at !== null;
    }

    public function getIsEditedAttribute(): bool
    {
        return $this->edited_at !== null;
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if ($this->deleted_at !== null || !$this->attachment_path) {
            return null;
        }

        return url('/api/image/' . $this->attachment_path);
    }
}
