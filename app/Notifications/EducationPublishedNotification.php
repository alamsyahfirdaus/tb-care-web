<?php

namespace App\Notifications;

use App\Models\EducationalMaterial;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EducationPublishedNotification extends Notification
{
    use Queueable;

    public EducationalMaterial $material;
    public string $targetRole;

    /**
     * Create a new notification instance.
     */
    public function __construct(EducationalMaterial $material, string $targetRole = 'kader')
    {
        $this->material = $material;
        $this->targetRole = $targetRole;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $creator = $this->material->author;
        $senderRole = ($creator && $creator->isSuperAdmin()) ? 'Administrator' : 'Petugas';
        $title = 'Materi Edukasi Baru';
        $message = "Petugas telah menerbitkan materi edukasi baru: {$this->material->title_material}. Silakan buka aplikasi TB Care untuk membaca materi.";

        $publishedAt = $this->material->updated_at
            ? $this->material->updated_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
            : Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');

        return [
            'type'              => 'education_published',
            'notification_type' => 'education_published',
            'education_id'      => (int) $this->material->id,
            'material_id'       => (int) $this->material->id,
            'title'             => $title,
            'message'           => $message,
            'material_title'    => $this->material->title_material,
            'material_type'     => $this->material->material_type,
            'published_at'      => $publishedAt,
            'target_role'       => $this->targetRole,
            'author_name'       => $creator ? $creator->name : $senderRole,
        ];
    }
}
