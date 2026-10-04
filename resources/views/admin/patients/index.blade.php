@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.patients.index') }}">Pasien TB</a></li>
    <li class="breadcrumb-item active">Semua Pasien</li>
@endsection

@section('content')
    <!-- Top Action Bar -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h5 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-user-injured text-danger mr-1"></i> Data Pasien Tuberkulosis Terdaftar
            </h5>
            <a href="{{ route('admin.patients.create') }}" class="btn btn-sm btn-danger font-weight-bold shadow-sm">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Pasien Baru
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-outline card-danger filter-card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form action="{{ route('admin.patients.index') }}" method="GET" class="form-row align-items-center">
                <div class="col-md-3 my-1">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari NIK, nama, alamat...">
                    </div>
                </div>
                <div class="col-md-3 my-1">
                    <select name="puskesmas_id" class="form-control form-control-sm select2">
                        <option value="">-- Puskesmas: Semua --</option>
                        @foreach($puskesmasList as $pkm)
                            <option value="{{ $pkm->id }}" {{ $puskesmasFilter == $pkm->id ? 'selected' : '' }}>
                                {{ $pkm->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 my-1">
                    <select name="treatment_status" class="form-control form-control-sm select2">
                        <option value="">-- Status: Semua --</option>
                        <option value="Berjalan" {{ $statusFilter == 'Berjalan' ? 'selected' : '' }}>Pengobatan Berjalan</option>
                        <option value="Selesai" {{ $statusFilter == 'Selesai' ? 'selected' : '' }}>Selesai / Sembuh</option>
                        <option value="Gagal" {{ $statusFilter == 'Gagal' ? 'selected' : '' }}>Gagal</option>
                        <option value="Meninggal" {{ $statusFilter == 'Meninggal' ? 'selected' : '' }}>Meninggal</option>
                    </select>
                </div>
                <div class="col-md-2 my-1">
                    <select name="gender" class="form-control form-control-sm select2">
                        <option value="">-- JK: Semua --</option>
                        <option value="L" {{ $genderFilter == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ $genderFilter == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-2 my-1 d-flex">
                    <button type="submit" class="btn btn-sm btn-danger font-weight-bold mr-2 flex-grow-1">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.patients.index') }}" class="btn btn-sm btn-default" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Patients Table Card -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold mb-0">
                <i class="fas fa-table mr-1 text-danger"></i> Daftar Rekam Pasien <span class="badge badge-light border ml-2">Total: {{ $patients->count() }} pasien</span>
            </h3>
            <a href="{{ route('admin.patients.create') }}" class="btn btn-sm btn-danger font-weight-bold shadow-sm">
                <i class="fas fa-plus mr-1"></i> Tambah Pasien
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="patientsTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Identitas Pasien</th>
                            <th>Puskesmas Pembina</th>
                            <th>Wilayah & Alamat</th>
                            <th>Status Pengobatan</th>
                            <th>Mulai Berobat</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patients as $p)
                            @php
                                $treatment = $p->treatments->first();
                            @endphp
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ optional($p->user)->photo ? asset('upload_images/' . $p->user->photo) : asset('assets/img/profile.png') }}" 
                                             class="img-circle mr-2 elevation-1" style="width: 38px; height: 38px; object-fit: cover;" alt="">
                                        <div>
                                            <a href="{{ route('admin.patients.show', $p->encrypted_id) }}" class="font-weight-bold text-dark d-block">
                                                {{ optional($p->user)->name ?? 'Pasien #' . ($p->patient_number ?? $loop->iteration) }}
                                            </a>
                                            <span class="text-xs text-muted">
                                                NIK: {{ $p->nik ?? '-' }} &bull; {{ optional($p->user)->gender == 'L' ? 'L' : 'P' }} 
                                                @if(optional($p->user)->date_of_birth)
                                                    ({{ Carbon\Carbon::parse($p->user->date_of_birth)->age }} th)
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-weight-bold text-primary">{{ optional($p->puskesmas)->name ?? '-' }}</span>
                                    <div class="text-xs text-muted">{{ optional($p->user)->phone ?? '-' }}</div>
                                </td>
                                <td>
                                    <div>Kec. {{ optional($p->subdistrict)->name ?? '-' }}</div>
                                    <div class="text-xs text-muted text-truncate" style="max-width: 220px;" title="{{ $p->address }}">
                                        {{ $p->address ?? '-' }} {{ $p->rt ? 'RT ' . $p->rt : '' }} {{ $p->rw ? 'RW ' . $p->rw : '' }}
                                    </div>
                                </td>
                                <td>
                                    @if($treatment)
                                        @if($treatment->treatment_status == 'Berjalan')
                                            <span class="badge badge-tb-berjalan"><i class="fas fa-clock mr-1"></i> Sedang Berjalan</span>
                                        @elseif($treatment->treatment_status == 'Selesai')
                                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Tuntas / Sembuh</span>
                                        @elseif($treatment->treatment_status == 'Gagal')
                                            <span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i> Gagal</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $treatment->treatment_status }}</span>
                                        @endif
                                        <div class="text-xs text-muted mt-1">{{ optional($treatment->treatmentType)->treatment_type ?? 'Kategori 1' }}</div>
                                    @else
                                        <span class="badge badge-light border text-muted">Belum ada regimen</span>
                                    @endif
                                </td>
                                <td class="text-xs text-muted" data-order="{{ $p->treatment_start_date ? Carbon\Carbon::parse($p->treatment_start_date)->timestamp : 0 }}">
                                    {{ $p->treatment_start_date ? Carbon\Carbon::parse($p->treatment_start_date)->format('d/m/Y') : '-' }}
                                    @if($p->diagnosis_date)
                                        <div class="text-xs">Diag: {{ Carbon\Carbon::parse($p->diagnosis_date)->format('d/m/Y') }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-action-group">
                                        <a href="{{ route('admin.patients.show', $p->encrypted_id) }}" class="btn btn-info btn-xs" title="Lihat Rekam Pasien Lengkap">
                                            <i class="fas fa-folder-open"></i> Rekam
                                        </a>
                                        <a href="{{ route('admin.patients.edit', $p->encrypted_id) }}" class="btn btn-warning btn-xs" title="Edit Data">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-xs" title="Hapus Pasien" 
                                                onclick="confirmDelete('{{ route('admin.patients.destroy', $p->encrypted_id) }}', '{{ addslashes(optional($p->user)->name ?? 'Pasien #' . $p->id) }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
