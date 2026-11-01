<?php

namespace App\Notifications;

use App\Models\MedicationRecord;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PatientMedicationLoggedNotification extends Notification
{
    use Queueable;

    public Patient $patient;
    public MedicationRecord $record;
    public ?string $notes;
    public bool $isLate;

    /**
     * Create a new notification instance.
     */
    public function __construct(Patient $patient, MedicationRecord $record, ?string $notes = null, bool $isLate = false)
    {
        $this->patient = $patient;
        $this->record = $record;
        $this->notes = $notes;
        $this->isLate = $isLate;
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
     * Format Indonesian date.
     */
    protected function formatIndonesianDate(Carbon $carbon): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $carbon->day . ' ' . ($months[$carbon->month] ?? $carbon->format('F')) . ' ' . $carbon->year;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $carbon = $this->record->created_at 
            ? Carbon::parse($this->record->created_at)->timezone('Asia/Jakarta')
            : Carbon::now('Asia/Jakarta');

        $patientName = optional($this->patient->user)->name ?? 'Pasien TB';
        $dateFormatted = $this->formatIndonesianDate($carbon);
        $timeFormatted = $carbon->format('H.i');

        $message = "{$patientName} telah mencatat minum obat pada {$dateFormatted} pukul {$timeFormatted} WIB.";
        if (!empty($this->notes)) {
            $message .= " Keterangan: {$this->notes}";
        }

        $url = route('patients.show', $this->patient->encrypted_id) . '?tab=tab-minum-obat';

        return [
            'type'            => 'medication_logged',
            'title'           => 'Pasien Telah Minum Obat',
            'message'         => $message,
            'patient_id'      => $this->patient->id,
            'patient_name'    => $patientName,
            'patient_nik'     => $this->patient->nik,
            'puskesmas_id'    => $this->patient->puskesmas_id,
            'puskesmas_name'  => optional($this->patient->puskesmas)->name ?? '-',
            'record_id'       => $this->record->id,
            'taken_at'        => $carbon->format('Y-m-d H:i:s'),
            'date_formatted'  => $dateFormatted,
            'time_formatted'  => $timeFormatted . ' WIB',
            'is_late'         => $this->isLate,
            'has_photo'       => !empty($this->record->photo),
            'notes'           => $this->notes,
            'url'             => $url,
        ];
    }
}
