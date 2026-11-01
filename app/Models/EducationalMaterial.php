<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\HasEncryptedId;

class EducationalMaterial extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'educational_materials';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'is_publish'        => 'boolean',
        'notification_sent' => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEmbedUrlAttribute()
    {
        if (!$this->video_url) return null;
        
        $url = $this->video_url;
        // Check for youtu.be/ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_\-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
        // Check for youtube.com/watch?v=ID
        if (preg_match('/[?&]v=([a-zA-Z0-9_\-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
        // If already embed
        if (str_contains($url, 'youtube.com/embed/')) {
            return $url;
        }

        return $url;
    }

    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            if (str_starts_with($this->image_path, 'http')) {
                return $this->image_path;
            }
            if (file_exists(public_path('storage/' . $this->image_path))) {
                return asset('storage/' . $this->image_path);
            }
            if (file_exists(public_path('images/' . $this->image_path))) {
                return asset('images/' . $this->image_path);
            }
            if (file_exists(public_path($this->image_path))) {
                return asset($this->image_path);
            }
            if (file_exists(public_path('assets/images/' . $this->image_path))) {
                return asset('assets/images/' . $this->image_path);
            }
            return asset('storage/' . $this->image_path);
        }
        return null;
    }

    /**
     * Memicu notifikasi materi edukasi baru ke pasien TB Care
     * Hanya dikirim jika materi berstatus publish dan belum pernah dikirim notifikasi.
     */
    public function sendPublishNotification(): bool
    {
        if (!$this->is_publish || $this->notification_sent) {
            return false;
        }

        // Kunci status notifikasi terlebih dahulu agar tidak terjadi duplikasi
        $this->notification_sent = true;
        $this->saveQuietly();

        $tokens = User::whereNotNull('fcm_token')
            ->where('user_type_id', 2) // Pasien TB Care
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->toArray();

        $title = 'Materi Edukasi Baru';
        $body  = "Ada materi edukasi baru untuk Anda: {$this->title_material}";
        $data  = [
            'type'         => 'education',
            'material_id'  => (string) $this->id,
            'education_id' => (string) $this->id,
        ];

        // 1. Simpan catatan ke Notification Center / System Notifications
        try {
            SystemNotification::create([
                'title'                => $title,
                'message'              => $body,
                'type'                 => 'Edukasi',
                'target_role'          => 'Pasien',
                'target_puskesmas_id'  => null,
                'sent_by'              => $this->created_by ?? (Auth::check() ? Auth::id() : 1),
                'sent_count'           => count($tokens),
                'status'               => 'Terkirim',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mencatat SystemNotification edukasi: ' . $e->getMessage());
        }

        // 2. Kirim Push Notification via FCM
        if (!empty($tokens)) {
            try {
                \App\Services\FcmService::sendNotification($tokens, $title, $body, $data);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Gagal mengirim FCM edukasi: ' . $e->getMessage());
            }
        }

        return true;
    }

    public static function getAllMaterials()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->user_type_id == 1) {
                return self::with('author')->orderBy('id', 'desc')->get();
            } else {
                return self::with('author')->where('created_by', $user->id)->orderBy('id', 'desc')->get();
            }
        }

        return self::with('author')->where('is_publish', 1)->orderBy('id', 'desc')->paginate(12);
    }

    public static function getMaterialById($id)
    {
        return self::with('author')->find($id);
    }
}
