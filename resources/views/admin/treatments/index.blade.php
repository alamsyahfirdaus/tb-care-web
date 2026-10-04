@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.treatments.index') }}">Pengobatan</a></li>
    <li class="breadcrumb-item active">{{ $pageTitle }}</li>
@endsection

@section('content')
    <!-- Top Tabs & Action -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('admin.treatments.index', ['status' => 'Berjalan']) }}" class="btn {{ $statusFilter == 'Berjalan' ? 'btn-success' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-pills mr-1"></i> Sedang Berjalan
                </a>
                <a href="{{ route('admin.treatments.monitoring') }}" class="btn btn-default font-weight-bold">
                    <i class="fas fa-camera mr-1 text-info"></i> Monitoring Foto Minum Obat
                </a>
                <a href="{{ route('admin.treatments.index', ['status' => 'Selesai']) }}" class="btn {{ $statusFilter == 'Selesai' ? 'btn-success' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Selesai / Sembuh
                </a>
                <a href="{{ route('admin.treatments.index', ['status' => 'all']) }}" class="btn {{ $statusFilter == 'all' ? 'btn-success' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-list mr-1"></i> Semua Program
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-outline card-success filter-card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form action="{{ route('admin.treatments.index') }}" method="GET" class="form-row align-items-center">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="col-md-4 my-1">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari nama pasien, NIK, nomor HP...">
                    </div>
                </div>
                <div class="col-md-3 my-1">
                    <select name="regimen" class="form-control form-control-sm select2">
                        <option value="">-- Regimen: Semua --</option>
                        @foreach($treatmentTypes as $tt)
                            <option value="{{ $tt->id }}" {{ $regimenFilter == $tt->id ? 'selected' : '' }}>{{ $tt->treatment_type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 my-1">
                    <select name="puskesmas_id" class="form-control form-control-sm select2">
                        <option value="">-- Puskesmas: Semua --</option>
                        @foreach($puskesmasList as $pkm)
                            <option value="{{ $pkm->id }}" {{ $puskesmasFilter == $pkm->id ? 'selected' : '' }}>{{ $pkm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 my-1 d-flex">
                    <button type="submit" class="btn btn-sm btn-success font-weight-bold mr-1 flex-grow-1"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('admin.treatments.index', ['status' => $statusFilter]) }}" class="btn btn-sm btn-default"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Treatments Table -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold">
                <i class="fas fa-pills mr-1 text-success"></i> {{ $pageTitle }}
            </h3>
            <span class="badge badge-light border">Total: {{ $treatments->count() }} data</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="treatmentsTable" class="table table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Pasien</th>
                            <th>Puskesmas</th>
                            <th>Regimen Pengobatan</th>
                            <th>Periode Terapi</th>
                            <th>Waktu Minum</th>
                            <th class="text-center">Minum Obat</th>
                            <th class="text-center">Status</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($treatments as $index => $tr)
                            @php
                                $recordsCount = $tr->medicationRecords->count();
                                $verifiedCount = $tr->medicationRecords->where('is_verified', 1)->count();
                            @endphp
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td>
                                    @if($tr->patient)
                                        <a href="{{ route('admin.patients.show', $tr->patient->id) }}" class="font-weight-bold text-dark d-block">
                                            {{ optional($tr->patient->user)->name ?? 'Pasien #' . $tr->patient->id }}
                                        </a>
                                        <span class="text-xs text-muted">NIK: {{ $tr->patient->nik ?? '-' }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="font-weight-bold text-primary">{{ optional(optional($tr->patient)->puskesmas)->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="font-weight-bold">{{ optional($tr->treatmentType)->treatment_type ?? 'Kategori 1' }}</span>
                                    <div class="text-xs text-muted">Durasi: {{ optional($tr->treatmentType)->treatment_duration ?? 6 }} {{ optional($tr->treatmentType)->duration_unit ?? 'Bulan' }}</div>
                                </td>
                                <td class="text-xs" data-order="{{ $tr->start_date ? Carbon\Carbon::parse($tr->start_date)->timestamp : 0 }}">
                                    <div>Mulai: <strong>{{ $tr->start_date ? Carbon\Carbon::parse($tr->start_date)->format('d/m/Y') : '-' }}</strong></div>
                                    <div>Target: <strong>{{ $tr->end_date ? Carbon\Carbon::parse($tr->end_date)->format('d/m/Y') : '-' }}</strong></div>
                                </td>
                                <td class="text-sm">
                                    <span class="badge badge-light border"><i class="far fa-clock mr-1"></i> {{ $tr->medication_time ? Carbon\Carbon::parse($tr->medication_time)->format('H:i') : '08:00' }} WIB</span>
                                </td>
                                <td class="text-center" data-order="{{ $recordsCount }}">
                                    <span class="badge badge-info">{{ $recordsCount }} Log</span>
                                    <div class="text-xs text-muted font-weight-bold">{{ $verifiedCount }} Verifikasi</div>
                                </td>
                                <td class="text-center">
                                    @if($tr->treatment_status == 'Berjalan')
                                        <span class="badge badge-primary px-2 py-1"><i class="fas fa-play mr-1"></i> Berjalan</span>
                                    @elseif($tr->treatment_status == 'Selesai')
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Selesai</span>
                                    @elseif($tr->treatment_status == 'Gagal')
                                        <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> Gagal</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $tr->treatment_status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-action-group">
                                        @if($tr->patient)
                                            <a href="{{ route('admin.patients.show', $tr->patient->encrypted_id) }}" class="btn btn-info btn-xs" title="Lihat Rekam Lengkap">
                                                <i class="fas fa-eye"></i> Rekam
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-warning btn-xs font-weight-bold" data-toggle="modal" data-target="#modal-status-{{ $tr->encrypted_id }}" title="Ubah Status">
                                            <i class="fas fa-edit"></i> Status
                                        </button>
                                    </div>

                                    <!-- Status Modal -->
                                    <div class="modal fade text-left" id="modal-status-{{ $tr->encrypted_id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.treatments.update_status', $tr->encrypted_id) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="modal-header bg-success text-white">
                                                        <h5 class="modal-title font-weight-bold"><i class="fas fa-pills mr-1"></i> Status Pengobatan: {{ optional(optional($tr->patient)->user)->name }}</h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Status Evaluasi Pengobatan <span class="text-danger">*</span></label>
                                                            <select name="treatment_status" class="form-control" required>
                                                                <option value="Berjalan" {{ $tr->treatment_status == 'Berjalan' ? 'selected' : '' }}>Berjalan (Sedang Minum Obat)</option>
                                                                <option value="Selesai" {{ $tr->treatment_status == 'Selesai' ? 'selected' : '' }}>Selesai / Sembuh (Tuntas)</option>
                                                                <option value="Gagal" {{ $tr->treatment_status == 'Gagal' ? 'selected' : '' }}>Gagal / Drop Out / Pindah</option>
                                                                <option value="Meninggal" {{ $tr->treatment_status == 'Meninggal' ? 'selected' : '' }}>Meninggal Dunia</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Catatan Resep / Evaluasi Dokter</label>
                                                            <textarea name="prescription" class="form-control" rows="3">{{ $tr->prescription }}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light d-flex justify-content-between">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-success font-weight-bold">Simpan Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
