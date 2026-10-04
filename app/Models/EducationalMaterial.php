<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EducationalMaterial extends Model
{
    use HasFactory;

    protected $table = 'educational_materials';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'is_publish' => 'boolean',
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
            if (file_exists(public_path('assets/images/' . $this->image_path))) {
                return asset('assets/images/' . $this->image_path);
            }
            return asset('storage/' . $this->image_path);
        }
        return null;
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
