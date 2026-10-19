@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Pengguna</a></li>
    <li class="breadcrumb-item active">Detail: {{ $user->name }}</li>
@endsection

@section('content')
    <div class="row">
        <!-- User Profile Card -->
        <div class="col-md-4">
            <div class="card card-primary card-outline shadow-sm">
                <div class="card-body box-profile text-center">
                    <img class="profile-user-img img-fluid img-circle elevation-2"
                         src="{{ $user->photo ? asset('upload_images/' . $user->photo) : asset('assets/img/profile.png') }}"
                         style="width: 110px; height: 110px; object-fit: cover;"
                         alt="Foto Profil">

                    <h3 class="profile-username font-weight-bold mt-3 mb-1">{{ $user->name }}</h3>
                    <p class="text-muted mb-2">@ {{ $user->username }}</p>

                    <div>
                        @if($user->user_type_id == 1)
                            <span class="badge badge-danger px-3 py-1 font-weight-bold"><i class="fas fa-shield-alt mr-1"></i> Administrator</span>
                        @elseif($user->user_type_id == 2)
                            <span class="badge badge-primary px-3 py-1 font-weight-bold"><i class="fas fa-user-injured mr-1"></i> Pasien TB</span>
                        @elseif($user->user_type_id == 3)
                            <span class="badge badge-success px-3 py-1 font-weight-bold"><i class="fas fa-user-md mr-1"></i> Petugas Kesehatan</span>
                        @else
                            <span class="badge badge-secondary px-3 py-1">{{ optional($user->userType)->name ?? '-' }}</span>
                        @endif

                        @if($user->is_active)
                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i> Aktif</span>
                        @else
                            <span class="badge badge-secondary px-2 py-1"><i class="fas fa-ban mr-1"></i> Nonaktif</span>
                        @endif
                    </div>

                    <ul class="list-group list-group-unbordered my-3 text-left">
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted"><i class="fas fa-envelope mr-1"></i> Email:</span>
                            <span class="font-weight-bold">{{ $user->email ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted"><i class="fas fa-phone mr-1"></i> No. Telepon:</span>
                            <span class="font-weight-bold">{{ $user->phone ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted"><i class="fas fa-venus-mars mr-1"></i> Jenis Kelamin:</span>
                            <span class="font-weight-bold">{{ $user->gender == 'L' ? 'Laki-laki' : ($user->gender == 'P' ? 'Perempuan' : '-') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted"><i class="fas fa-birthday-cake mr-1"></i> Tempat / Tgl Lahir:</span>
                            <span class="font-weight-bold">
                                {{ $user->place_of_birth ?? '-' }}, 
                                {{ $user->date_of_birth ? Carbon\Carbon::parse($user->date_of_birth)->format('d/m/Y') : '-' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted"><i class="fas fa-calendar-alt mr-1"></i> Terdaftar Sejak:</span>
                            <span class="font-weight-bold">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between py-2">
                            <span class="text-muted"><i class="fas fa-history mr-1"></i> Login Terakhir:</span>
                            <span class="font-weight-bold text-info">
                                {{ $user->last_login_at ? Carbon\Carbon::parse($user->last_login_at)->diffForHumans() : 'Belum pernah login' }}
                            </span>
                        </li>
                    </ul>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('users.edit', $user->encrypted_id) }}" class="btn btn-warning btn-sm font-weight-bold flex-grow-1 mr-2">
                            <i class="fas fa-edit mr-1"></i> Edit Data
                        </a>
                        <a href="{{ route('users.index') }}" class="btn btn-default btn-sm font-weight-bold">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Data & Details -->
        <div class="col-md-8">
            <!-- If user is Pasien -->
            @if($user->user_type_id == 2)
                @if($user->patient)
                    <div class="card card-outline card-danger shadow-sm mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title font-weight-bold text-danger">
                                <i class="fas fa-id-card mr-1"></i> Rekam Medis Pasien Terhubung
                            </h3>
                            <a href="{{ route('patients.show', $user->patient->encrypted_id) }}" class="btn btn-xs btn-outline-danger font-weight-bold">
                                <i class="fas fa-external-link-alt mr-1"></i> Buka Detail Lengkap Pasien
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <dl class="mb-0">
                                        <dt class="text-muted font-weight-normal">Nomor Induk Kependudukan (NIK):</dt>
                                        <dd class="font-weight-bold">{{ $user->patient->nik ?? '-' }}</dd>
                                        
                                        <dt class="text-muted font-weight-normal">Fasilitas Kesehatan Pembina:</dt>
                                        <dd class="font-weight-bold text-primary">{{ optional($user->patient->puskesmas)->name ?? '-' }}</dd>

                                        <dt class="text-muted font-weight-normal">Alamat Lengkap:</dt>
                                        <dd class="font-weight-bold">
                                            {{ $user->patient->address ?? '-' }} 
                                            RT {{ $user->patient->rt ?? '-' }} / RW {{ $user->patient->rw ?? '-' }},
                                            Kel/Desa {{ optional($user->patient->village)->name ?? '-' }},
                                            Kec. {{ optional($user->patient->subdistrict)->name ?? '-' }}
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-sm-6">
                                    <dl class="mb-0">
                                        <dt class="text-muted font-weight-normal">Tinggi & Berat Badan:</dt>
                                        <dd class="font-weight-bold">{{ $user->patient->height ? $user->patient->height . ' cm' : '-' }} / {{ $user->patient->weight ? $user->patient->weight . ' kg' : '-' }}</dd>

                                        <dt class="text-muted font-weight-normal">Golongan Darah & Pekerjaan:</dt>
                                        <dd class="font-weight-bold">{{ $user->patient->blood_type ?? '-' }} &bull; {{ $user->patient->occupation ?? '-' }}</dd>

                                        <dt class="text-muted font-weight-normal">Tanggal Mulai Pengobatan:</dt>
                                        <dd class="font-weight-bold text-success">{{ $user->patient->treatment_start_date ? Carbon\Carbon::parse($user->patient->treatment_start_date)->format('d F Y') : '-' }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i> Pengguna terdaftar sebagai Pasien, namun belum ada profil rekam medis pasien di database. 
                        <a href="{{ route('patients.create') }}" class="font-weight-bold text-dark underline">Klik di sini untuk membuatkan profil pasien.</a>
                    </div>
                @endif
            @endif

            <!-- If user is Petugas / Officer -->
            @if($user->user_type_id == 3)
                @if($user->officer)
                    <div class="card card-outline card-success shadow-sm mb-3">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold text-success">
                                <i class="fas fa-briefcase-medical mr-1"></i> Penugasan Petugas Kesehatan
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <dl class="mb-0">
                                        <dt class="text-muted font-weight-normal">Jabatan / Tipe Petugas:</dt>
                                        <dd class="font-weight-bold">
                                            @if($user->officer->officer_type_id == 1)
                                                <span class="badge badge-info">Dinkes Provinsi</span>
                                            @elseif($user->officer->officer_type_id == 2)
                                                <span class="badge badge-info">Dinkes Kabupaten / Kota</span>
                                            @elseif($user->officer->officer_type_id == 3)
                                                <span class="badge badge-primary">PJTB Puskesmas</span>
                                            @elseif($user->officer->officer_type_id == 4)
                                                <span class="badge badge-success">Kader Kesehatan</span>
                                            @endif
                                        </dd>

                                        <dt class="text-muted font-weight-normal">Puskesmas Tempat Tugas:</dt>
                                        <dd class="font-weight-bold text-primary">{{ optional($user->officer->puskesmas)->name ?? '-' }}</dd>
                                    </dl>
                                </div>
                                <div class="col-sm-6">
                                    <dl class="mb-0">
                                        <dt class="text-muted font-weight-normal">Wilayah Penugasan Kab/Kota:</dt>
                                        <dd class="font-weight-bold">{{ optional($user->officer->district)->name ?? '-' }}</dd>

                                        <dt class="text-muted font-weight-normal">Tanggal Penugasan:</dt>
                                        <dd class="font-weight-bold">{{ $user->officer->created_at ? $user->officer->created_at->format('d F Y') : '-' }}</dd>
                                    </dl>
                                </div>
                            </div>

                            @if($user->officer->officer_type_id == 4)
                                @php
                                    $canManageKaderArea = false;
                                    if (auth()->check()) {
                                        $curUser = auth()->user();
                                        if ($curUser->user_type_id == 1) {
                                            $canManageKaderArea = true;
                                        } elseif ($curUser->user_type_id == 3) {
                                            $curPjtb = \App\Models\Officer::where('user_id', $curUser->id)->first();
                                            if ($curPjtb && $curPjtb->officer_type_id == 3 && $curPjtb->puskesmas_id == optional($user->officer)->puskesmas_id) {
                                                $canManageKaderArea = true;
                                            }
                                        }
                                    }
                                @endphp
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="font-weight-bold text-dark m-0"><i class="fas fa-map-marked-alt mr-1"></i> Wilayah Binaan Kader (Kader Areas):</h6>
                                    @if($canManageKaderArea)
                                        <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modalAddKaderArea">
                                            <i class="fas fa-plus-circle mr-1"></i> Tambah Wilayah Binaan
                                        </button>
                                    @endif
                                </div>
                                <div class="table-responsive">
                                    <table id="userKaderAreasTable" class="table table-sm table-bordered table-striped data-table">
                                        <thead>
                                            <tr>
                                                <th>Kecamatan</th>
                                                <th>Kelurahan / Desa</th>
                                                <th>RW</th>
                                                <th>RT</th>
                                                @if($canManageKaderArea)
                                                    <th style="width: 80px; text-align: center;">Aksi</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($user->officer->kaderAreas as $area)
                                                <tr>
                                                    <td>{{ optional($area->subdistrict)->name ?? '-' }}</td>
                                                    <td>{{ optional($area->village)->name ?? '-' }}</td>
                                                    <td>{{ $area->rw }}</td>
                                                    <td>{{ $area->rt ?? 'Semua RT' }}</td>
                                                    @if($canManageKaderArea)
                                                        <td class="text-center">
                                                            <form action="{{ route('kader-areas.destroy', $area->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus wilayah binaan ini?');" style="display:inline;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger btn-xs" title="Hapus Wilayah">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="{{ $canManageKaderArea ? 5 : 4 }}" class="text-center text-muted py-3">
                                                        <em>Belum ada wilayah binaan yang ditetapkan untuk Kader ini.</em>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle mr-1"></i> Akun ini berstatus Petugas, namun belum memiliki relasi data penugasan Puskesmas/Dinkes. Harap lakukan Edit data untuk melengkapi unit tugas.
                    </div>
                @endif
            @endif

            <!-- User Activity Logs -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-history mr-1 text-secondary"></i> Riwayat Aktivitas Akun Terakhir
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="userLogsTable" class="table table-sm table-bordered table-hover data-table">
                            <thead class="bg-light">
                                <tr>
                                    <th>Aktivitas</th>
                                    <th>Modul</th>
                                    <th>Keterangan</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->activityLogs as $log)
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ $log->activity }}</td>
                                        <td><span class="badge badge-light border">{{ $log->module }}</span></td>
                                        <td class="text-xs text-muted">{{ $log->description ?? '-' }}</td>
                                        <td class="text-xs text-muted" data-order="{{ $log->created_at ? $log->created_at->timestamp : 0 }}">{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($user->officer && $user->officer->officer_type_id == 4)
        <!-- Modal Tambah Wilayah Binaan -->
        <div class="modal fade" id="modalAddKaderArea" tabindex="-1" role="dialog" aria-labelledby="modalAddKaderAreaLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ route('kader-areas.store') }}" method="POST" id="formAddKaderArea">
                        @csrf
                        <input type="hidden" name="officer_id" value="{{ $user->officer->id }}">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title font-weight-bold" id="modalAddKaderAreaLabel">
                                <i class="fas fa-map-marker-alt mr-1"></i> Tambah Wilayah Binaan Kader
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="area_subdistrict_id">Kecamatan <span class="text-danger">*</span></label>
                                <select class="form-control" name="subdistrict_id" id="area_subdistrict_id" required>
                                    <option value="">-- Pilih Kecamatan --</option>
                                    @foreach($subdistricts ?? [] as $sub)
                                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="area_village_id">Kelurahan / Desa <span class="text-danger">*</span></label>
                                <select class="form-control" name="village_id" id="area_village_id" required disabled>
                                    <option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="area_rw">RW <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="rw" id="area_rw" placeholder="Contoh: 01" maxlength="5" required>
                                    <small class="text-muted">Nomor RW binaan</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="area_rt">RT <span class="text-muted">(Opsional)</span></label>
                                    <input type="text" class="form-control" name="rt" id="area_rt" placeholder="Contoh: 02" maxlength="5">
                                    <small class="text-muted">Kosongkan jika membina semua RT di RW ini</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary font-weight-bold">
                                <i class="fas fa-save mr-1"></i> Simpan Wilayah
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            $(document).ready(function() {
                $('#area_subdistrict_id').on('change', function() {
                    let subdistrictId = $(this).val();
                    let $villageSelect = $('#area_village_id');
                    $villageSelect.empty().prop('disabled', true);

                    if (subdistrictId) {
                        $villageSelect.append('<option value="">Memuat desa...</option>');
                        $.ajax({
                            url: '{{ url("kader-areas/villages") }}/' + subdistrictId,
                            type: 'GET',
                            dataType: 'json',
                            success: function(data) {
                                $villageSelect.empty().append('<option value="">-- Pilih Kelurahan/Desa --</option>');
                                $.each(data, function(key, village) {
                                    $villageSelect.append('<option value="' + village.id + '">' + village.name + '</option>');
                                });
                                $villageSelect.prop('disabled', false);
                            },
                            error: function() {
                                $villageSelect.empty().append('<option value="">Gagal memuat desa</option>');
                            }
                        });
                    } else {
                        $villageSelect.append('<option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>');
                    }
                });
            });
        </script>
        @endpush
    @endif
@endsection
