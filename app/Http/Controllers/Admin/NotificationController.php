<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use App\Models\Puskesmas;
use App\Models\User;
use App\Models\Patient;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $typeFilter   = $request->query('type');
        $targetFilter = $request->query('target');
        $statusFilter = $request->query('status');
        $keyword      = $request->query('q');

        $query = SystemNotification::with(['sender', 'puskesmas']);

        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }

        if ($targetFilter) {
            $query->where('target_role', $targetFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('message', 'like', "%{$keyword}%");
            });
        }

        $notifications = $query->orderByDesc('id')->get();

        // Statistics
        $totalAll       = SystemNotification::count();
        $totalBroadcast = SystemNotification::where('type', 'Pengumuman')->count();
        $totalReminder  = SystemNotification::where('type', 'Pengingat Minum Obat')->count();
        $totalClinical  = SystemNotification::where('type', 'Peringatan Kasus')->count();

        $puskesmasList = Puskesmas::orderBy('name')->get();

        return view('admin.notifications.index', compact(
            'notifications', 'typeFilter', 'targetFilter', 'statusFilter', 'keyword',
            'totalAll', 'totalBroadcast', 'totalReminder', 'totalClinical', 'puskesmasList'
        ))->with([
            'title'        => 'Notifikasi & Pengumuman Sistem',
            'pageTitle'    => 'Pusat Notifikasi & Broadcast TB Care',
            'pageSubtitle' => 'Kirim broadcast pengumuman, pengingat minum obat, dan pemantauan nakes.'
        ]);
    }

    public function create()
    {
        $puskesmasList = Puskesmas::orderBy('name')->get();

        return view('admin.notifications.form', [
            'notification'  => new SystemNotification(),
            'puskesmasList' => $puskesmasList,
            'isEdit'        => false,
            'title'         => 'Buat Notifikasi Baru',
            'pageTitle'     => 'Kirim Broadcast Notifikasi Baru',
            'pageSubtitle'  => 'Kirimkan pesan langsung ke aplikasi mobile pasien atau portal nakes.'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'                => 'required|string|max:255',
            'message'              => 'required|string',
            'type'                 => 'required|string',
            'target_role'          => 'required|string',
            'target_puskesmas_id'  => 'nullable|exists:puskesmas,id',
            'status'               => 'required|in:Terkirim,Draft',
        ], [
            'title.required'   => 'Judul notifikasi wajib diisi.',
            'message.required' => 'Pesan notifikasi tidak boleh kosong.',
        ]);

        // Calculate recipient count
        $sentCount = 0;
        if ($request->status == 'Terkirim') {
            if ($request->target_role == 'Semua') {
                $sentCount = User::count();
            } elseif ($request->target_role == 'Pasien') {
                $sentCount = Patient::count();
            } elseif ($request->target_role == 'Petugas') {
                $sentCount = User::whereIn('user_type_id', [2, 3])->count();
            } else {
                $sentCount = User::count();
            }

            if ($request->target_puskesmas_id) {
                $sentCount = Patient::where('puskesmas_id', $request->target_puskesmas_id)->count();
            }
        }

        $notif = SystemNotification::create([
            'title'               => $request->title,
            'message'             => $request->message,
            'type'                => $request->type,
            'target_role'         => $request->target_role,
            'target_puskesmas_id' => $request->target_puskesmas_id,
            'sent_by'             => Auth::id() ?? 1,
            'sent_count'          => $sentCount,
            'status'              => $request->status,
        ]);

        ActivityLog::log('Kirim Notifikasi', 'Notifikasi', "Mengirim notifikasi: '{$notif->title}' ({$notif->status}) ke {$notif->target_role}.");

        return redirect()->route('admin.notifications.index')->with('success', 'Notifikasi berhasil diproses dan dicatat.');
    }

    public function show($id)
    {
        $notification = SystemNotification::with(['sender', 'puskesmas'])->findOrFail($id);

        return view('admin.notifications.show', compact('notification'))->with([
            'title'        => 'Detail Notifikasi: ' . $notification->title,
            'pageTitle'    => 'Rincian Pengiriman Notifikasi',
            'pageSubtitle' => 'Detail target penerima dan isi pesan pengumuman/pengingat.'
        ]);
    }

    public function destroy($id)
    {
        $notification = SystemNotification::findOrFail($id);
        $title = $notification->title;
        $notification->delete();

        ActivityLog::log('Hapus Notifikasi', 'Notifikasi', "Menghapus riwayat notifikasi {$title}.");

        return redirect()->route('admin.notifications.index')->with('success', "Notifikasi '{$title}' berhasil dihapus.");
    }
}
