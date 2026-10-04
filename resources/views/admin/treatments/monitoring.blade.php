@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.treatments.index') }}">Pengobatan</a></li>
    <li class="breadcrumb-item active">Monitoring Minum Obat</li>
@endsection

@section('content')
    <!-- Summary KPIs for Filtered Date -->
    <div class="row">
        <div class="col-md-4">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-camera"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Total Bukti Terkirim (Tanggal Terpilih)</span>
                    <span class="info-box-number text-xl font-weight-bold">{{ $totalHariIni }} Foto Log</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-double"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Sudah Terverifikasi</span>
                    <span class="info-box-number text-xl font-weight-bold text-success">{{ $verifiedHariIni }} Log</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-stopwatch"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Minum Tepat Waktu</span>
                    <span class="info-box-number text-xl font-weight-bold text-primary">{{ $tepatWaktuHariIni }} Log</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-outline card-info filter-card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form action="{{ route('admin.treatments.monitoring') }}" method="GET" class="form-row align-items-center">
                <div class="col-md-3 my-1">
                    <label class="text-xs text-muted mb-0">Pilih Tanggal:</label>
                    <input type="date" name="date" value="{{ $dateFilter }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 my-1">
                    <label class="text-xs text-muted mb-0">Status Verifikasi:</label>
                    <select name="verified" class="form-control form-control-sm select2">
                        <option value="">-- Semua Status --</option>
                        <option value="1" {{ $verifiedFilter === '1' ? 'selected' : '' }}>Sudah Terverifikasi</option>
                        <option value="0" {{ $verifiedFilter === '0' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    </select>
                </div>
                <div class="col-md-4 my-1">
                    <label class="text-xs text-muted mb-0">Puskesmas Pembina:</label>
                    <select name="puskesmas_id" class="form-control form-control-sm select2">
                        <option value="">-- Semua Puskesmas --</option>
                        @foreach($puskesmasList as $pkm)
                            <option value="{{ $pkm->id }}" {{ $puskesmasFilter == $pkm->id ? 'selected' : '' }}>{{ $pkm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 my-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-info font-weight-bold mr-1 flex-grow-1"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('admin.treatments.monitoring') }}" class="btn btn-sm btn-default" title="Hari Ini"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Monitoring Table -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold">
                <i class="fas fa-camera mr-1 text-info"></i> Daftar Bukti Foto Minum Obat
            </h3>
            <span class="badge badge-light border">Total: {{ $records->count() }} data</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="monitoringTable" class="table table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Pasien TB</th>
                            <th>Puskesmas</th>
                            <th>Waktu Kirim</th>
                            <th class="text-center">Bukti Foto</th>
                            <th class="text-center">Kepatuhan Waktu</th>
                            <th class="text-center">Status Verifikasi</th>
                            <th>Catatan</th>
                            <th style="width: 130px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $index => $rec)
                            @php
                                $patient = optional($rec->patientTreatment)->patient;
                            @endphp
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td>
                                    @if($patient)
                                        <a href="{{ route('admin.patients.show', $patient->id) }}" class="font-weight-bold text-dark d-block">
                                            {{ optional($patient->user)->name ?? 'Pasien #' . $patient->id }}
                                        </a>
                                        <span class="text-xs text-muted">NIK: {{ $patient->nik ?? '-' }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="font-weight-bold text-primary">{{ optional(optional($patient)->puskesmas)->name ?? '-' }}</span>
                                </td>
                                <td data-order="{{ $rec->created_at->timestamp }}">
                                    <strong>{{ $rec->created_at->format('d/m/Y') }}</strong>
                                    <div class="text-xs text-muted">{{ $rec->created_at->format('H:i:s') }} WIB</div>
                                </td>
                                <td class="text-center">
                                    @if($rec->photo)
                                        <a href="{{ asset('upload_images/' . $rec->photo) }}" target="_blank" class="btn btn-xs btn-outline-info font-weight-bold" title="Lihat Foto Fullscreen">
                                            <i class="fas fa-image mr-1"></i> Lihat Foto
                                        </a>
                                    @else
                                        <span class="text-xs text-muted">Tanpa Foto</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($rec->late == 0)
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Tepat Waktu</span>
                                    @else
                                        <span class="badge badge-warning text-dark"><i class="fas fa-clock mr-1"></i> Terlambat</span>
                                    @endif
                                </td>
                                <td class="text-center" data-order="{{ $rec->is_verified }}">
                                    @if($rec->is_verified)
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-double mr-1"></i> Terverifikasi</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">Menunggu</span>
                                    @endif
                                </td>
                                <td class="text-xs text-muted">{{ $rec->notes ?? '-' }}</td>
                                <td class="text-center">
                                    @if(!$rec->is_verified)
                                        <form action="{{ route('admin.treatments.verify_medication', $rec->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-xs btn-success font-weight-bold">
                                                <i class="fas fa-check"></i> Verifikasi
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-success font-weight-bold"><i class="fas fa-check"></i> Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
