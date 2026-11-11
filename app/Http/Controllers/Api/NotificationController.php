<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     * Mengambil daftar notifikasi milik pengguna terautentikasi (Kader, Pasien, Petugas).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $query = $user->notifications();

        // Filter tipe notifikasi (e.g., 'education_published', 'medication_logged', 'reminder')
        if ($request->filled('type')) {
            $type = trim($request->input('type'));
            $query->where(function ($q) use ($type) {
                $q->where('data->type', $type)
                  ->orWhere('data->notification_type', $type);
            });
        }

        // Filter notifikasi belum dibaca saja
        if ($request->boolean('unread_only') || $request->input('status') === 'unread') {
            $query->whereNull('read_at');
        }

        // Pencarian teks judul / pesan
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('data->title', 'like', "%{$search}%")
                  ->orWhere('data->message', 'like', "%{$search}%");
            });
        }

        $limit = min(100, max(1, (int) $request->input('limit', 50)));
        $notifications = $query->latest()->take($limit)->get();

        $unreadCount = $user->unreadNotifications()->count();

        $data = $notifications->map(function ($n) {
            $nData = $n->data ?? [];
            $type = $nData['notification_type'] ?? $nData['type'] ?? 'general';
            $title = $nData['title'] ?? 'Notifikasi TB Care';
            $message = $nData['message'] ?? '';
            $createdAt = $n->created_at ? $n->created_at->timezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

            return [
                'id'           => (string) $n->id,
                'type'         => (string) $type,
                'title'        => (string) $title,
                'message'      => (string) $message,
                'is_read'      => $n->read_at !== null,
                'read_at'      => $n->read_at ? $n->read_at->timezone('Asia/Jakarta')->toIso8601String() : null,
                'created_at'   => $createdAt->toIso8601String(),
                'time_ago'     => $createdAt ? $createdAt->locale('id')->diffForHumans() : null,
                'payload'      => $nData,
            ];
        });

        return response()->json([
            'success'      => true,
            'message'      => 'Daftar notifikasi berhasil diambil.',
            'unread_count' => $unreadCount,
            'data'         => $data,
        ]);
    }

    /**
     * GET /api/notifications/unread-count
     * Mengambil jumlah notifikasi yang belum dibaca (badge counter).
     */
    public function unreadCount(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $count = $user->unreadNotifications()->count();

        return response()->json([
            'success'      => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * POST /api/notifications/{id}/read
     * Menandai satu notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Notifikasi berhasil ditandai sebagai dibaca.',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * POST /api/notifications/mark-all-read
     * Menandai seluruh notifikasi belum dibaca milik pengguna sebagai sudah dibaca.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success'      => true,
            'message'      => 'Semua notifikasi berhasil ditandai sebagai dibaca.',
            'unread_count' => 0,
        ]);
    }

    /**
     * DELETE /api/notifications/{id}
     * Menghapus catatan notifikasi milik pengguna.
     */
    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success'      => true,
            'message'      => 'Notifikasi berhasil dihapus.',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }
}
