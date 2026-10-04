<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class SystemNotification extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'system_notifications';
    protected $guarded = [];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'target_puskesmas_id');
    }
}
