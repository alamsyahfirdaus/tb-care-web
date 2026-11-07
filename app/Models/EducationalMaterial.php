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

    public function reads()
    {
        return $this->hasMany(EducationalMaterialRead::class, 'educational_material_id');
    }

    public function isReadBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return $this->reads()->where('user_id', $user->id)->exists();
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
     * Memicu notifikasi materi edukasi baru ke kader dan pasien TB Care yang aktif.
     * Mengirim Database Notification (in-app) dan Push Notification via FCM.
     * Hanya dikirim jika materi berstatus publish dan belum pernah dikirim notifikasi (Idempotent).
     */
    public function sendPublishNotification(): bool
    {
        if (!$this->is_publish || $this->notification_sent) {
            return false;
        }

        // Kunci status notifikasi terlebih dahulu agar tidak terjadi duplikasi (idempotent)
        $this->notification_sent = true;
        $this->saveQuietly();

        // Penerima: Kader aktif (user_type_id == 3 && officer_type_id == 4) dan Pasien aktif (user_type_id == 2)
        // Tidak mengirim ke Admin, Petugas selain kader, akun nonaktif, atau pembuat materi sendiri
        $creatorId = $this->created_by;

        $kaderUsers = User::where('is_active', true)
            ->where('user_type_id', 3)
            ->whereHas('officer', function ($q) {
                $q->where('officer_type_id', 4);
            })
            ->when($creatorId, fn($q) => $q->where('id', '!=', $creatorId))
            ->get();

        $patientUsers = User::where('is_active', true)
            ->where('user_type_id', 2)
            ->when($creatorId, fn($q) => $q->where('id', '!=', $creatorId))
            ->get();

        $recipientUsers = $kaderUsers->merge($patientUsers)->unique('id');

        // 1. Simpan In-App Database Notifications untuk seluruh penerima yang berhak
        if ($recipientUsers->isNotEmpty()) {
            try {
                \Illuminate\Support\Facades\Notification::send(
                    $recipientUsers,
                    new \App\Notifications\EducationPublishedNotification($this, 'kader')
                );
            } catch (\Throwable $notifEx) {
                \Illuminate\Support\Facades\Log::error('Gagal mengirim Database Notification edukasi: ' . $notifEx->getMessage());
            }
        }

        // 2. Kumpulkan token FCM untuk push notification
        $patientTokens = $patientUsers
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token');

        $kaderTokens = $kaderUsers
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token');

        $tokens = $patientTokens->merge($kaderTokens)->unique()->filter()->values()->toArray();

        $creator = $this->author;
        $senderRole = ($creator && $creator->isSuperAdmin()) ? 'Administrator TB Care' : 'Petugas TB Care';
        $title = 'Materi Edukasi Baru';
        $body  = "Petugas telah menerbitkan materi edukasi baru: {$this->title_material}. Silakan buka aplikasi TB Care untuk membaca materi.";
        $publishedAt = $this->updated_at
            ? $this->updated_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
            : now('Asia/Jakarta')->format('Y-m-d H:i:s');

        $data = [
            'type'              => 'education_published',
            'notification_type' => 'education_published',
            'material_id'       => (string) $this->id,
            'education_id'      => (string) $this->id,
            'title'             => $title,
            'message'           => $body,
            'published_at'      => $publishedAt,
            'target_role'       => 'kader',
            'click_action'      => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        // 3. Simpan catatan ke Notification Center / System Notifications (Web Admin)
        try {
            SystemNotification::create([
                'title'                => $title,
                'message'              => $body,
                'type'                 => 'Edukasi',
                'target_role'          => 'Pasien & Kader',
                'target_puskesmas_id'  => null,
                'sent_by'              => $this->created_by ?? (Auth::check() ? Auth::id() : 1),
                'sent_count'           => count($tokens),
                'status'               => 'Terkirim',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mencatat SystemNotification edukasi: ' . $e->getMessage());
        }

        // 4. Kirim Push Notification via FCM
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
        return self::with('author')->where('is_publish', 1)->orderBy('id', 'desc')->paginate(12);
    }

    public static function getMaterialById($id)
    {
        return self::with('author')->find($id);
    }
}
