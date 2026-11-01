@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('kaders.index') }}">Manajemen Petugas</a></li>
    <li class="breadcrumb-item active">Manajemen Kader</li>
@endsection

@section('content')
    <!-- Stat Summary Cards -->
    <div class="row mb-3">
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-box shadow-sm mb-3">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-user-friends"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Kader Terdaftar</span>
                    <span class="info-box-number font-weight-bold h4 mb-0">{{ $totalKader }}</span>
                    @if($currentUser->isPJTB() && $currentPuskesmas)
                        <span class="text-xs text-muted">{{ $currentPuskesmas->name }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-box shadow-sm mb-3">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-user-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Kader Aktif</span>
                    <span class="info-box-number font-weight-bold text-success h4 mb-0">{{ $activeKader }}</span>
                    <span class="text-xs text-muted">Dapat mengakses TB Care Mobile</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-box shadow-sm mb-3">
                <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-user-slash"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Kader Nonaktif</span>
                    <span class="info-box-number font-weight-bold text-secondary h4 mb-0">{{ $inactiveKader }}</span>
                    <span class="text-xs text-muted">Akses mobile ditangguhkan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card card-outline card-primary filter-card mb-3">
        <div class="card-body py-2">
            <form action="{{ route('kaders.index') }}" method="GET" class="form-row align-items-center">
                <div class="col-md-3 my-1">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama, username, kontak...">
                    </div>
                </div>

                @if($currentUser->isSuperAdmin())
                <div class="col-md-3 my-1">
                    <select name="puskesmas_id" class="form-control form-control-sm select2">
                        <option value="">-- Semua Puskesmas --</option>
                        @foreach($puskesmasList as $pkm)
                            <option value="{{ $pkm->id }}" {{ request('puskesmas_id') == $pkm->id ? 'selected' : '' }}>
                                {{ $pkm->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="{{ $currentUser->isSuperAdmin() ? 'col-md-2' : 'col-md-3' }} my-1">
                    <select name="village_id" class="form-control form-control-sm select2">
                        <option value="">-- Semua Desa/Kelurahan --</option>
                        @foreach($villages as $v)
                            <option value="{{ $v->id }}" {{ request('village_id') == $v->id ? 'selected' : '' }}>
                                {{ $v->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 my-1">
                    <select name="status" class="form-control form-control-sm select2">
                        <option value="">-- Status: Semua --</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="{{ $currentUser->isSuperAdmin() ? 'col-md-2' : 'col-md-4' }} my-1 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold mr-2 flex-grow-1">
                        <i class="fas fa-filter mr-1"></i> Terapkan
                    </button>
                    <a href="{{ route('kaders.index') }}" class="btn btn-sm btn-default" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold mb-0">
                <i class="fas fa-user-friends mr-1 text-primary"></i> Daftar Kader Kesehatan Puskesmas
                <span class="badge badge-light border ml-2">Total: {{ $kaders->total() }} kader</span>
            </h3>
            <div class="card-tools d-flex">
                <a href="{{ route('kaders.export', request()->query()) }}" class="btn btn-sm btn-outline-success font-weight-bold mr-2">
                    <i class="fas fa-file-excel mr-1"></i> Ekspor CSV
                </a>
                <a href="{{ route('kaders.create') }}" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
                    <i class="fas fa-user-plus mr-1"></i> Tambah Kader Baru
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th style="width: 100px;">Kode</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Kontak</th>
                            <th>Puskesmas</th>
                            <th>Desa / Kelurahan Binaan</th>
                            <th class="text-center" style="width: 90px;">Status</th>
                            <th style="width: 110px;">Terdaftar</th>
                            <th style="width: 130px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kaders as $kader)
                            @php
                                $kodeKader = 'KDR-' . str_pad(optional($kader->officer)->id ?? $kader->id, 4, '0', STR_PAD_LEFT);
                                $kaderAreas = optional($kader->officer)->kaderAreas ?? collect();
                            @endphp
                            <tr>
                                <td class="text-center text-muted font-weight-bold">
                                    {{ $loop->iteration + ($kaders->currentPage() - 1) * $kaders->perPage() }}
                                </td>
                                <td>
                                    <span class="badge badge-light border text-monospace font-weight-bold">{{ $kodeKader }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('kaders.show', $kader->encrypted_id) }}" class="font-weight-bold text-dark d-block">
                                        {{ $kader->name }}
                                    </a>
                                    <small class="text-muted">
                                        <i class="fas {{ $kader->gender == 'L' ? 'fa-mars text-primary' : 'fa-venus text-danger' }} mr-1"></i>
                                        {{ $kader->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}
                                    </small>
                                </td>
                                <td>
                                    <span class="text-monospace text-primary font-weight-bold">@ {{ $kader->username }}</span>
                                </td>
                                <td>
                                    @if($kader->phone)
                                        <div><i class="fas fa-phone text-muted text-xs mr-1"></i> {{ $kader->phone }}</div>
                                    @endif
                                    @if($kader->email)
                                        <div class="text-xs text-muted"><i class="fas fa-envelope text-muted mr-1"></i> {{ $kader->email }}</div>
                                    @endif
                                    @if(!$kader->phone && !$kader->email)
                                        <span class="text-muted text-xs">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-info px-2 py-1">
                                        <i class="fas fa-hospital-alt mr-1"></i>
                                        {{ optional(optional($kader->officer)->puskesmas)->name ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($kaderAreas->isNotEmpty())
                                        @foreach($kaderAreas as $area)
                                            <span class="badge badge-secondary mr-1 mb-1 font-weight-normal">
                                                <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                                {{ optional($area->village)->name ?? 'Desa' }}
                                                @if($area->rw)
                                                    <small class="font-italic">RW {{ $area->rw }}</small>
                                                @endif
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="badge badge-warning text-dark font-italic">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Belum ada wilayah binaan
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($kader->is_active)
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Aktif</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1"><i class="fas fa-ban mr-1"></i> Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-xs text-muted">
                                    {{ $kader->created_at ? $kader->created_at->format('d/m/Y') : '-' }}
                                    @if($kader->last_login_at)
                                        <div class="text-xs text-info" title="Login terakhir">
                                            <i class="far fa-clock"></i> {{ Carbon\Carbon::parse($kader->last_login_at)->diffForHumans() }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-action-group">
                                        <a href="{{ route('kaders.show', $kader->encrypted_id) }}" class="btn btn-info btn-xs" title="Lihat Detail Kader">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('kaders.edit', $kader->encrypted_id) }}" class="btn btn-warning btn-xs" title="Edit Data Kader">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($kader->is_active)
                                            <button type="button" class="btn btn-secondary btn-xs" title="Nonaktifkan Akun Kader"
                                                    onclick="confirmToggleStatus('{{ route('kaders.toggle_status', $kader->encrypted_id) }}', '{{ addslashes($kader->name) }}', true)">
                                                <i class="fas fa-user-slash text-warning"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-success btn-xs" title="Aktifkan Akun Kader"
                                                    onclick="confirmToggleStatus('{{ route('kaders.toggle_status', $kader->encrypted_id) }}', '{{ addslashes($kader->name) }}', false)">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-user-friends fa-3x mb-2 text-muted"></i>
                                    <p class="mb-0 font-weight-bold">Tidak ada data kader yang ditemukan.</p>
                                    <small>Silakan ubah filter pencarian atau tambahkan kader baru.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($kaders->hasPages())
        <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center">
            <span class="text-muted text-xs">
                Menampilkan {{ $kaders->firstItem() ?? 0 }} - {{ $kaders->lastItem() ?? 0 }} dari total {{ $kaders->total() }} kader
            </span>
            <div>
                {{ $kaders->links() }}
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function confirmToggleStatus(actionUrl, userName, willDeactivate) {
            var title = willDeactivate ? 'Nonaktifkan Akun Kader' : 'Aktifkan Akun Kader';
            var text = willDeactivate 
                ? 'Apakah Anda yakin ingin menonaktifkan akun "' + userName + '"? Seluruh sesi dan token TB Care Mobile kader ini akan langsung dicabut.'
                : 'Apakah Anda yakin ingin mengaktifkan kembali akun "' + userName + '"?';
            var confirmText = willDeactivate ? '<i class="fas fa-ban mr-1"></i> Ya, Nonaktifkan' : '<i class="fas fa-check mr-1"></i> Ya, Aktifkan';
            var confirmColor = willDeactivate ? '#e0a800' : '#28a745';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: willDeactivate ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: confirmColor,
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitToggleForm(actionUrl);
                    }
                });
            } else {
                if (confirm(text)) {
                    submitToggleForm(actionUrl);
                }
            }
        }

        function submitToggleForm(actionUrl) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;

            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            var methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PATCH';
            form.appendChild(methodInput);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    @endpush
@endsection
