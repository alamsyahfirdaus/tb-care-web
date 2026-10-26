<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppVersion extends Model
{
    use HasFactory;

    protected $table = 'app_versions';

    protected $fillable = [
        'platform',
        'version_name',
        'version_code',
        'min_supported_version_code',
        'title',
        'release_notes',
        'apk_path',
        'download_url',
        'file_size',
        'sha256_checksum',
        'is_force_update',
        'is_active',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'version_code' => 'integer',
        'min_supported_version_code' => 'integer',
        'is_force_update' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * User yang merilis versi
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * URL unduhan resmi APK
     */
    public function getResolvedDownloadUrlAttribute(): ?string
    {
        if ($this->apk_path) {
            // Gunakan endpoint API download jika file tersimpan lokal
            return url('/api/mobile/app-version/download/' . $this->id);
        }

        return $this->download_url;
    }

    /**
     * Format ukuran file yang mudah dibaca (MB/KB)
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return '-';
        }

        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Ambil versi aktif terbaru untuk platform tertentu
     */
    public static function getLatestActive(string $platform = 'android'): ?self
    {
        return self::where('platform', $platform)
            ->where('is_active', true)
            ->orderByDesc('version_code')
            ->first();
    }
}
