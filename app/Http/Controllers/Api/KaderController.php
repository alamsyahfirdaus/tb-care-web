<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MedicationRecord;
use App\Models\Message;
use App\Models\Patient;
use App\Models\PatientReminder;
use App\Models\TreatmentVisit;
use App\Models\User;
use App\Http\Controllers\Api\PatientController;
use App\Services\ChatAuthorizationService;
use App\Services\FcmService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class KaderController extends Controller
{
    /**
     * Pastikan pengguna adalah Kader atau Petugas resmi.
     */
    protected function authorizeKaderAccess(): ?\Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Pasien dilarang keras mengakses endpoint Kader
        if ($user->user_type_id == 2) {
            return response()->json([
                'success' => false,
                'message' => 'Halaman ini khusus untuk Kader dan Petugas Kesehatan.'
            ], 403);
        }

        return null;
    }

    /**
     * GET /api/kader/dashboard
     * Ringkasan monitoring pasien binaan dan data dashboard khusus Kader.
     */
    public function dashboard(Request $request)
    {
        if ($errorResponse = $this->authorizeKaderAccess()) {
            return $errorResponse;
        }

        try {
            $user = Auth::user();
            $officer = $user->officer;

            // Ambil seluruh pasien binaan yang berhak diakses oleh Kader ini
            $patientsQuery = Patient::accessibleBy($user)
                ->whereHas('user', function ($q) {
                    $q->where('is_active', true);
                })
                ->with([
                    'user',
                    'village.subdistrict',
                    'subdistrict',
                    'puskesmas',
                    'treatments' => function ($q) {
                        $q->orderByDesc('start_date')->with(['treatmentType', 'visits']);
                    }
                ]);

            // Filter pencarian nama / NIK jika disediakan
            if ($request->filled('search')) {
                $search = trim($request->input('search'));
                $patientsQuery->where(function ($q) use ($search) {
                    $q->where('nik', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Filter status pengobatan: default hanya pengobatan aktif 'Berjalan'
            if ($request->filled('treatment_status')) {
                $statusFilter = trim($request->input('treatment_status'));
                if (strtolower($statusFilter) !== 'all' && strtolower($statusFilter) !== 'semua') {
                    $patientsQuery->whereHas('treatments', function ($tq) use ($statusFilter) {
                        $lower = strtolower($statusFilter);
                        if ($lower === 'pengobatan lengkap') {
                            $tq->whereIn('treatment_status', ['Pengobatan Lengkap', 'Selesai']);
                        } elseif ($lower === 'putus berobat') {
                            $tq->whereIn('treatment_status', ['Putus Berobat', 'Lost to Follow Up', 'Putus Obat']);
                        } else {
                            $tq->where('treatment_status', $statusFilter);
                        }
                    });
                }
            } else {
                $patientsQuery->whereHas('treatments', function ($tq) {
                    $tq->where('treatment_status', 'Berjalan');
                });
            }

            $patients = $patientsQuery->get();

            // Urutkan pasien berdasarkan nama secara alfabetis A-Z (case-insensitive)
            $patients = $patients->sortBy(function ($patient) {
                return strtolower(trim(optional($patient->user)->name ?? ''));
            }, SORT_NATURAL)->values();

            $today = Carbon::today('Asia/Jakarta')->toDateString();
            $tomorrow = Carbon::tomorrow('Asia/Jakarta')->toDateString();

            $totalPatients = $patients->count();
            $activeTreatmentsCount = 0;
            $needRemindersCount = 0;
            $upcomingVisitsCount = 0;

            // Kumpulkan ID treatment aktif untuk pemeriksaan bukti minum obat hari ini (tanpa N+1 query)
            $activeTreatmentIds = [];
            foreach ($patients as $p) {
                $actTr = $p->treatments->firstWhere('treatment_status', 'Berjalan');
                if ($actTr) {
                    $activeTreatmentIds[] = $actTr->id;
                }
            }
            $activeTreatmentIds = array_unique($activeTreatmentIds);

            // Cek bukti minum obat hari ini (menggunakan kolom created_at aktual)
            $todayTakenTreatmentIds = [];
            if (!empty($activeTreatmentIds)) {
                $todayTakenTreatmentIds = DB::table('medication_records')
                    ->whereIn('patient_treatment_id', $activeTreatmentIds)
                    ->whereDate('created_at', $today)
                    ->pluck('patient_treatment_id')
                    ->flip()
                    ->all();
            }

            $patientList = [];

            foreach ($patients as $p) {
                $activeTreatment = $p->treatments->firstWhere('treatment_status', 'Berjalan');
                $hasActiveTreatment = ($activeTreatment !== null);
                if ($hasActiveTreatment) {
                    $activeTreatmentsCount++;
                }

                // Periksa jadwal waktu minum obat
                $medicationTime = $activeTreatment?->medication_time ?? '07:00';
                if (strlen($medicationTime) > 5) {
                    $medicationTime = substr($medicationTime, 0, 5);
                }

                // Status minum obat hari ini
                $todayMedicationTaken = false;
                if ($activeTreatment && isset($todayTakenTreatmentIds[$activeTreatment->id])) {
                    $todayMedicationTaken = true;
                }

                // Cari kunjungan berikutnya yang terjadwal
                $nextVisit = null;
                $hasUpcomingVisit = false;
                $hasVisitNear = false;

                if ($activeTreatment && $activeTreatment->visits) {
                    $upcomingVisit = $activeTreatment->visits
                        ->filter(function ($v) use ($today) {
                            $st = strtolower(trim($v->visit_status ?? ''));
                            return in_array($st, ['terjadwal', 'belum hadir'])
                                && !empty($v->visit_date)
                                && Carbon::parse($v->visit_date)->gte(Carbon::today('Asia/Jakarta'));
                        })
                        ->sortBy('visit_date')
                        ->first();

                    if ($upcomingVisit) {
                        $hasUpcomingVisit = true;
                        $upcomingVisitsCount++;
                        $vDate = Carbon::parse($upcomingVisit->visit_date)->toDateString();
                        if ($vDate === $today || $vDate === $tomorrow) {
                            $hasVisitNear = true;
                        }

                        $nextVisit = [
                            'id'             => $upcomingVisit->id,
                            'visit_date'     => $upcomingVisit->visit_date,
                            'visit_time'     => $upcomingVisit->visit_time ? substr($upcomingVisit->visit_time, 0, 5) : '08:00',
                            'visit_status'   => $upcomingVisit->visit_status,
                            'notes'          => $upcomingVisit->notes,
                            'puskesmas_name' => optional($p->puskesmas)->name ?? 'Puskesmas',
                        ];
                    }
                }

                // Pasien perlu pengingat jika:
                // 1. Pengobatan aktif dan hari ini belum konfirmasi minum obat, ATAU
                // 2. Ada jadwal kunjungan hari ini atau besok
                $needsReminder = false;
                if ($hasActiveTreatment && (!$todayMedicationTaken || $hasVisitNear)) {
                    $needsReminder = true;
                    $needRemindersCount++;
                }

                // Hitung durasi dan persentase pengobatan
                $progressPercent = 0.0;
                $daysElapsed = 0;
                $totalDays = $activeTreatment?->treatment_days ?? 180;
                if ($activeTreatment && $activeTreatment->start_date) {
                    $start = Carbon::parse($activeTreatment->start_date);
                    $daysElapsed = max(0, $start->diffInDays(Carbon::today('Asia/Jakarta')));
                    if ($totalDays > 0) {
                        $progressPercent = min(100.0, round(($daysElapsed / $totalDays) * 100, 1));
                    }
                }

                $villageName = optional($p->village)->name ?? '-';
                $subdistrictName = optional($p->subdistrict)->name ?? optional(optional($p->village)->subdistrict)->name ?? '-';

                // Format nama Title Case
                $rawName = optional($p->user)->name ?? 'Pasien TB';
                $formattedName = ucwords(strtolower(trim($rawName)));

                $patientList[] = [
                    'id'                     => $p->id,
                    'user_id'                => $p->user_id,
                    'name'                   => $formattedName,
                    'username'               => optional($p->user)->username ?? '',
                    'nik'                    => $p->nik ?? '-',
                    'phone'                  => optional($p->user)->phone ?? '-',
                    'gender'                 => optional($p->user)->gender ?? '-',
                    'village_name'           => $villageName,
                    'subdistrict_name'       => $subdistrictName,
                    'puskesmas_name'         => optional($p->puskesmas)->name ?? 'Puskesmas',
                    'treatment_status'       => $activeTreatment ? 'Berjalan' : (optional($p->treatments->first())->treatment_status ?? 'Belum Pengobatan'),
                    'start_date'             => $activeTreatment?->start_date,
                    'end_date'               => $activeTreatment?->end_date,
                    'days_elapsed'           => $daysElapsed,
                    'total_days'             => $totalDays,
                    'progress_percent'       => $progressPercent,
                    'medication_time'        => $medicationTime,
                    'today_medication_taken' => $todayMedicationTaken,
                    'has_upcoming_visit'     => $hasUpcomingVisit,
                    'next_visit'             => $nextVisit,
                    'needs_reminder'         => $needsReminder,
                    'fcm_ready'              => !empty($p->user?->fcm_token),
                ];
            }

            // Info wilayah penugasan Kader
            $assignedVillages = [];
            if ($officer && method_exists($officer, 'kaderAreas')) {
                $assignedVillages = $officer->kaderAreas()->with('village')->get()->map(function ($ka) {
                    return optional($ka->village)->name;
                })->filter()->unique()->values()->all();
            }

            return response()->json([
                'success' => true,
                'message' => 'Dashboard kader berhasil diambil.',
                'kader'   => [
                    'name'              => $user->name,
                    'role'              => $officer?->officer_type_name ?? 'Kader Puskesmas',
                    'puskesmas'         => optional($officer?->puskesmas)->name ?? 'Puskesmas',
                    'scope_description' => $officer?->scope_description ?? 'Wilayah Kader',
                    'villages'          => $assignedVillages,
                ],
                'statistics' => [
                    'total_patients'    => $totalPatients,
                    'active_treatments' => $activeTreatmentsCount,
                    'need_reminders'    => $needRemindersCount,
                    'upcoming_visits'   => $upcomingVisitsCount,
                ],
                'patients' => $patientList,
                'data'     => $patientList,
            ]);
        } catch (\Throwable $e) {
            Log::error('KaderController@dashboard Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace'     => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat dashboard kader: ' . $e->getMessage(),
                'statistics' => [
                    'total_patients'    => 0,
                    'active_treatments' => 0,
                    'need_reminders'    => 0,
                    'upcoming_visits'   => 0,
                ],
                'patients' => [],
                'data'     => [],
            ], 500);
        }
    }

    /**
     * POST /api/kader/reminders/send
     * Mengirim pengingat minum obat atau kunjungan ke perangkat pasien via FCM.
     */
    public function sendReminder(Request $request)
    {
        if ($errorResponse = $this->authorizeKaderAccess()) {
            return $errorResponse;
        }

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'type'       => 'required|in:medication,visit',
            'message'    => 'required|string|max:1000',
        ], [
            'patient_id.required' => 'Pasien tujuan wajib dipilih.',
            'patient_id.exists'   => 'Data pasien tidak ditemukan.',
            'type.required'       => 'Jenis pengingat wajib ditentukan.',
            'type.in'             => 'Jenis pengingat harus salah satu dari: medication atau visit.',
            'message.required'    => 'Pesan pengingat tidak boleh kosong.',
            'message.max'         => 'Pesan pengingat maksimal 1000 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $patient = Patient::with(['user', 'puskesmas'])->find($request->patient_id);
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien tidak ditemukan.',
            ], 404);
        }

        // Verifikasi kepemilikan wilayah binaan (Proteksi IDOR)
        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki wewenang mengirim pengingat ke pasien di luar wilayah binaan Anda.',
            ], 403);
        }

        // Anti-Spam / Cooldown Protection: 5 menit untuk jenis pengingat yang sama ke pasien yang sama
        if (Schema::hasTable('patient_reminders')) {
            $recentReminder = PatientReminder::where('sender_id', $user->id)
                ->where('patient_id', $patient->id)
                ->where('type', $request->type)
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->first();

            if ($recentReminder) {
                $diffMinutes = 5 - Carbon::now()->diffInMinutes($recentReminder->created_at);
                $diffMinutes = max(1, $diffMinutes);
                return response()->json([
                    'success' => false,
                    'code'    => 'RATE_LIMITED',
                    'message' => "Pengingat {$request->type} baru saja dikirimkan ke {$patient->user->name}. Mohon tunggu sekitar {$diffMinutes} menit sebelum mengirim pengingat kembali.",
                ], 429);
            }
        }

        $patientUser = $patient->user;
        $title = ($request->type === 'visit')
            ? 'Pengingat Jadwal Kunjungan TB'
            : 'Pengingat Minum Obat TB';
        $body = trim($request->message);

        $status = 'no_token';
        $errorReason = null;

        // 1. Kirim via FCM jika token tersedia
        $fcmToken = $patientUser?->fcm_token;
        if (!empty($fcmToken)) {
            try {
                $fcmPayload = [
                    'type'          => 'reminder',
                    'reminder_type' => $request->type,
                    'patient_id'    => (string) $patient->id,
                    'sender_name'   => (string) $user->name,
                    'click_action'  => 'FLUTTER_NOTIFICATION_CLICK',
                ];

                $sent = FcmService::sendNotification([$fcmToken], $title, $body, $fcmPayload);
                if ($sent) {
                    $status = 'sent';
                } else {
                    $status = 'failed';
                    $errorReason = 'Layanan FCM gagal memproses token perangkat pasien.';
                }
            } catch (\Throwable $e) {
                Log::error('FCM Reminder Error: ' . $e->getMessage());
                $status = 'failed';
                $errorReason = $e->getMessage();
            }
        } else {
            $status = 'no_token';
            $errorReason = 'Pasien belum memiliki token FCM (belum login di aplikasi).';
        }

        // 2. Kirim pesan ke Live Chat / Percakapan Privat jika pasien aktif
        // Hal ini memastikan pengingat juga dapat dibaca saat pasien membuka menu percakapan
        try {
            if ($patientUser && ChatAuthorizationService::canCommunicate($user, $patientUser)) {
                $conversation = Conversation::getOrCreateBetween($user, $patientUser);
                $msg = Message::create([
                    'conversation_id'      => $conversation->id,
                    'sender_id'            => $user->id,
                    'message_type'         => 'text',
                    'message'              => "[$title]\n$body",
                    'attachment_path'      => null,
                    'attachment_mime_type' => null,
                    'attachment_size'      => null,
                    'read_at'              => null,
                ]);

                $conversation->update([
                    'last_message_id' => $msg->id,
                    'last_message_at' => $msg->created_at,
                ]);
            }
        } catch (\Throwable $chatEx) {
            Log::warning('Chat Reminder Mirror Error: ' . $chatEx->getMessage());
        }

        // 3. Simpan riwayat pengingat ke database
        $reminderRecord = null;
        if (Schema::hasTable('patient_reminders')) {
            try {
                $reminderRecord = PatientReminder::create([
                    'sender_id'       => $user->id,
                    'patient_id'      => $patient->id,
                    'patient_user_id' => $patientUser?->id,
                    'type'            => $request->type,
                    'title'           => $title,
                    'message'         => $body,
                    'status'          => $status,
                    'channel'         => 'fcm',
                    'error_reason'    => $errorReason,
                    'metadata'        => [
                        'patient_name'   => $patientUser?->name,
                        'sender_name'    => $user->name,
                        'puskesmas_name' => optional($patient->puskesmas)->name,
                    ],
                ]);
            } catch (\Throwable $dbEx) {
                Log::warning('Failed saving PatientReminder log: ' . $dbEx->getMessage());
            }
        }

        // 4. Sinkronisasi notifikasi database untuk pasien (In-App Notification Center)
        if ($patientUser && Schema::hasTable('notifications')) {
            try {
                $patientUser->notifications()->create([
                    'id'      => (string) \Illuminate\Support\Str::uuid(),
                    'type'    => ($request->type === 'visit') ? 'App\Notifications\VisitReminderNotification' : 'App\Notifications\MedicationReminderNotification',
                    'data'    => [
                        'notification_type' => ($request->type === 'visit') ? 'visit_reminder' : 'medication_reminder',
                        'type'              => ($request->type === 'visit') ? 'visit_reminder' : 'medication_reminder',
                        'patient_id'        => (int) $patient->id,
                        'title'             => $title,
                        'message'           => $body,
                        'reminder_time'     => Carbon::now('Asia/Jakarta')->format('H:i'),
                        'sender_name'       => $user->name,
                    ],
                    'read_at' => null,
                ]);
            } catch (\Throwable $notifEx) {
                Log::warning('Database Notification Reminder Error: ' . $notifEx->getMessage());
            }
        }

        $statusLabel = match ($status) {
            'sent'     => 'Pengingat berhasil dikirim ke perangkat pasien.',
            'no_token' => 'Pengingat dicatat, namun pasien belum mengaktifkan notifikasi / belum login.',
            'failed'   => 'Gagal mengirim push notification ke perangkat pasien.',
            default    => 'Pengingat diproses.',
        };

        return response()->json([
            'success'      => ($status === 'sent' || $status === 'no_token'),
            'status'       => $status,
            'message'      => $statusLabel,
            'error_reason' => $errorReason,
            'data'         => $reminderRecord,
        ], 200);
    }

    /**
     * GET /api/kader/reminders
     * Riwayat pengingat yang telah dikirim oleh Kader ini.
     */
    public function reminders(Request $request)
    {
        if ($errorResponse = $this->authorizeKaderAccess()) {
            return $errorResponse;
        }

        $user = Auth::user();

        if (!Schema::hasTable('patient_reminders')) {
            return response()->json([
                'success' => true,
                'data'    => [],
                'message' => 'Tabel riwayat pengingat belum tersedia.',
            ]);
        }

        $query = PatientReminder::with(['patient.user', 'patient.village'])
            ->where('sender_id', $user->id)
            ->orderByDesc('created_at');

        // Filter tipe
        if ($request->filled('type') && in_array($request->type, ['medication', 'visit'])) {
            $query->where('type', $request->type);
        }

        // Filter status
        if ($request->filled('status') && in_array($request->status, ['sent', 'no_token', 'failed'])) {
            $query->where('status', $request->status);
        }

        // Filter pencarian nama pasien
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('patient.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $reminders = $query->take(100)->get();

        $formatted = $reminders->map(function ($r) {
            $p = $r->patient;
            return [
                'id'           => $r->id,
                'patient_id'   => $r->patient_id,
                'patient_name' => optional($p?->user)->name ?? ($r->metadata['patient_name'] ?? 'Pasien TB'),
                'village_name' => optional($p?->village)->name ?? '-',
                'type'         => $r->type,
                'type_label'   => $r->type === 'visit' ? 'Kunjungan Puskesmas' : 'Minum Obat',
                'title'        => $r->title,
                'message'      => $r->message,
                'status'       => $r->status,
                'channel'      => $r->channel,
                'error_reason' => $r->error_reason,
                'created_at'   => $r->created_at ? $r->created_at->format('Y-m-d H:i:s') : null,
                'time_ago'     => $r->created_at ? $r->created_at->diffForHumans() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pengingat berhasil diambil.',
            'data'    => $formatted,
        ]);
    }

    /**
     * GET /api/kader/patients/{id}/history
     * Riwayat terpadu untuk pasien binaan (read-only): pengobatan, minum obat, kunjungan, pengingat.
     */
    public function patientHistory($id)
    {
        if ($errorResponse = $this->authorizeKaderAccess()) {
            return $errorResponse;
        }

        try {
            $user = Auth::user();
            $patient = Patient::with(['user', 'puskesmas', 'village'])->find($id);

            if (!$patient) {
                return response()->json(['message' => 'Data pasien tidak ditemukan.'], 404);
            }

            if (!$patient->isAccessibleBy($user)) {
                return response()->json(['message' => 'Anda tidak memiliki akses ke pasien ini.'], 403);
            }

            // Riwayat Pengobatan
            $puskesmasName = optional($patient->puskesmas)->name ?? 'Puskesmas';
            $puskesmasAddress = optional($patient->puskesmas)->address;

            $treatments = $patient->treatments()
                ->with(['treatmentType', 'visits' => function ($vq) {
                    $vq->orderBy('visit_date', 'asc')->orderBy('visit_time', 'asc');
                }])
                ->orderByDesc('start_date')
                ->get();

            $treatmentIds = $treatments->pluck('id')->all();

            // Hitung Riwayat Lengkap & Kepatuhan Minum Obat (Harian dari tanggal mulai hingga hari ini)
            $activeTreatment = $treatments->first();
            $dailyRecordsDescending = [];
            $adherenceSummary = null;

            if ($activeTreatment) {
                $detailedAdherence = $activeTreatment->calculateDetailedAdherence('all');
                $dailyRecordsDescending = collect($detailedAdherence['daily_records'] ?? [])->map(function ($dr) {
                    if (!empty($dr['photo'])) {
                        $dr['photo_url'] = url('/api/image/' . $dr['photo']);
                        $dr['has_proof'] = true;
                    }
                    return $dr;
                })->all();
                $adherenceSummary = [
                    'due_doses'            => $detailedAdherence['due_doses'] ?? 0,
                    'taken_doses'          => $detailedAdherence['taken_doses'] ?? 0,
                    'missed_doses'         => $detailedAdherence['total_missed_days'] ?? 0,
                    'adherence_percentage' => $detailedAdherence['percentage'] ?? 0.0,
                    'adherence_average'    => $detailedAdherence['percentage_str'] ?? '0%',
                    'status'               => $detailedAdherence['status'] ?? 'Belum ada data',
                ];
            }

            // Cari 1 Jadwal Kunjungan Puskesmas Berikutnya yang terdekat dan masih aktif
            $todayStr = Carbon::today('Asia/Jakarta')->format('Y-m-d');
            $nextVisit = TreatmentVisit::whereIn('patient_treatment_id', $treatmentIds)
                ->where('visit_date', '>=', $todayStr)
                ->whereNotIn('visit_status', ['Dibatalkan', 'Batal', 'Selesai', 'Hadir'])
                ->orderBy('visit_date', 'asc')
                ->orderBy('visit_time', 'asc')
                ->first();

            $nextVisitFormatted = $nextVisit ? [
                'id'                => $nextVisit->id,
                'visit_date'        => Carbon::parse($nextVisit->visit_date)->format('Y-m-d'),
                'visit_time'        => $nextVisit->visit_time,
                'visit_status'      => $nextVisit->visit_status,
                'notes'             => $nextVisit->notes,
                'puskesmas_name'    => $puskesmasName,
                'puskesmas_address' => $puskesmasAddress,
            ] : null;

            // Riwayat Minum Obat (menggunakan kolom aktual di tabel medication_records)
            $medicationRecords = DB::table('medication_records')
                ->whereIn('patient_treatment_id', $treatmentIds)
                ->select('id', 'photo', 'is_verified', 'late', 'notes', 'created_at')
                ->orderByDesc('created_at')
                ->take(100)
                ->get()
                ->map(function ($r) {
                    return [
                        'id'          => $r->id,
                        'taken_at'    => $r->created_at ? Carbon::parse($r->created_at)->format('Y-m-d H:i') : null,
                        'is_verified' => (bool) $r->is_verified,
                        'late'        => (bool) $r->late,
                        'status'      => $r->is_verified ? 'Terverifikasi' : 'Menunggu Verifikasi',
                        'notes'       => $r->notes,
                        'photo_url'   => $r->photo ? url('/api/image/' . $r->photo) : null,
                        'created_at'  => $r->created_at,
                    ];
                });

            // Riwayat Pengingat yang Pernah Dikirim ke Pasien Ini
            $reminders = [];
            if (Schema::hasTable('patient_reminders')) {
                $reminders = PatientReminder::where('patient_id', $patient->id)
                    ->orderByDesc('created_at')
                    ->take(30)
                    ->get()
                    ->map(function ($rm) {
                        return [
                            'id'         => $rm->id,
                            'type'       => $rm->type,
                            'title'      => $rm->title,
                            'message'    => $rm->message,
                            'status'     => $rm->status,
                            'created_at' => $rm->created_at ? $rm->created_at->format('Y-m-d H:i') : null,
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'patient'            => [
                        'id'                   => $patient->id,
                        'name'                 => optional($patient->user)->name,
                        'username'             => optional($patient->user)->username ?? '',
                        'nik'                  => $patient->nik,
                        'phone'                => optional($patient->user)->phone,
                        'village'              => optional($patient->village)->name,
                        'puskesmas'            => $puskesmasName,
                        'treatment_start_date' => $patient->treatment_start_date ? Carbon::parse($patient->treatment_start_date)->format('Y-m-d') : $activeTreatment?->start_date,
                        'medication_time'      => $activeTreatment?->medication_time,
                    ],
                    'treatments'         => $treatments,
                    'medications'        => $activeTreatment ? PatientController::normalizePrescription($activeTreatment->prescription, $activeTreatment->medication_time) : [],
                    'medication_records' => $medicationRecords,
                    'daily_records'      => $dailyRecordsDescending,
                    'adherence'          => $adherenceSummary,
                    'next_visit'         => $nextVisitFormatted,
                    'reminders'          => $reminders,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('KaderController@patientHistory Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat riwayat pasien: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/kader/patients/{id}/verify-medication
     * Verifikasi lapangan konfirmasi minum obat untuk hari terlewat oleh Kader.
     */
    public function verifyMedication(Request $request, $id)
    {
        if ($errorResponse = $this->authorizeKaderAccess()) {
            return $errorResponse;
        }

        $user = Auth::user();
        $patient = Patient::with(['user', 'puskesmas'])->find($id);

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Data pasien tidak ditemukan.'
            ], 404);
        }

        if (!$patient->isAccessibleBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses memverifikasi pasien di luar wilayah binaan Anda.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'date'   => 'required|date_format:Y-m-d|before_or_equal:today',
            'action' => 'required|in:tidak_minum,minum_tanpa_konfirmasi',
            'note'   => 'nullable|string|max:500',
            'notes'  => 'nullable|string|max:500',
        ], [
            'date.required'        => 'Tanggal verifikasi wajib diisi.',
            'date.date_format'     => 'Format tanggal harus YYYY-MM-DD.',
            'date.before_or_equal' => 'Tanggal verifikasi tidak boleh di masa depan.',
            'action.required'      => 'Tindakan verifikasi wajib dipilih.',
            'action.in'            => 'Pilihan verifikasi harus salah satu dari: tidak_minum atau minum_tanpa_konfirmasi.',
            'notes.max'            => 'Catatan maksimal 500 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $date = $request->input('date');
        $action = $request->input('action');
        $notes = $request->input('note') ?? $request->input('notes');

        // Cari pengobatan aktif
        $activeTreatment = $patient->treatments()
            ->where('treatment_status', 'Berjalan')
            ->orderByDesc('start_date')
            ->first();

        if (!$activeTreatment) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien tidak memiliki data pengobatan aktif berstatus Berjalan.',
            ], 422);
        }

        // Validasi tanggal tidak boleh mendahului tanggal mulai pengobatan
        if ($activeTreatment->start_date && Carbon::parse($date)->lt(Carbon::parse($activeTreatment->start_date)->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal verifikasi tidak boleh sebelum tanggal mulai pengobatan (' . $activeTreatment->start_date . ').',
            ], 422);
        }

        $doseTime = substr($activeTreatment->medication_time ?? '08:00', 0, 5) . ':00';
        $recordDateTime = Carbon::parse($date . ' ' . $doseTime, 'Asia/Jakarta');

        DB::beginTransaction();
        try {
            // Cari apakah record pada tanggal tersebut sudah ada
            $record = MedicationRecord::where('patient_treatment_id', $activeTreatment->id)
                ->whereDate('created_at', $date)
                ->first();

            $status = ($action === 'minum_tanpa_konfirmasi') ? 'Diminum' : 'Terlewat';
            $defaultNote = ($action === 'minum_tanpa_konfirmasi')
                ? 'Dikonfirmasi langsung oleh Kader (Minum Obat Tanpa Konfirmasi)'
                : 'Dikonfirmasi langsung oleh Kader (Pasien Tidak Minum Obat)';
            $finalNote = !empty($notes) ? $notes : $defaultNote;

            if ($record) {
                $record->update([
                    'status'              => $status,
                    'is_verified'         => 1,
                    'late'                => ($action === 'tidak_minum') ? 1 : $record->late,
                    'confirmation_source' => 'kader',
                    'verified_by'         => $user->id,
                    'verified_at'         => Carbon::now('Asia/Jakarta'),
                    'verification_note'   => $finalNote,
                    'notes'               => $finalNote,
                ]);
            } else {
                $record = MedicationRecord::create([
                    'patient_treatment_id' => $activeTreatment->id,
                    'photo'                => null,
                    'is_verified'          => 1,
                    'late'                 => ($action === 'tidak_minum') ? 1 : 0,
                    'status'               => $status,
                    'confirmation_source'  => 'kader',
                    'verified_by'          => $user->id,
                    'verified_at'          => Carbon::now('Asia/Jakarta'),
                    'verification_note'    => $finalNote,
                    'notes'                => $finalNote,
                    'created_at'           => $recordDateTime,
                    'updated_at'           => Carbon::now('Asia/Jakarta'),
                ]);
            }

            DB::commit();

            // Hitung ulang kepatuhan minum obat secara real-time
            $detailedAdherence = $activeTreatment->fresh(['medicationRecords'])->calculateDetailedAdherence('all');
            $adherenceSummary = [
                'due_doses'            => $detailedAdherence['due_doses'] ?? 0,
                'taken_doses'          => $detailedAdherence['taken_doses'] ?? 0,
                'missed_doses'         => $detailedAdherence['total_missed_days'] ?? 0,
                'adherence_percentage' => $detailedAdherence['percentage'] ?? 0.0,
                'adherence_average'    => $detailedAdherence['percentage_str'] ?? '0%',
                'status'               => $detailedAdherence['status'] ?? 'Belum ada data',
            ];

            return response()->json([
                'success'   => true,
                'message'   => ($action === 'minum_tanpa_konfirmasi')
                    ? 'Berhasil mengonfirmasi pasien telah meminum obat.'
                    : 'Berhasil mencatat pasien tidak meminum obat pada tanggal tersebut.',
                'data'      => [
                    'record_id'           => $record->id,
                    'date'                => $date,
                    'action'              => $action,
                    'status'              => $status,
                    'confirmation_source' => 'kader',
                    'verified_by'         => $user->id,
                    'verified_by_name'    => $user->name,
                    'verified_at'         => Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'verification_note'   => $finalNote,
                    'adherence'           => $adherenceSummary,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('KaderController@verifyMedication Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan verifikasi minum obat: ' . $e->getMessage(),
            ], 500);
        }
    }
}
