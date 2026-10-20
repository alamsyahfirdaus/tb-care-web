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
     * Menghitung kepatuhan minum obat berbasis dosis jatuh tempo aktual.
     * Rumus WHO / Kemenkes: (Dosis unik tercatat diminum) / (Total dosis jatuh tempo) * 100
     *
     * @return array
     */
    public function calculateAdherence(): array
    {
        if (empty($this->start_date)) {
            return [
                'due_doses'      => 0,
                'taken_doses'    => 0,
                'percentage'     => 0.0,
                'percentage_str' => '0%',
                'status'         => 'Belum ada jadwal',
            ];
        }

        $startDate = \Carbon\Carbon::parse($this->start_date)->startOfDay();
        $today = \Carbon\Carbon::today();

        // Jika tanggal mulai di masa depan, belum ada dosis yang jatuh tempo
        if ($startDate->isAfter($today)) {
            return [
                'due_doses'      => 0,
                'taken_doses'    => 0,
                'percentage'     => 0.0,
                'percentage_str' => '0%',
                'status'         => 'Belum mulai pengobatan',
            ];
        }

        // Tentukan batas akhir perhitungan
        $isFinished = in_array($this->treatment_status, ['Selesai', 'Sembuh', 'Pengobatan Lengkap', 'Gagal', 'Meninggal']);
        if ($isFinished && !empty($this->end_date)) {
            $endDate = \Carbon\Carbon::parse($this->end_date)->startOfDay();
            $cutoffDate = $endDate->isBefore($today) ? $endDate : $today;
        } else {
            $cutoffDate = $today;
        }

        // Total hari dari start_date sampai cutoffDate inklusif
        $totalDays = $startDate->diffInDays($cutoffDate) + 1;

        // Jika cutoffDate adalah hari ini, periksa apakah jam minum obat hari ini sudah jatuh tempo atau sudah diminum
        if ($cutoffDate->isToday()) {
            $now = \Carbon\Carbon::now();
            $medTimeStr = $this->medication_time ?: '07:00:00';
            $medTimeToday = \Carbon\Carbon::today()->setTimeFromTimeString($medTimeStr);

            $hasRecordToday = $this->medicationRecords()
                ->where('is_verified', true)
                ->whereDate('created_at', $today)
                ->exists();

            // Jika belum jamnya dan belum ada catatan hari ini, dosis hari ini belum jatuh tempo
            if ($now->isBefore($medTimeToday) && !$hasRecordToday && $totalDays > 1) {
                $totalDays -= 1;
            }
        }

        // Batasi dosis jatuh tempo agar tidak melebihi treatment_days jika ada
        if (!empty($this->treatment_days) && $this->treatment_days > 0) {
            $dueDoses = min((int)$this->treatment_days, $totalDays);
        } else {
            $dueDoses = max(0, $totalDays);
        }

        // Dosis unik yang tercatat diminum (dan terverifikasi) dalam rentang jatuh tempo
        $takenDoses = $this->medicationRecords()
            ->where('is_verified', true)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $cutoffDate)
            ->selectRaw('DATE(created_at) as intake_date')
            ->distinct()
            ->get()
            ->count();

        // Tidak boleh melebihi dosis yang jatuh tempo (capped at 100%)
        $takenDoses = min($dueDoses, $takenDoses);

        $percentage = $dueDoses > 0 ? round(($takenDoses / $dueDoses) * 100, 1) : 0.0;

        return [
            'due_doses'      => $dueDoses,
            'taken_doses'    => $takenDoses,
            'percentage'     => $percentage,
            'percentage_str' => $percentage . '%',
            'status'         => $dueDoses > 0 ? 'Aktif terpantau' : 'Belum ada data dosis jatuh tempo',
        ];
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
