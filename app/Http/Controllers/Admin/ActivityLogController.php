<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $moduleFilter = $request->query('module');
        $keyword      = $request->query('q');

        $query = ActivityLog::with('user');

        if ($moduleFilter) {
            $query->where('module', $moduleFilter);
        }

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('activity', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('user_name', 'like', "%{$keyword}%")
                  ->orWhere('ip_address', 'like', "%{$keyword}%");
            });
        }

        $logs = $query->orderByDesc('id')->get();

        $modules = ActivityLog::select('module')->distinct()->pluck('module');

        return view('admin.activity_logs.index', compact('logs', 'moduleFilter', 'keyword', 'modules'))->with([
            'title'        => 'Log Aktivitas Sistem TB Care',
            'pageTitle'    => 'Audit Trail & Rekam Jejak Sistem',
            'pageSubtitle' => 'Pemantauan rekam jejak operasi admin, perubahan data pasien, penerbitan skrining, dan integritas sistem.'
        ]);
    }

    public function clearOld()
    {
        // Delete logs older than 30 days
        $threshold = now()->subDays(30);
        $deleted = ActivityLog::where('created_at', '<', $threshold)->delete();

        ActivityLog::log('Pembersihan Log', 'Sistem', "Membersihkan {$deleted} rekam jejak aktivitas lama (> 30 hari).");

        return back()->with('success', "Berhasil membersihkan {$deleted} log aktivitas lawas.");
    }
}
