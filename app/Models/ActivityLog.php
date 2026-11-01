<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log($activity, $module, $description = null)
    {
        try {
            $user = auth()->user();
            return self::create([
                'user_id'     => $user ? $user->id : null,
                'user_name'   => $user ? $user->name : 'Sistem / Tamu',
                'activity'    => $activity,
                'module'      => $module,
                'description' => $description,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Fail silently so logging never breaks core requests
            return null;
        }
    }
}
