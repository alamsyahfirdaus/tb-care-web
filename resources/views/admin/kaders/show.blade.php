@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('kaders.index') }}">Manajemen Kader</a></li>
    <li class="breadcrumb-item active">{{ $kader->name }}</li>
@endsection

@section('content')
    @php
        $officer = $kader->officer;
        $kaderAreas = optional($officer)->kaderAreas ?? collect();
        $kodeKader = 'KDR-' . str_pad(optional($officer)->id ?? $kader->id, 4, '0', STR_PAD_LEFT);
    @endphp

    <!-- Top Action Bar -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <a href="{{ route('kaders.index') }}" class="btn btn-sm btn-default">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Kader
            </a>
            <div>
                <a href="{{ route('kaders.edit', $kader->encrypted_id) }}" class="btn btn-sm btn-warning font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-edit mr-1"></i> Edit Data Kader
                </a>
                @if($kader->is_active)
                    <button type="button" class="btn btn-sm btn-secondary font-weight-bold shadow-sm"
                            onclick="confirmToggleStatus('{{ route('kaders.toggle_status', $kader->encrypted_id) }}', '{{ addslashes($kader->name) }}', true)">
                        <i class="fas fa-user-slash mr-1"></i> Nonaktifkan Akun
                    </button>
                @else
                    <button type="button" class="btn btn-sm btn-success font-weight-bold shadow-sm"
                            onclick="confirmToggleStatus('{{ route('kaders.toggle_status', $kader->encrypted_id) }}', '{{ addslashes($kader->name) }}', false)">
                        <i class="fas fa-user-check mr-1"></i> Aktifkan Akun
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Metrics Row -->
    <div class="row mb-3">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box shadow-sm mb-3">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-user-injured"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pasien di Wilayah Binaan</span>
                    <span class="info-box-number font-weight-bold h4 mb-0">{{ $totalPatientsInArea }}</span>
                    <span class="text-xs text-muted">Total warga terdaftar</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box shadow-sm mb-3">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-pills"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pengobatan Aktif</span>
                    <span class="info-box-number font-weight-bold text-success h4 mb-0">{{ $activeTreatmentsInArea }}</span>
                    <span class="text-xs text-muted">Pasien dalam masa OAT</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box shadow-sm mb-3">
                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-stethoscope"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Skrining TB Dicatat</span>
                    <span class="info-box-number font-weight-bold text-dark h4 mb-0">{{ $screeningsRecorded }}</span>
                    <span class="text-xs text-muted">Oleh kader ini</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box shadow-sm mb-3">
                <span class="info-box-icon bg-teal elevation-1"><i class="fas fa-people-arrows"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Kontak Erat Terpantau</span>
                    <span class="info-box-number font-weight-bold text-teal h4 mb-0">{{ $closeContactsInArea }}</span>
                    <span class="text-xs text-muted">Dalam wilayah binaan</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Kolom Kiri: Profil & Identitas Akun -->
        <div class="col-md-5">
            <!-- Profile Card -->
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-body box-profile text-center">
                    <img class="profile-user-img img-fluid img-circle elevation-2 mb-2"
                         src="{{ $kader->photo ? asset('images/' . $kader->photo) : asset('assets/img/profile.png') }}"
                         alt="Foto Kader" style="width: 90px; height: 90px; object-fit: cover;">

                    <h4 class="profile-username font-weight-bold mb-1">{{ $kader->name }}</h4>
                    <p class="text-muted mb-2">
                        <span class="badge badge-light border text-monospace font-weight-bold mr-1">{{ $kodeKader }}</span>
                        <span class="text-primary font-weight-bold">@ {{ $kader->username }}</span>
                    </p>

                    <div class="d-flex justify-content-center mb-3">
                        <span class="badge badge-info px-2 py-1 mr-2">
                            <i class="fas fa-hospital-alt mr-1"></i> {{ optional($officer->puskesmas)->name ?? 'Puskesmas' }}
                        </span>
                        @if($kader->is_active)
                            <span class="badge badge-success px-2 py-1">
                                <i class="fas fa-check-circle mr-1"></i> Aktif (Mobile OK)
                            </span>
                        @else
                            <span class="badge badge-secondary px-2 py-1">
                                <i class="fas fa-ban mr-1"></i> Nonaktif (Ditangguhkan)
                            </span>
                        @endif
                    </div>

                    <ul class="list-group list-group-unbordered text-left mb-0">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="fas fa-phone mr-1"></i> No. HP / WhatsApp</span>
                            <span class="font-weight-bold">{{ $kader->phone ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="fas fa-envelope mr-1"></i> Alamat Email</span>
                            <span class="font-weight-bold">{{ $kader->email ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="fas fa-venus-mars mr-1"></i> Jenis Kelamin</span>
                            <span class="font-weight-bold">{{ $kader->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="fas fa-birthday-cake mr-1"></i> TTL</span>
                            <span class="font-weight-bold">
                                {{ $kader->place_of_birth ?? '-' }}{{ $kader->date_of_birth ? ', ' . Carbon\Carbon::parse($kader->date_of_birth)->format('d M Y') : '' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="fas fa-calendar-check mr-1"></i> Terdaftar Sejak</span>
                            <span class="font-weight-bold">{{ $kader->created_at ? $kader->created_at->format('d/m/Y H:i') : '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted"><i class="fas fa-clock mr-1"></i> Login Terakhir</span>
                            <span class="font-weight-bold">
                                {{ $kader->last_login_at ? Carbon\Carbon::parse($kader->last_login_at)->diffForHumans() : 'Belum pernah login' }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Catatan Integrasi Mobile -->
            <div class="callout callout-info shadow-sm bg-white">
                <h6 class="font-weight-bold text-info"><i class="fas fa-mobile-alt mr-1"></i> Integrasi TB Care Mobile</h6>
                <p class="text-xs text-muted mb-0">
                    Kader masuk ke aplikasi TB Care Mobile menggunakan <strong>Username</strong> dan kata sandi yang telah didaftarkan. Wilayah penugasan dan daftar pasien akan disinkronkan secara otomatis sesuai desa binaan aktif di samping.
                </p>
            </div>
        </div>

        <!-- Kolom Kanan: Wilayah Binaan & Log Aktivitas -->
        <div class="col-md-7">
            <!-- Card Wilayah Binaan -->
            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold mb-0">
                        <i class="fas fa-map-marked-alt mr-1 text-success"></i> Desa / Kelurahan Binaan Kader
                        <span class="badge badge-light border ml-2">{{ $kaderAreas->count() }} Wilayah</span>
                    </h3>
                    <button type="button" class="btn btn-sm btn-success font-weight-bold" data-toggle="modal" data-target="#modalAddKaderArea">
                        <i class="fas fa-plus mr-1"></i> Tambah Wilayah
                    </button>
                </div>

                <div class="card-body p-0">
                    @if($kaderAreas->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">No</th>
                                        <th>Kelurahan / Desa</th>
                                        <th>Kecamatan</th>
                                        <th style="width: 90px;" class="text-center">RW</th>
                                        <th style="width: 90px;" class="text-center">RT</th>
                                        <th style="width: 65px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kaderAreas as $area)
                                        <tr>
                                            <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                            <td class="font-weight-bold">
                                                <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                                {{ optional($area->village)->name ?? 'Desa' }}
                                            </td>
                                            <td>{{ optional($area->subdistrict)->name ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-light border">{{ $area->rw ? 'RW ' . $area->rw : 'Semua RW' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-light border">{{ $area->rt ? 'RT ' . $area->rt : 'Semua RT' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <form action="{{ route('kader-areas.destroy', $area->id) }}" method="POST"
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus wilayah binaan {{ optional($area->village)->name }} dari kader ini?');"
                                                      style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-xs" title="Hapus Wilayah Binaan">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                            <h6 class="font-weight-bold mb-1">Belum Ada Wilayah Binaan Ditugaskan</h6>
                            <p class="text-xs text-muted mb-3">
                                Kader belum ditugaskan ke desa/kelurahan mana pun. Berdasarkan prinsip <em>fail-closed</em>, kader tidak dapat melihat data pasien di TB Care Mobile sampai wilayah binaan ditetapkan.
                            </p>
                            <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" data-toggle="modal" data-target="#modalAddKaderArea">
                                <i class="fas fa-plus mr-1"></i> Tetapkan Wilayah Binaan Sekarang
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card Riwayat Log Aktivitas -->
            <div class="card shadow-sm mb-4">
                <div class="card-header border-bottom-0">
                    <h3 class="card-title font-weight-bold mb-0">
                        <i class="fas fa-history mr-1 text-secondary"></i> Log Aktivitas Terakhir
                    </h3>
                </div>
                <div class="card-body p-0">
                    @if($kader->activityLogs && $kader->activityLogs->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 140px;">Waktu</th>
                                        <th>Aktivitas</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kader->activityLogs as $log)
                                        <tr>
                                            <td class="text-xs text-muted">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-' }}</td>
                                            <td class="text-xs font-weight-bold">{{ $log->action }}</td>
                                            <td class="text-xs">{{ $log->description }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-3 text-center text-muted text-xs">
                            <em>Belum ada catatan aktivitas khusus untuk akun ini.</em>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Wilayah Binaan -->
    <div class="modal fade" id="modalAddKaderArea" tabindex="-1" role="dialog" aria-labelledby="modalAddKaderAreaLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('kader-areas.store') }}" method="POST" id="formAddKaderArea">
                    @csrf
                    <input type="hidden" name="officer_id" value="{{ optional($officer)->id }}">

                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title font-weight-bold" id="modalAddKaderAreaLabel">
                            <i class="fas fa-map-marker-alt mr-1"></i> Tambah Wilayah Binaan Kader
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label for="modal_subdistrict_id">Kecamatan <span class="text-danger">*</span></label>
                            <select class="form-control" name="subdistrict_id" id="modal_subdistrict_id" required>
                                <option value="">-- Pilih Kecamatan --</option>
                                @foreach($subdistricts as $sub)
                                    <option value="{{ $sub->id }}" {{ $sub->id == optional($puskesmas)->subdistrict_id ? 'selected' : '' }}>
                                        {{ $sub->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="modal_village_id">Kelurahan / Desa <span class="text-danger">*</span></label>
                            <select class="form-control" name="village_id" id="modal_village_id" required>
                                <option value="">-- Pilih Kelurahan/Desa --</option>
                                @foreach($availableVillages as $village)
                                    <option value="{{ $village->id }}">{{ $village->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="modal_rw">RW <span class="text-muted">(Opsional)</span></label>
                                <input type="text" class="form-control" name="rw" id="modal_rw" placeholder="Contoh: 01" maxlength="5">
                                <small class="text-muted">Kosongkan untuk semua RW di desa ini</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="modal_rt">RT <span class="text-muted">(Opsional)</span></label>
                                <input type="text" class="form-control" name="rt" id="modal_rt" placeholder="Contoh: 02" maxlength="5">
                                <small class="text-muted">Kosongkan untuk semua RT</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success font-weight-bold">
                            <i class="fas fa-save mr-1"></i> Simpan Wilayah Binaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
            $('#modal_subdistrict_id').on('change', function() {
                let subdistrictId = $(this).val();
                let $villageSelect = $('#modal_village_id');
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
                            $villageSelect.empty().append('<option value="">Gagal memuat daftar desa</option>');
                        }
                    });
                } else {
                    $villageSelect.append('<option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>');
                }
            });
        });

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
