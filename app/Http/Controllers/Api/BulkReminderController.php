<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\User;
use App\Services\ChatAuthorizationService;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkReminderController extends Controller
{
    /**
     * Get the list of eligible patients for bulk medication reminder.
     */
    public function getRecipients(Request $request)
    {
        $user = Auth::user();

        // Pasien is forbidden from sending bulk reminders
        if ($user->user_type_id == 2) {
            return response()->json([
                'message' => 'Hanya petugas kesehatan yang dapat mengirim pengingat minum obat.'
            ], 403);
        }

        $patients = Patient::accessibleBy($user)
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->with(['user', 'village', 'puskesmas', 'activeTreatment.treatmentType'])
            ->get();

        $data = $patients->map(function ($patient) {
            return [
                'user_id'         => $patient->user->id,
                'patient_id'      => $patient->id,
                'name'            => $patient->user->name,
                'phone'           => $patient->user->phone,
                'photo'           => $patient->user->photo ? url('/api/image/' . $patient->user->photo) : null,
                'village_name'    => $patient->village?->name ?? '-',
                'rt'              => $patient->rt ?? '-',
                'rw'              => $patient->rw ?? '-',
                'puskesmas_name'  => $patient->puskesmas?->name ?? '-',
                'treatment_phase' => $patient->activeTreatment?->phase ?? 'Pengobatan',
            ];
        })->values();

        return response()->json([
            'message' => 'Daftar pasien untuk pengingat berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * Send bulk medication reminder as individual 1-on-1 private messages.
     */
    public function sendBulk(Request $request)
    {
        $user = Auth::user();

        if ($user->user_type_id == 2) {
            return response()->json([
                'message' => 'Hanya petugas kesehatan yang dapat mengirim pengingat minum obat.'
            ], 403);
        }

        $request->validate([
            'patient_user_ids'   => 'required|array|min:1',
            'patient_user_ids.*' => 'required|integer|exists:users,id',
            'message'            => 'required|string|max:2000',
        ], [
            'patient_user_ids.required' => 'Pilih setidaknya satu pasien penerima.',
            'patient_user_ids.min'      => 'Pilih setidaknya satu pasien penerima.',
            'message.required'          => 'Pesan pengingat minum obat wajib diisi.',
            'message.max'               => 'Pesan pengingat maksimal 2000 karakter.',
        ]);

        $recipientUserIds = array_unique($request->patient_user_ids);
        $messageText = trim($request->message);

        $successList = [];
        $failedList = [];

        // Chunk recipients in batches for server efficiency
        $recipientUsers = User::whereIn('id', $recipientUserIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $fcmTokensToNotify = [];

        DB::beginTransaction();
        try {
            foreach ($recipientUserIds as $rId) {
                $recipientUser = $recipientUsers->get($rId);

                if (!$recipientUser) {
                    $failedList[] = [
                        'user_id' => $rId,
                        'reason'  => 'Pengguna tidak ditemukan atau tidak aktif.',
                    ];
                    continue;
                }

                // Verify authorization per recipient
                if (!ChatAuthorizationService::canCommunicate($user, $recipientUser)) {
                    $failedList[] = [
                        'user_id' => $rId,
                        'name'    => $recipientUser->name,
                        'reason'  => 'Pasien berada di luar wilayah tanggung jawab Anda.',
                    ];
                    continue;
                }

                // Get or create 1-on-1 private conversation
                $conversation = Conversation::getOrCreateBetween($user, $recipientUser);

                // Insert individual private message
                $msg = Message::create([
                    'conversation_id'      => $conversation->id,
                    'sender_id'            => $user->id,
                    'message_type'         => 'text',
                    'message'              => $messageText,
                    'attachment_path'      => null,
                    'attachment_mime_type' => null,
                    'attachment_size'      => null,
                    'read_at'              => null,
                ]);

                // Update conversation last message pointer
                $conversation->update([
                    'last_message_id' => $msg->id,
                    'last_message_at' => $msg->created_at,
                ]);

                $successList[] = [
                    'user_id'         => $recipientUser->id,
                    'name'            => $recipientUser->name,
                    'conversation_id' => $conversation->id,
                ];

                if (!empty($recipientUser->fcm_token)) {
                    $fcmTokensToNotify[] = [
                        'token'           => $recipientUser->fcm_token,
                        'conversation_id' => $conversation->id,
                        'message_id'      => $msg->id,
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mengirimkan pengingat minum obat massal: ' . $e->getMessage());

            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses pengiriman pengingat massal.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        // Send individual FCM notifications safely outside transaction
        foreach ($fcmTokensToNotify as $item) {
            try {
                $title = "Pengingat Minum Obat dari {$user->name}";
                $body = $messageText;
                $data = [
                    'type'            => 'chat',
                    'conversation_id' => (string) $item['conversation_id'],
                    'sender_id'       => (string) $user->id,
                    'sender_name'     => (string) $user->name,
                    'message_id'      => (string) $item['message_id'],
                    'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
                ];

                FcmService::sendNotification([$item['token']], $title, $body, $data);
            } catch (\Exception $e) {
                Log::warning('FCM reminder dispatch warning: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message'       => 'Pengingat berhasil dikirim ke ' . count($successList) . ' pasien.',
            'success_count' => count($successList),
            'failed_count'  => count($failedList),
            'success_items' => $successList,
            'failed_items'  => $failedList,
        ], 200);
    }
}
