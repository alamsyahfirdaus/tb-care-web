@extends('admin.layouts.app')

@section('content')
<!-- Filter Card -->
<div class="card card-primary card-outline shadow-sm">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter Jejak Audit</h3>
        <div class="card-tools">
            <form action="{{ route('admin.activity-logs.clear') }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus log lama yang berumur lebih dari 30 hari?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-trash-alt mr-1"></i> Bersihkan Log Lama (&gt; 30 Hari)
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="row">
            <div class="col-md-4 mb-2">
                <label class="small text-muted font-weight-bold">Modul Sistem</label>
                <select name="module" class="form-control select2">
                    <option value="">-- Semua Modul --</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" {{ $moduleFilter == $mod ? 'selected' : '' }}>{{ $mod }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-2">
                <label class="small text-muted font-weight-bold">Pencarian Aktivitas / Deskripsi / Operator / IP</label>
                <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari aktivitas...">
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary mr-1 flex-fill"><i class="fas fa-search"></i> Cari</button>
                <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-default"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card card-outline card-navy shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-1 text-navy"></i> Riwayat Aktivitas & Transaksi Data</h3>
        <div class="card-tools">
            <span class="badge badge-secondary p-2">Total: {{ $logs->count() }} Log</span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="activityLogsTable" class="table table-bordered table-hover data-table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Operator / Pengguna</th>
                        <th>Modul</th>
                        <th>Aktivitas</th>
                        <th>Rincian Deskripsi</th>
                        <th>Alamat IP</th>
                        <th>Waktu (WIB)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $idx => $log)
                    <tr>
                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $log->user_name ?? ($log->user->name ?? 'Sistem') }}</div>
                            <small class="text-muted"><i class="fas fa-user-tag mr-1"></i>{{ $log->user->role->name ?? 'System' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-light border px-2 py-1 font-weight-bold">{{ $log->module }}</span>
                        </td>
                        <td>
                            <strong class="text-primary">{{ $log->activity }}</strong>
                        </td>
                        <td>
                            <small class="text-dark">{{ $log->description ?: '-' }}</small>
                        </td>
                        <td>
                            <code>{{ $log->ip_address ?: '127.0.0.1' }}</code>
                        </td>
                        <td data-order="{{ $log->created_at ? $log->created_at->timestamp : 0 }}">
                            <small class="text-muted">{{ $log->created_at ? $log->created_at->format('d/m/Y, H:i:s') : '-' }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
