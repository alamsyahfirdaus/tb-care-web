<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PatientTreatment;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckTreatmentExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patients:check-treatment-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memeriksa periode pengobatan pasien dan mengubah status Berjalan menjadi perlu_tindak_lanjut secara otomatis jika telah melewati tanggal akhir pengobatan (end_date < hari ini WIB).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $timezone = 'Asia/Jakarta';
        $today = Carbon::today($timezone);
        $todayStr = $today->toDateString();

        $this->info("Menjalankan pemeriksaan masa pengobatan pasien (Tanggal rujukan WIB: {$todayStr})...");

        // Ambil data pengobatan yang:
        // 1. Memiliki start_date dan end_date yang valid
        // 2. Tanggal akhir sudah terlewati (end_date < hari ini di WIB)
        // 3. Status masih 'Berjalan' / 'Aktif'
        // Catatan: Jika end_date adalah hari ini, status Berjalan tetap berlaku sampai akhir hari ini (23:59:59 WIB).
        // Perubahan ke perlu_tindak_lanjut baru berlaku keesokan harinya (end_date < todayStr).
        $expiredTreatments = PatientTreatment::with('patient.user')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $todayStr)
            ->whereIn('treatment_status', ['Berjalan', 'berjalan', 'Aktif', 'aktif'])
            ->get();

        $updatedCount = 0;

        foreach ($expiredTreatments as $treatment) {
            $oldStatus = $treatment->treatment_status;
            $patientName = optional($treatment->patient->user)->name ?? "Pasien #{$treatment->patient_id}";

            // Update status ke perlu_tindak_lanjut
            $treatment->treatment_status = 'perlu_tindak_lanjut';
            $treatment->save();

            $updatedCount++;

            // Catat ke system log
            Log::info("[Scheduler] Pengobatan ID {$treatment->id} (Pasien: {$patientName}) otomatis diubah dari '{$oldStatus}' menjadi 'perlu_tindak_lanjut' karena tanggal akhir pengobatan ({$treatment->end_date}) telah terlewati.");

            // Catat ke ActivityLog
            try {
                ActivityLog::create([
                    'user_id'     => null,
                    'user_name'   => 'Sistem Otomatis (Scheduler)',
                    'activity'    => 'Update Otomatis Status Pengobatan',
                    'module'      => 'Pengobatan',
                    'description' => "Status pengobatan {$patientName} otomatis diubah menjadi perlu_tindak_lanjut karena periode pengobatan berakhir pada {$treatment->end_date}.",
                    'ip_address'  => '127.0.0.1',
                    'user_agent'  => 'CLI / Artisan Scheduler',
                ]);
            } catch (\Throwable $e) {
                // Jangan menghentikan loop jika audit log gagal
            }
        }

        $this->info("Pemeriksaan selesai. {$updatedCount} pengobatan berhasil diperbarui menjadi 'perlu_tindak_lanjut'.");

        return Command::SUCCESS;
    }
}
