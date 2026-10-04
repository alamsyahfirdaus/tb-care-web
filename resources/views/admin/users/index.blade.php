@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Pengguna</a></li>
    <li class="breadcrumb-item active">{{ $activeRoleName }}</li>
@endsection

@section('content')
    <!-- Quick Nav Role Tabs -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="btn-group btn-group-sm flex-wrap" role="group">
                <a href="{{ route('admin.users.index') }}" class="btn {{ empty($roleFilter) ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-users mr-1"></i> Semua Pengguna
                </a>
                <a href="{{ route('admin.users.index', ['role' => 2]) }}" class="btn {{ $roleFilter == '2' ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-user-injured mr-1"></i> Masyarakat / Pasien
                </a>
                <a href="{{ route('admin.users.index', ['role' => 3]) }}" class="btn {{ $roleFilter == '3' ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-user-md mr-1"></i> Petugas / Nakes
                </a>
                <a href="{{ route('admin.users.index', ['role' => 1]) }}" class="btn {{ $roleFilter == '1' ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-user-shield mr-1"></i> Administrator
                </a>
            </div>
            <div class="float-right">
                <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-success font-weight-bold shadow-sm">
                    <i class="fas fa-user-plus mr-1"></i> Tambah Pengguna Baru
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card card-outline card-primary filter-card mb-3">
        <div class="card-body py-2">
            <form action="{{ route('admin.users.index') }}" method="GET" class="form-row align-items-center">
                @if($roleFilter)
                    <input type="hidden" name="role" value="{{ $roleFilter }}">
                @endif
                <div class="col-md-4 my-1">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari nama, username, email, no HP...">
                    </div>
                </div>
                <div class="col-md-3 my-1">
                    <select name="status" class="form-control form-control-sm select2">
                        <option value="">-- Status Akun: Semua --</option>
                        <option value="1" {{ $statusFilter === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $statusFilter === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2 my-1">
                    <select name="gender" class="form-control form-control-sm select2">
                        <option value="">-- JK: Semua --</option>
                        <option value="L" {{ $genderFilter == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ $genderFilter == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-3 my-1 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold mr-2 flex-grow-1">
                        <i class="fas fa-filter mr-1"></i> Terapkan
                    </button>
                    <a href="{{ route('admin.users.index', $roleFilter ? ['role' => $roleFilter] : []) }}" class="btn btn-sm btn-default" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold mb-0">
                <i class="fas fa-list mr-1 text-primary"></i> Daftar {{ $activeRoleName }} <span class="badge badge-light border ml-2">Total: {{ $users->count() }} akun</span>
            </h3>
            <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
                <i class="fas fa-plus mr-1"></i> Tambah Pengguna
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Pengguna</th>
                            <th>Kontak</th>
                            <th>Peran / Role</th>
                            <th>Unit / Afiliasi</th>
                            <th class="text-center">Status</th>
                            <th>Terdaftar</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $u)
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $u->photo ? asset('upload_images/' . $u->photo) : asset('assets/img/profile.png') }}" 
                                             class="img-circle mr-2 elevation-1" style="width: 38px; height: 38px; object-fit: cover;" alt="">
                                        <div>
                                            <a href="{{ route('admin.users.show', $u->encrypted_id) }}" class="font-weight-bold text-dark d-block">
                                                {{ $u->name }}
                                            </a>
                                            <span class="text-xs text-muted">@ {{ $u->username }} &bull; {{ $u->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><i class="fas fa-phone-alt text-muted text-xs mr-1"></i> {{ $u->phone ?? '-' }}</div>
                                    <div class="text-xs text-muted"><i class="fas fa-envelope text-muted mr-1"></i> {{ $u->email ?? '-' }}</div>
                                </td>
                                <td>
                                    @if($u->user_type_id == 1)
                                        <span class="badge badge-danger font-weight-bold"><i class="fas fa-shield-alt mr-1"></i> Administrator</span>
                                    @elseif($u->user_type_id == 2)
                                        <span class="badge badge-primary font-weight-bold"><i class="fas fa-user-injured mr-1"></i> Pasien TB</span>
                                    @elseif($u->user_type_id == 3)
                                        <span class="badge badge-success font-weight-bold"><i class="fas fa-user-md mr-1"></i> Petugas Nakes</span>
                                    @else
                                        <span class="badge badge-secondary">{{ optional($u->userType)->name ?? '-' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($u->user_type_id == 3 && $u->officer)
                                        @if($u->officer->officer_type_id == 1)
                                            <span class="text-xs font-weight-bold">Dinkes Provinsi</span>
                                        @elseif($u->officer->officer_type_id == 2)
                                            <span class="text-xs font-weight-bold">Dinkes Kab/Kota</span>
                                        @elseif($u->officer->officer_type_id == 3)
                                            <span class="text-xs font-weight-bold">PJTB: {{ optional($u->officer->puskesmas)->name ?? '-' }}</span>
                                        @elseif($u->officer->officer_type_id == 4)
                                            <span class="text-xs font-weight-bold">Kader: {{ optional($u->officer->puskesmas)->name ?? '-' }}</span>
                                        @endif
                                    @elseif($u->user_type_id == 2 && $u->patient)
                                        <span class="text-xs">Faskes: {{ optional($u->patient->puskesmas)->name ?? '-' }}</span>
                                    @else
                                        <span class="text-xs text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($u->is_active)
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Aktif</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1"><i class="fas fa-ban mr-1"></i> Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-xs text-muted" data-order="{{ $u->created_at ? $u->created_at->timestamp : 0 }}">
                                    {{ $u->created_at ? $u->created_at->format('d/m/Y') : '-' }}
                                    @if($u->last_login_at)
                                        <div class="text-xs text-info" title="Terakhir Login"><i class="far fa-clock"></i> {{ Carbon\Carbon::parse($u->last_login_at)->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-action-group">
                                        <a href="{{ route('admin.users.show', $u->encrypted_id) }}" class="btn btn-info btn-xs" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $u->encrypted_id) }}" class="btn btn-warning btn-xs" title="Edit Data">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($u->id != 1 && $u->id != auth()->id())
                                            <button type="button" class="btn btn-danger btn-xs" title="Hapus Akun" 
                                                    onclick="confirmDelete('{{ route('admin.users.destroy', $u->encrypted_id) }}', '{{ addslashes($u->name) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
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
