<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasEncryptedId;

class PatientTreatment extends Model
{
    use HasFactory, HasEncryptedId;

    protected $table = 'patient_treatments';
    protected $primaryKey = 'id';

    protected $guarded = [];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function treatmentType()
    {
        return $this->belongsTo(TreatmentType::class, 'treatment_type_id');
    }

    public function visits()
    {
        return $this->hasMany(TreatmentVisit::class, 'patient_treatment_id');
    }

    public function medicationRecords()
    {
        return $this->hasMany(MedicationRecord::class, 'patient_treatment_id');
    }

    /**
     * Memeriksa apakah pasien sudah mencatat minum obat untuk hari ini.
     */
    public function isTakenToday(): bool
    {
        return $this->medicationRecords()
            ->whereDate('created_at', \Carbon\Carbon::today('Asia/Jakarta'))
            ->exists();
    }

    public static function getPatientTreatments($filters = [])
    {
        $query = self::with(['patient.user', 'patient.puskesmas', 'treatmentType']);

        if (isset($filters['id']) && $filters['id']) {
            $query->where('id', $filters['id']);
        }

        if (isset($filters['patient_id']) && $filters['patient_id']) {
            $query->where('patient_id', $filters['patient_id']);
        }

        if (isset($filters['treatment_status']) && $filters['treatment_status']) {
            $query->where('treatment_status', $filters['treatment_status']);
        }

        $query->orderBy('id', 'desc');

        return $query->get()->map(function ($treatment) {
            $tTypeName = $treatment->treatmentType 
                ? $treatment->treatmentType->treatment_type . ' (' . $treatment->treatmentType->treatment_duration . ' ' . ucfirst($treatment->treatmentType->duration_unit) . ')'
                : 'Standar';

            return [
                'id'                 => $treatment->id,
                'patient_id'         => $treatment->patient_id,
                'treatment_type_id'  => $treatment->treatment_type_id,
                'full_name'          => optional($treatment->patient->user)->name ?? '-',
                'user_id'            => optional($treatment->patient)->user_id,
                'username'           => optional($treatment->patient->user)->username ?? '-',
                'gender'             => optional($treatment->patient->user)->gender ?? '-',
                'phone'              => optional($treatment->patient->user)->phone ?? '-',
                'puskesmas_name'     => optional($treatment->patient->puskesmas)->name ?? '-',
                'treatment_type'     => $tTypeName,
                'diagnosis_date'     => $treatment->diagnosis_date,
                'start_date'         => $treatment->start_date,
                'end_date'           => $treatment->end_date,
                'treatment_days'     => $treatment->treatment_days,
                'medication_time'    => $treatment->medication_time,
                'prescription'       => $treatment->prescription,
                'treatment_status'   => $treatment->treatment_status,
            ];
        });
    }

    public static function getTreatmentByPatientId($patientId)
    {
        return self::getPatientTreatments(['patient_id' => $patientId]);
    }

    public static function getTreatmentById($id)
    {
        $treatment = self::getPatientTreatments(['id' => $id]);
        return collect($treatment)->firstWhere('id', $id);
    }

    public static function getTreatmentDateRange($id)
    {
        $treatment = self::find($id);

        if (!$treatment || !$treatment->start_date || !$treatment->end_date) {
            return [];
        }

        $startDate = \DateTime::createFromFormat('Y-m-d', $treatment->start_date);
        $endDate   = \DateTime::createFromFormat('Y-m-d', $treatment->end_date);

        if (!$startDate || !$endDate) {
            return [];
        }

        $dateList = [];
        while ($startDate <= $endDate) {
            $dateList[] = $startDate->format('Y-m-d');
            $startDate->modify('+1 day');
        }

        return $dateList;
    }

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->user_type_id == 1) {
            return $query;
        }

        return $query->whereHas('patient', function ($q) use ($user) {
            $q->accessibleBy($user);
        });
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($user->user_type_id == 1) {
            return true;
        }

        return optional($this->patient)->isAccessibleBy($user) ?? false;
    }

    /**
     * Selesaikan jadwal waktu minum obat (mendukung 1 atau lebih waktu dosis harian).
     *
     * @return array<string>
     */
    public function resolveDoseTimes(): array
    {
        // 1. Cek dari patient_medication_schedules
        if ($this->patient) {
            $schedule = $this->patient->medicationSchedule;
            if ($schedule && !empty($schedule->reminder_time)) {
                $t = $schedule->reminder_time;
                return [strlen($t) === 5 ? $t . ':00' : $t];
            }
        }

        // 2. Cek dari medication_time di tabel patient_treatments
        if (!empty($this->medication_time)) {
            $t = $this->medication_time;
            return [strlen($t) === 5 ? $t . ':00' : $t];
        }

        // 3. Cek dari resep (jika ada multi-jadwal dalam array)
        if (!empty($this->prescription)) {
            $decoded = is_string($this->prescription) ? json_decode($this->prescription, true) : $this->prescription;
            if (is_array($decoded)) {
                $times = [];
                foreach ($decoded as $item) {
                    if (is_array($item) && (!empty($item['time']) || !empty($item['jadwal']))) {
                        $t = $item['time'] ?? $item['jadwal'];
                        if (strlen($t) === 5) $t .= ':00';
                        $times[] = $t;
                    }
                }
                if (!empty($times)) {
                    return array_values(array_unique($times));
                }
            }
        }

        return ['08:00:00'];
    }

    /**
     * Menghitung kepatuhan minum obat secara detail dan mendeteksi hari-hari yang terlewat.
     * Menggunakan zona waktu Asia/Jakarta (WIB) secara konsisten.
     *
     * @param string $period Filter periode: 'all', '30_days', '7_days', 'this_month'
     * @return array
     */
    public function calculateDetailedAdherence(string $period = 'all'): array
    {
        $tz = 'Asia/Jakarta';
        $today = \Carbon\Carbon::today($tz);
        $now = \Carbon\Carbon::now($tz);

        // 1. Tentukan tanggal mulai pengobatan
        $startDateStr = $this->start_date ?: optional($this->patient)->treatment_start_date;
        if (empty($startDateStr)) {
            return [
                'due_doses'                      => 0,
                'taken_doses'                    => 0,
                'percentage'                     => 0.0,
                'percentage_str'                 => '0%',
                'total_scheduled_days'           => 0,
                'total_taken_days'               => 0,
                'total_missed_days'              => 0,
                'adherence_percentage'           => 0.0,
                'adherence_percentage_formatted' => '0%',
                'status'                         => 'Belum ada jadwal',
                'start_date'                     => null,
                'end_date'                       => null,
                'period'                         => $period,
                'missed_days'                    => [],
                'taken_days'                     => [],
                'daily_records'                  => [],
            ];
        }

        $startDate = \Carbon\Carbon::parse($startDateStr, $tz)->startOfDay();

        // Jika tanggal mulai di masa depan, belum ada hari yang jatuh tempo
        if ($startDate->isAfter($today)) {
            return [
                'due_doses'                      => 0,
                'taken_doses'                    => 0,
                'percentage'                     => 0.0,
                'percentage_str'                 => '0%',
                'total_scheduled_days'           => 0,
                'total_taken_days'               => 0,
                'total_missed_days'              => 0,
                'adherence_percentage'           => 0.0,
                'adherence_percentage_formatted' => '0%',
                'status'                         => 'Belum mulai pengobatan',
                'start_date'                     => $startDate->format('Y-m-d'),
                'end_date'                       => $this->end_date,
                'period'                         => $period,
                'missed_days'                    => [],
                'taken_days'                     => [],
                'daily_records'                  => [],
            ];
        }

        // 2. Tentukan batas akhir perhitungan
        $isFinished = in_array($this->treatment_status, ['Selesai', 'Sembuh', 'Pengobatan Lengkap', 'Gagal', 'Meninggal', 'Putus Berobat', 'Pindah']);
        if ($isFinished && !empty($this->end_date)) {
            $endDate = \Carbon\Carbon::parse($this->end_date, $tz)->startOfDay();
            $cutoffDate = $endDate->isBefore($today) ? $endDate : $today;
        } else {
            $cutoffDate = $today;
        }

        // 3. Tentukan filter rentang tanggal berdasarkan parameter $period
        $rangeEnd = $cutoffDate->copy();
        $rangeStart = match ($period) {
            '7_days'     => $today->copy()->subDays(6)->max($startDate),
            '30_days'    => $today->copy()->subDays(29)->max($startDate),
            'this_month' => $today->copy()->startOfMonth()->max($startDate),
            default      => $startDate->copy(),
        };

        if ($rangeStart->isAfter($rangeEnd)) {
            $rangeStart = $rangeEnd->copy();
        }

        // 4. Resolusi jadwal waktu minum obat (bisa lebih dari satu jika terdapat multi-dosis)
        $doseTimes = $this->resolveDoseTimes();

        // 5. Ambil seluruh catatan minum obat pasien untuk pengobatan ini
        $records = $this->relationLoaded('medicationRecords')
            ? $this->medicationRecords
            : $this->medicationRecords()->orderBy('created_at', 'asc')->get();

        // Petakan catatan berdasarkan tanggal (Y-m-d di WIB)
        $recordsByDate = [];
        foreach ($records as $rec) {
            if (!$rec->created_at) continue;
            $d = \Carbon\Carbon::parse($rec->created_at)->timezone($tz)->format('Y-m-d');
            $recordsByDate[$d][] = $rec;
        }

        // Helper nama hari dan bulan bahasa Indonesia
        $dayNames = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $missedDays = [];
        $takenDays = [];
        $dailyRecords = [];

        $scheduledDosesCount = 0;
        $takenDosesCount = 0;
        $missedDosesCount = 0;

        $current = $rangeStart->copy();
        while ($current->lte($rangeEnd)) {
            $dateStr = $current->format('Y-m-d');
            $dayNameIndo = $dayNames[$current->format('l')] ?? $current->format('l');
            $dateIndo = $current->day . ' ' . ($monthNames[$current->month] ?? $current->format('F')) . ' ' . $current->year;
            $isToday = $current->isToday();
            $recsOnDate = $recordsByDate[$dateStr] ?? [];

            // Evaluasi per dosis terjadwal (jika ada multi-dosis atau dosis tunggal)
            foreach ($doseTimes as $dIndex => $doseTime) {
                $hasTakenDose = !empty($recsOnDate);
                $timeCarbonToday = \Carbon\Carbon::today($tz)->setTimeFromTimeString($doseTime);

                if ($hasTakenDose) {
                    $rec = $recsOnDate[min($dIndex, count($recsOnDate) - 1)];
                    $isMissedStatus = ($rec->status === 'Terlewat');

                    if ($isMissedStatus) {
                        // Diverifikasi oleh Kader: Pasien Tidak Minum Obat
                        $missedItem = [
                            'id'                  => $rec->id,
                            'record_id'           => $rec->id,
                            'patient_treatment_id'=> $rec->patient_treatment_id,
                            'date'                => $dateStr,
                            'date_formatted'      => $dateIndo,
                            'day_name'            => $dayNameIndo,
                            'scheduled_time'      => substr($doseTime, 0, 5) . ' WIB',
                            'time_taken'          => null,
                            'time_taken_formatted'=> null,
                            'status'              => 'Terlewat',
                            'status_key'          => 'missed',
                            'color'               => '#EF4444',
                            'badge_class'         => 'badge-danger',
                            'is_verified'         => 1,
                            'verification_status' => 'Terverifikasi (Tidak Minum)',
                            'verification_note'   => $rec->verification_note ?? 'Diverifikasi oleh Kader: Tidak Minum Obat',
                            'confirmation_source' => $rec->confirmation_source ?? 'kader',
                            'verified_by'         => $rec->verified_by,
                            'verified_at'         => $rec->verified_at ? \Carbon\Carbon::parse($rec->verified_at)->format('Y-m-d H:i:s') : null,
                            'late'                => 1,
                            'notes'               => $rec->notes ?: 'Diverifikasi langsung oleh Kader: Pasien Tidak Minum Obat',
                            'photo'               => null,
                            'photo_url'           => null,
                            'photo_raw'           => null,
                            'submitted_at'        => $rec->created_at ? $rec->created_at->format('Y-m-d H:i:s') : null,
                            'submitted_relative'  => $rec->created_at ? $rec->created_at->diffForHumans() : null,
                            'has_proof'           => false,
                        ];

                        $missedDays[] = $missedItem;
                        $dailyRecords[] = $missedItem;
                        $scheduledDosesCount++;
                        $missedDosesCount++;
                    } else {
                        // Dosis sudah diminum (konfirmasi mandiri atau verifikasi kader)
                        $takenAt = \Carbon\Carbon::parse($rec->created_at)->timezone($tz);

                        $isKaderVerified = ($rec->is_verified && ($rec->confirmation_source === 'kader' || !empty($rec->verified_by)));

                        $takenItem = [
                            'id'                  => $rec->id,
                            'record_id'           => $rec->id,
                            'patient_treatment_id'=> $rec->patient_treatment_id,
                            'date'                => $dateStr,
                            'date_formatted'      => $dateIndo,
                            'day_name'            => $dayNameIndo,
                            'scheduled_time'      => substr($doseTime, 0, 5) . ' WIB',
                            'time_taken'          => $takenAt->format('H:i:s'),
                            'time_taken_formatted'=> $takenAt->format('H.i') . ' WIB',
                            'status'              => 'Sudah Minum',
                            'status_key'          => 'taken',
                            'color'               => '#10B981',
                            'badge_class'         => 'badge-success',
                            'is_verified'         => (int) $rec->is_verified,
                            'verification_status' => $isKaderVerified ? 'Terverifikasi' : 'Sudah Minum',
                            'verification_note'   => $rec->verification_note ?? null,
                            'confirmation_source' => $rec->confirmation_source ?? 'patient',
                            'verified_by'         => $rec->verified_by,
                            'verified_at'         => $rec->verified_at ? \Carbon\Carbon::parse($rec->verified_at)->format('Y-m-d H:i:s') : null,
                            'late'                => (int) $rec->late,
                            'notes'               => $rec->notes ?: null,
                            'photo'               => $rec->photo ?: null,
                            'photo_url'           => $rec->photo ? url('/api/image/' . $rec->photo) : null,
                            'photo_raw'           => $rec->photo ?: null,
                            'submitted_at'        => $rec->created_at ? $rec->created_at->format('Y-m-d H:i:s') : null,
                            'submitted_relative'  => $rec->created_at ? $rec->created_at->diffForHumans() : null,
                            'has_proof'           => !empty($rec->photo),
                        ];

                        $takenDays[] = $takenItem;
                        $dailyRecords[] = $takenItem;
                        $scheduledDosesCount++;
                        $takenDosesCount++;
                    }
                } else {
                    // Belum ada rekaman pada tanggal ini
                    if ($isToday) {
                        // Hari ini: periksa apakah jam minum obat sudah lewat
                        if ($now->greaterThanOrEqualTo($timeCarbonToday)) {
                            // Sudah melewati jadwal minum obat hari ini tetapi belum melewati batas akhir hari (cutoff 23:59:59 WIB)
                            // Jangan tetapkan Terlewat secara prematur -> Status: Belum Konfirmasi
                            $unconfirmedItem = [
                                'id'                  => null,
                                'record_id'           => null,
                                'patient_treatment_id'=> $this->id,
                                'date'                => $dateStr,
                                'date_formatted'      => $dateIndo,
                                'day_name'            => $dayNameIndo,
                                'scheduled_time'      => substr($doseTime, 0, 5) . ' WIB',
                                'time_taken'          => null,
                                'time_taken_formatted'=> null,
                                'status'              => 'Belum Konfirmasi',
                                'status_key'          => 'not_confirmed',
                                'color'               => '#F59E0B',
                                'badge_class'         => 'badge-warning',
                                'is_verified'         => null,
                                'verification_status' => null,
                                'verification_note'   => null,
                                'late'                => 0,
                                'notes'               => 'Belum ada bukti minum obat hari ini. Batas konfirmasi sampai pukul 23:59 WIB.',
                                'photo'               => null,
                                'photo_url'           => null,
                                'photo_raw'           => null,
                                'submitted_at'        => null,
                                'submitted_relative'  => null,
                                'has_proof'           => false,
                            ];
                            $dailyRecords[] = $unconfirmedItem;
                            // Hari ini yang belum konfirmasi tidak dianggap terlewat
                        } else {
                            // Belum jamnya hari ini -> Belum jatuh tempo
                            $dailyRecords[] = [
                                'id'                  => null,
                                'record_id'           => null,
                                'patient_treatment_id'=> $this->id,
                                'date'                => $dateStr,
                                'date_formatted'      => $dateIndo,
                                'day_name'            => $dayNameIndo,
                                'scheduled_time'      => substr($doseTime, 0, 5) . ' WIB',
                                'time_taken'          => null,
                                'time_taken_formatted'=> null,
                                'status'              => 'Belum Jatuh Tempo',
                                'status_key'          => 'not_due',
                                'color'               => '#94A3B8',
                                'badge_class'         => 'badge-secondary',
                                'is_verified'         => null,
                                'verification_status' => null,
                                'verification_note'   => null,
                                'late'                => 0,
                                'notes'               => 'Waktu minum obat belum tiba (' . substr($doseTime, 0, 5) . ' WIB)',
                                'photo'               => null,
                                'photo_url'           => null,
                                'photo_raw'           => null,
                                'submitted_at'        => null,
                                'submitted_relative'  => null,
                                'has_proof'           => false,
                            ];
                        }
                    } else {
                        // Tanggal masa lalu tanpa catatan -> Pasti Terlewat
                        $missedItem = [
                            'id'                  => null,
                            'record_id'           => null,
                            'patient_treatment_id'=> $this->id,
                            'date'                => $dateStr,
                            'date_formatted'      => $dateIndo,
                            'day_name'            => $dayNameIndo,
                            'scheduled_time'      => substr($doseTime, 0, 5) . ' WIB',
                            'time_taken'          => null,
                            'time_taken_formatted'=> null,
                            'status'              => 'Terlewat',
                            'status_key'          => 'missed',
                            'color'               => '#EF4444',
                            'badge_class'         => 'badge-danger',
                            'is_verified'         => null,
                            'verification_status' => null,
                            'verification_note'   => null,
                            'late'                => 1,
                            'notes'               => 'Belum ada bukti minum obat pada tanggal ini.',
                            'photo'               => null,
                            'photo_url'           => null,
                            'photo_raw'           => null,
                            'submitted_at'        => null,
                            'submitted_relative'  => null,
                            'has_proof'           => false,
                        ];
                        $missedDays[] = $missedItem;
                        $dailyRecords[] = $missedItem;
                        $scheduledDosesCount++;
                        $missedDosesCount++;
                    }
                }
            }

            $current->addDay();
        }

        // Batasi scheduled doses agar tidak melebihi treatment_days jika ada
        if (!empty($this->treatment_days) && $this->treatment_days > 0 && $period === 'all') {
            $maxDoses = (int) $this->treatment_days * count($doseTimes);
            $scheduledDosesCount = min($maxDoses, $scheduledDosesCount);
        }

        $percentage = $scheduledDosesCount > 0 
            ? round(($takenDosesCount / $scheduledDosesCount) * 100, 2)
            : 0.0;

        // Adherence status description
        $statusText = match (true) {
            $scheduledDosesCount === 0 => 'Belum ada dosis jatuh tempo',
            $percentage >= 90.0 => 'Sangat Baik (Patuh)',
            $percentage >= 80.0 => 'Baik',
            default => 'Perlu Perhatian (Kurang Patuh)',
        };

        // Urutkan missed_days dan daily_records secara desc (terbaru di atas)
        $missedDaysDesc = array_reverse($missedDays);
        $dailyRecordsDesc = array_reverse($dailyRecords);

        return [
            // Kunci lama untuk kompatibilitas penuh unit test
            'due_doses'                      => $scheduledDosesCount,
            'taken_doses'                    => $takenDosesCount,
            'percentage'                     => $percentage,
            'percentage_str'                 => $percentage . '%',
            'status'                         => $scheduledDosesCount > 0 ? 'Aktif terpantau' : 'Belum ada data dosis jatuh tempo',

            // Kunci baru yang lengkap untuk Fitur 2
            'total_scheduled'                => $scheduledDosesCount,
            'total_taken'                    => $takenDosesCount,
            'total_missed'                   => $missedDosesCount,
            'total_scheduled_days'           => $scheduledDosesCount,
            'total_taken_days'               => $takenDosesCount,
            'total_missed_days'              => $missedDosesCount,
            'adherence_percentage'           => $percentage,
            'adherence_percentage_formatted' => number_format($percentage, 2, ',', '.') . '%',
            'start_date'                     => $startDate->format('Y-m-d'),
            'end_date'                       => $this->end_date,
            'period'                         => $period,
            'dose_times'                     => $doseTimes,
            'missed_days'                    => $missedDaysDesc,
            'taken_days'                     => $takenDays,
            'daily_records'                  => $dailyRecordsDesc,
        ];
    }

    /**
     * Menghitung kepatuhan minum obat berbasis dosis jatuh tempo aktual.
     * Rumus WHO / Kemenkes: (Dosis unik tercatat diminum) / (Total dosis jatuh tempo) * 100
     *
     * @return array
     */
    public function calculateAdherence(): array
    {
        return $this->calculateDetailedAdherence('all');
    }

    /**
     * Menghitung kepatuhan minum obat secara agregat (kohort) untuk sekelompok pengobatan.
     *
     * @param iterable $treatments
     * @return array
     */
    public static function calculateCohortAdherence($treatments): array
    {
        $totalDue = 0;
        $totalTaken = 0;
        $activeCount = 0;

        foreach ($treatments as $treatment) {
            $metrics = $treatment->calculateAdherence();
            $totalDue += $metrics['due_doses'];
            $totalTaken += $metrics['taken_doses'];
            if ($metrics['due_doses'] > 0) {
                $activeCount++;
            }
        }

        $percentage = $totalDue > 0 ? round(($totalTaken / $totalDue) * 100, 1) : 0.0;

        return [
            'total_treatments'   => is_countable($treatments) ? count($treatments) : 0,
            'active_monitored'   => $activeCount,
            'total_due_doses'    => $totalDue,
            'total_taken_doses'  => $totalTaken,
            'percentage'         => $percentage,
            'percentage_str'     => $percentage . '%',
        ];
    }
}
