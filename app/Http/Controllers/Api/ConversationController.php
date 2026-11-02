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
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    /**
     * Display a list of the user's active conversations.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $conversations = Conversation::with([
            'participantOne.officer.puskesmas',
            'participantOne.patient.village',
            'participantTwo.officer.puskesmas',
            'participantTwo.patient.village',
            'lastMessage'
        ])
        ->where(function ($query) use ($user) {
            $query->where('participant_one_id', $user->id)
                  ->orWhere('participant_two_id', $user->id);
        })
        ->whereHas('messages')
        ->orderByDesc('last_message_at')
        ->orderByDesc('updated_at')
        ->get();

        $data = $conversations->map(function ($conv) use ($user) {
            $otherUser = $conv->getOtherParticipant($user);
            if (!$otherUser) {
                return null;
            }

            $otherRole = $this->determineUserRoleLabel($otherUser);
            $subInfo = $this->determineUserSubInfo($otherUser);

            $lastMsg = $conv->lastMessage;
            $unreadCount = $conv->unreadCountFor($user);

            return [
                'id'              => $conv->id,
                'other_user'      => [
                    'id'          => $otherUser->id,
                    'name'        => $otherUser->name,
                    'photo'       => $otherUser->photo ? url('/api/image/' . $otherUser->photo) : null,
                    'role'        => $otherRole,
                    'sub_info'    => $subInfo,
                    'is_active'   => (bool) $otherUser->is_active,
                ],
                'last_message'    => $lastMsg ? [
                    'id'           => $lastMsg->id,
                    'message_type' => $lastMsg->message_type,
                    'message'      => $lastMsg->message_type === 'image' && empty($lastMsg->message)
                                        ? '📷 Gambar'
                                        : ($lastMsg->message ?? '📷 Gambar'),
                    'is_sender'    => (int) $lastMsg->sender_id === (int) $user->id,
                    'read_at'      => $lastMsg->read_at ? $lastMsg->read_at->toIso8601String() : null,
                    'created_at'   => $lastMsg->created_at ? $lastMsg->created_at->toIso8601String() : null,
                    'time_display' => $lastMsg->created_at ? $lastMsg->created_at->timezone('Asia/Jakarta')->format('H:i') : null,
                ] : null,
                'last_message_at' => $conv->last_message_at ? $conv->last_message_at->toIso8601String() : null,
                'unread_count'    => $unreadCount,
                'created_at'      => $conv->created_at ? $conv->created_at->toIso8601String() : null,
            ];
        })->filter()->values();

        return response()->json([
            'message' => 'Daftar percakapan berhasil diambil.',
            'data'    => $data,
        ]);
    }

    /**
     * Get eligible recipients based on user role and territory.
     */
    public function getRecipients(Request $request)
    {
        $user = Auth::user();
        $recipients = ChatAuthorizationService::getEligibleRecipients($user);

        return response()->json([
            'message' => 'Daftar penerima berhasil diambil.',
            'data'    => $recipients,
        ]);
    }

    /**
     * Start or open a 2-way private conversation with a recipient.
     */
    public function store(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|integer|exists:users,id',
        ], [
            'recipient_id.required' => 'Penerima percakapan wajib dipilih.',
            'recipient_id.exists'   => 'Penerima tidak ditemukan dalam sistem.',
        ]);

        $user = Auth::user();
        $recipientId = (int) $request->recipient_id;

        if ($user->id === $recipientId) {
            return response()->json([
                'message' => 'Anda tidak dapat memulai percakapan dengan diri sendiri.'
            ], 422);
        }

        $recipient = User::findOrFail($recipientId);

        if (!$recipient->is_active) {
            return response()->json([
                'message' => 'Pengguna tujuan sedang tidak aktif.'
            ], 403);
        }

        // Territory and role authorization check
        if (!ChatAuthorizationService::canCommunicate($user, $recipient)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk berkomunikasi dengan pengguna ini sesuai ketentuan wilayah binaan.'
            ], 403);
        }

        // Cari apakah percakapan antara kedua pengguna sudah ada dan memiliki pesan
        $existingConversation = Conversation::between($user->id, $recipient->id)
            ->whereHas('messages')
            ->first();

        if ($existingConversation) {
            return response()->json([
                'message' => 'Ruang percakapan siap.',
                'data'    => [
                    'id'         => $existingConversation->id,
                    'is_new'     => false,
                    'other_user' => [
                        'id'        => $recipient->id,
                        'name'      => $recipient->name,
                        'photo'     => $recipient->photo ? url('/api/image/' . $recipient->photo) : null,
                        'role'      => $this->determineUserRoleLabel($recipient),
                        'sub_info'  => $this->determineUserSubInfo($recipient),
                        'is_active' => (bool) $recipient->is_active,
                    ],
                    'created_at' => $existingConversation->created_at ? $existingConversation->created_at->toIso8601String() : null,
                ]
            ], 200);
        }

        // Percakapan baru: JANGAN simpan data ke tabel conversations sebelum pesan pertama dikirim.
        // Cukup siapkan identitas penerima dengan ID 0 agar tidak meninggalkan percakapan kosong jika dibatalkan.
        return response()->json([
            'message' => 'Penerima siap untuk memulai percakapan.',
            'data'    => [
                'id'         => 0,
                'is_new'     => true,
                'other_user' => [
                    'id'        => $recipient->id,
                    'name'      => $recipient->name,
                    'photo'     => $recipient->photo ? url('/api/image/' . $recipient->photo) : null,
                    'role'      => $this->determineUserRoleLabel($recipient),
                    'sub_info'  => $this->determineUserSubInfo($recipient),
                    'is_active' => (bool) $recipient->is_active,
                ],
                'created_at' => null,
            ]
        ], 200);
    }

    /**
     * Get conversation details.
     */
    public function show($id)
    {
        if ((int) $id === 0) {
            return response()->json([
                'message' => 'Percakapan baru belum aktif.',
                'data'    => null,
            ]);
        }

        $user = Auth::user();
        $conversation = Conversation::findOrFail($id);

        if (!ChatAuthorizationService::authorizeConversationAccess($user, $conversation)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke percakapan ini.'
            ], 403);
        }

        $otherUser = $conversation->getOtherParticipant($user);

        return response()->json([
            'message' => 'Detail percakapan berhasil diambil.',
            'data'    => [
                'id'         => $conversation->id,
                'other_user' => $otherUser ? [
                    'id'       => $otherUser->id,
                    'name'     => $otherUser->name,
                    'photo'    => $otherUser->photo ? url('/api/image/' . $otherUser->photo) : null,
                    'role'     => $this->determineUserRoleLabel($otherUser),
                    'sub_info' => $this->determineUserSubInfo($otherUser),
                ] : null,
                'unread_count' => $conversation->unreadCountFor($user),
            ]
        ]);
    }

    /**
     * Get paginated messages in a conversation, with support for live polling via since_id.
     */
    public function getMessages($id, Request $request)
    {
        if ((int) $id === 0) {
            return response()->json([
                'message' => 'Riwayat pesan kosong.',
                'data'    => [],
            ]);
        }

        $user = Auth::user();
        $conversation = Conversation::findOrFail($id);

        if (!ChatAuthorizationService::authorizeConversationAccess($user, $conversation)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke percakapan ini.'
            ], 403);
        }

        $query = Message::with('sender')
            ->where('conversation_id', $conversation->id);

        // Fast live polling by since_id
        if ($request->filled('since_id')) {
            $sinceId = (int) $request->since_id;
            $newMessages = $query->where('id', '>', $sinceId)
                ->orderBy('created_at', 'asc')
                ->get();

            $formatted = $newMessages->map(function ($msg) use ($user) {
                return $this->formatMessageItem($msg, $user);
            });

            return response()->json([
                'message' => 'Pesan baru berhasil dimuat.',
                'data'    => $formatted,
            ]);
        }

        // Standard message loading: get messages in chronological order
        // Limit to latest 100 for fast mobile rendering
        $limit = $request->input('limit', 100);
        $messages = $query->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $formatted = $messages->map(function ($msg) use ($user) {
            return $this->formatMessageItem($msg, $user);
        });

        return response()->json([
            'message' => 'Riwayat pesan berhasil diambil.',
            'data'    => $formatted,
        ]);
    }

    /**
     * Send a new message (text and/or image) in the conversation.
     */
    public function sendMessage($id, Request $request)
    {
        $rules = [
            'message' => 'required_without:image|nullable|string|max:5000',
            'image'   => 'required_without:message|nullable|file|mimes:jpeg,jpg,png,webp|max:5120',
        ];

        if ((int) $id === 0) {
            $rules['recipient_id'] = 'required|integer|exists:users,id';
        }

        $request->validate($rules, [
            'recipient_id.required'    => 'Penerima percakapan wajib ditentukan.',
            'recipient_id.exists'      => 'Penerima tidak ditemukan dalam sistem.',
            'message.required_without' => 'Pesan teks atau gambar wajib disertakan.',
            'image.required_without'   => 'Pesan teks atau gambar wajib disertakan.',
            'image.file'               => 'Lampiran harus berupa file yang valid.',
            'image.mimes'              => 'Format file tidak didukung. Lampiran hanya mendukung gambar (JPG, PNG, WEBP).',
            'image.max'                => 'Ukuran gambar maksimal 5 MB.',
        ]);

        $user = Auth::user();

        // 1. Pengiriman pesan pertama untuk percakapan baru ($id === 0)
        if ((int) $id === 0) {
            $recipientId = (int) $request->recipient_id;
            if ($user->id === $recipientId) {
                return response()->json([
                    'message' => 'Anda tidak dapat mengirim pesan kepada diri sendiri.'
                ], 422);
            }

            $recipient = User::findOrFail($recipientId);
            if (!$recipient->is_active) {
                return response()->json([
                    'message' => 'Penerima tidak aktif atau tidak ditemukan.'
                ], 403);
            }

            // Validasi hak komunikasi wilayah
            if (!ChatAuthorizationService::canCommunicate($user, $recipient)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki wewenang untuk mengirim pesan kepada pengguna ini.'
                ], 403);
            }

            $attachmentPath = null;
            $attachmentMime = null;
            $attachmentSize = null;
            $messageType = 'text';

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $extension = strtolower($file->getClientOriginalExtension());
                $fileName = 'chat_' . Str::random(24) . '.' . $extension;
                $file->move(public_path('images'), $fileName);

                $attachmentPath = $fileName;
                $attachmentMime = $file->getClientMimeType() ?? 'image/' . $extension;
                $attachmentSize = @filesize(public_path('images/' . $fileName)) ?: null;
                $messageType = 'image';
            }

            // Gunakan transaksi atomik: Buat percakapan jika belum ada, simpan pesan, dan update pointer pesan terakhir
            $result = DB::transaction(function () use ($user, $recipient, $request, $messageType, $attachmentPath, $attachmentMime, $attachmentSize) {
                $conversation = Conversation::getOrCreateBetween($user, $recipient);

                $msg = Message::create([
                    'conversation_id'      => $conversation->id,
                    'sender_id'            => $user->id,
                    'message_type'         => $messageType,
                    'message'              => $request->message,
                    'attachment_path'      => $attachmentPath,
                    'attachment_mime_type' => $attachmentMime,
                    'attachment_size'      => $attachmentSize,
                    'read_at'              => null,
                ]);

                $conversation->update([
                    'last_message_id' => $msg->id,
                    'last_message_at' => $msg->created_at,
                ]);

                return [
                    'conversation' => $conversation,
                    'message'      => $msg,
                ];
            });

            // Kirim notifikasi FCM ke penerima
            $this->dispatchChatNotification($recipient, $user, $result['conversation'], $result['message']);

            return response()->json([
                'message' => 'Pesan berhasil dikirim.',
                'data'    => $this->formatMessageItem($result['message'], $user),
            ], 201);
        }

        // 2. Pengiriman pesan pada percakapan yang sudah aktif ($id > 0)
        $conversation = Conversation::findOrFail($id);

        if (!ChatAuthorizationService::authorizeConversationAccess($user, $conversation)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses untuk mengirim pesan pada percakapan ini.'
            ], 403);
        }

        $otherUser = $conversation->getOtherParticipant($user);
        if (!$otherUser || !$otherUser->is_active) {
            return response()->json([
                'message' => 'Penerima tidak aktif atau tidak ditemukan.'
            ], 403);
        }

        // Validate territory rules for sender and recipient
        if (!ChatAuthorizationService::canCommunicate($user, $otherUser)) {
            return response()->json([
                'message' => 'Anda tidak memiliki wewenang untuk mengirim pesan kepada pengguna ini.'
            ], 403);
        }

        $attachmentPath = null;
        $attachmentMime = null;
        $attachmentSize = null;
        $messageType = 'text';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = strtolower($file->getClientOriginalExtension());
            $fileName = 'chat_' . Str::random(24) . '.' . $extension;

            // Save directly to public/images for fast and uniform serving
            $file->move(public_path('images'), $fileName);

            $attachmentPath = $fileName;
            $attachmentMime = $file->getClientMimeType() ?? 'image/' . $extension;
            $attachmentSize = @filesize(public_path('images/' . $fileName)) ?: null;
            $messageType = 'image';
        }

        $message = DB::transaction(function () use ($conversation, $user, $request, $messageType, $attachmentPath, $attachmentMime, $attachmentSize) {
            $msg = Message::create([
                'conversation_id'      => $conversation->id,
                'sender_id'            => $user->id,
                'message_type'         => $messageType,
                'message'              => $request->message,
                'attachment_path'      => $attachmentPath,
                'attachment_mime_type' => $attachmentMime,
                'attachment_size'      => $attachmentSize,
                'read_at'              => null,
            ]);

            $conversation->update([
                'last_message_id' => $msg->id,
                'last_message_at' => $msg->created_at,
            ]);

            return $msg;
        });

        // Send FCM notification to recipient asynchronously/safely
        $this->dispatchChatNotification($otherUser, $user, $conversation, $message);

        return response()->json([
            'message' => 'Pesan berhasil dikirim.',
            'data'    => $this->formatMessageItem($message, $user),
        ], 201);
    }

    /**
     * Mark all unread incoming messages in the conversation as read.
     */
    public function markAsRead($id)
    {
        if ((int) $id === 0) {
            return response()->json([
                'message'       => 'Tidak ada pesan untuk ditandai.',
                'updated_count' => 0,
            ]);
        }

        $user = Auth::user();
        $conversation = Conversation::findOrFail($id);

        if (!ChatAuthorizationService::authorizeConversationAccess($user, $conversation)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke percakapan ini.'
            ], 403);
        }

        $updatedCount = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->json([
            'message'       => 'Pesan telah ditandai sebagai dibaca.',
            'updated_count' => $updatedCount,
        ]);
    }

    /**
     * Helper to dispatch push notifications via FCM.
     */
    protected function dispatchChatNotification(User $recipient, User $sender, Conversation $conversation, Message $message)
    {
        if (empty($recipient->fcm_token)) {
            return;
        }

        try {
            $senderRole = $this->determineUserRoleLabel($sender);
            $title = "Pesan Baru dari {$sender->name} ({$senderRole})";
            $body = $message->message_type === 'image' && empty($message->message)
                ? '📷 Mengirimkan gambar'
                : ($message->message ?? '📷 Mengirimkan gambar');

            $data = [
                'type'            => 'chat',
                'conversation_id' => (string) $conversation->id,
                'sender_id'       => (string) $sender->id,
                'sender_name'     => (string) $sender->name,
                'message_id'      => (string) $message->id,
                'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
            ];

            FcmService::sendNotification([$recipient->fcm_token], $title, $body, $data);
        } catch (\Exception $e) {
            Log::error('Gagal mengirimkan notifikasi chat FCM: ' . $e->getMessage());
        }
    }

    /**
     * Format a message model for JSON response.
     */
    protected function formatMessageItem(Message $msg, User $currentUser): array
    {
        return [
            'id'                   => $msg->id,
            'conversation_id'      => $msg->conversation_id,
            'sender_id'            => $msg->sender_id,
            'sender_name'          => $msg->sender ? $msg->sender->name : 'Pengguna',
            'is_sender'            => (int) $msg->sender_id === (int) $currentUser->id,
            'message_type'         => $msg->message_type,
            'message'              => $msg->message,
            'attachment_url'       => $msg->attachment_path ? url('/api/image/' . $msg->attachment_path) : null,
            'attachment_mime_type' => $msg->attachment_mime_type,
            'attachment_size'      => $msg->attachment_size,
            'is_read'              => $msg->read_at !== null,
            'read_at'              => $msg->read_at ? $msg->read_at->toIso8601String() : null,
            'created_at'           => $msg->created_at ? $msg->created_at->toIso8601String() : null,
            'time_display'         => $msg->created_at ? $msg->created_at->timezone('Asia/Jakarta')->format('H:i') : null,
            'date_display'         => $msg->created_at ? $msg->created_at->timezone('Asia/Jakarta')->format('Y-m-d') : null,
        ];
    }

    /**
     * Determine role label for display.
     */
    protected function determineUserRoleLabel(User $user): string
    {
        if ($user->user_type_id == 2) {
            return 'Pasien';
        }

        if ($user->user_type_id == 3) {
            $officer = $user->officer;
            if ($officer) {
                if ($officer->isKader()) {
                    return 'Kader';
                }
                if ($officer->isPJTB()) {
                    return 'PJTB';
                }
            }
            return 'Petugas';
        }

        if ($user->user_type_id == 1) {
            return 'Admin';
        }

        return 'Pengguna';
    }

    /**
     * Determine subtitle / sub info for user (e.g. Village or Puskesmas).
     */
    protected function determineUserSubInfo(User $user): string
    {
        if ($user->user_type_id == 2) {
            $patient = $user->patient;
            if ($patient && $patient->village) {
                return 'Desa ' . $patient->village->name;
            }
            return 'Pasien TB Care';
        }

        if ($user->user_type_id == 3) {
            $officer = $user->officer;
            if ($officer) {
                if ($officer->isKader()) {
                    $areas = $officer->kaderAreas()->with('village')->get();
                    if ($areas->isNotEmpty()) {
                        $villages = $areas->map(fn($a) => optional($a->village)->name)->filter()->unique()->implode(', ');
                        if ($villages) {
                            return 'Wilayah: ' . $villages;
                        }
                    }
                    return $officer->puskesmas ? 'Puskesmas ' . $officer->puskesmas->name : 'Kader';
                }
                if ($officer->isPJTB()) {
                    return $officer->puskesmas ? 'Puskesmas ' . $officer->puskesmas->name : 'PJTB';
                }
            }
            return 'Petugas Kesehatan';
        }

        return $user->email ?? '';
    }
}
