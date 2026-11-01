@extends('admin.layouts.app')

@section('content')
<!-- Stats Row -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $userNotifications->total() ?? 0 }}</h3>
                <p>Notifikasi Masuk (Aktivitas Pasien)</p>
            </div>
            <div class="icon">
                <i class="fas fa-inbox"></i>
            </div>
            <a href="#tab-inbox" data-toggle="pill" class="small-box-footer">Lihat Aktivitas Pasien <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 class="text-white">{{ $unreadUserCount ?? 0 }}</h3>
                <p class="text-white">Belum Dibaca</p>
            </div>
            <div class="icon">
                <i class="fas fa-envelope"></i>
            </div>
            <a href="#tab-inbox" data-toggle="pill" class="small-box-footer">Perlu Respon <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $totalReminder }}</h3>
                <p>Pengingat Minum Obat</p>
            </div>
            <div class="icon">
                <i class="fas fa-pills"></i>
            </div>
            <a href="{{ route('notifications.index', ['type' => 'Pengingat Minum Obat']) }}" class="small-box-footer">Filter Pengingat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $totalClinical }}</h3>
                <p>Peringatan Kasus TB</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <a href="{{ route('notifications.index', ['type' => 'Peringatan Kasus']) }}" class="small-box-footer">Filter Peringatan <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- Main Tabs Card -->
<div class="card card-primary card-outline card-outline-tabs shadow-sm">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" id="notif-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active font-weight-bold" id="tab-inbox-tab" data-toggle="pill" href="#tab-inbox" role="tab">
                    <i class="fas fa-inbox mr-1 text-primary"></i> Notifikasi Masuk (Aktivitas Pasien)
                    @if(($unreadUserCount ?? 0) > 0)
                        <span class="badge badge-danger ml-1 font-weight-bold">{{ $unreadUserCount }} Baru</span>
                    @endif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="tab-broadcast-tab" data-toggle="pill" href="#tab-broadcast" role="tab">
                    <i class="fas fa-bullhorn mr-1 text-info"></i> Broadcast & Pengumuman Sistem ({{ $notifications->count() }})
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="notif-tabContent">
            
            <!-- TAB 1: NOTIFIKASI MASUK AKTIVITAS PASIEN -->
            <div class="tab-pane fade show active" id="tab-inbox" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="font-weight-bold text-dark mb-0">
                            <i class="fas fa-bell mr-1 text-warning"></i> Aktivitas Minum Obat Pasien Terpantau
                        </h5>
                        <small class="text-muted">Notifikasi otomatis yang dikirim ke Anda saat pasien yang menjadi wewenang Anda mencatat minum obat.</small>
                    </div>
                    @if(($unreadUserCount ?? 0) > 0)
                        <form method="POST" action="{{ route('notifications.mark_all_read') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary font-weight-bold shadow-sm">
                                <i class="fas fa-check-double mr-1"></i> Tandai Semua Sudah Dibaca
                            </button>
                        </form>
                    @endif
                </div>

                @if($userNotifications->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100 mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">No</th>
                                    <th style="width: 140px;">Waktu</th>
                                    <th style="width: 180px;">Pasien</th>
                                    <th>Detail Pemberitahuan & Keterangan</th>
                                    <th style="width: 120px;" class="text-center">Status</th>
                                    <th style="width: 130px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userNotifications as $idx => $n)
                                    @php
                                        $d = $n->data;
                                        $isUnread = is_null($n->read_at);
                                    @endphp
                                    <tr class="{{ $isUnread ? 'bg-light font-weight-semibold' : '' }}">
                                        <td class="text-center text-muted">{{ $loop->iteration + ($userNotifications->currentPage() - 1) * $userNotifications->perPage() }}</td>
                                        <td>
                                            <span class="font-weight-bold text-dark d-block">
                                                {{ $n->created_at ? $n->created_at->timezone('Asia/Jakarta')->format('d/m/Y') : '-' }}
                                            </span>
                                            <small class="text-muted d-block">
                                                {{ $n->created_at ? $n->created_at->timezone('Asia/Jakarta')->format('H:i') : '' }} WIB
                                            </small>
                                            <small class="text-primary">{{ $n->created_at ? $n->created_at->diffForHumans() : '' }}</small>
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $d['patient_name'] ?? 'Pasien TB' }}</strong>
                                            @if(!empty($d['patient_nik']))
                                                <div class="text-xs text-muted">NIK: {{ $d['patient_nik'] }}</div>
                                            @endif
                                            @if(!empty($d['puskesmas_name']))
                                                <div class="text-xs text-info"><i class="fas fa-clinic-medical mr-1"></i>{{ $d['puskesmas_name'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <span class="badge badge-success px-2 py-1 mr-1">
                                                    <i class="fas fa-pills mr-1"></i> {{ $d['title'] ?? 'Pasien Telah Minum Obat' }}
                                                </span>
                                                @if(!empty($d['is_late']))
                                                    <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i>Terlambat</span>
                                                @else
                                                    <span class="badge badge-info px-2 py-1"><i class="fas fa-check mr-1"></i>Tepat Waktu</span>
                                                @endif
                                            </div>
                                            <p class="mb-1 text-sm text-dark">{{ $d['message'] ?? '-' }}</p>
                                            @if(!empty($d['notes']))
                                                <div class="p-2 rounded bg-white border border-info text-xs mt-1">
                                                    <strong class="text-info"><i class="fas fa-sticky-note mr-1"></i> Keterangan Pasien:</strong>
                                                    <div class="text-dark mt-1">"{{ $d['notes'] }}"</div>
                                                </div>
                                            @else
                                                <div class="text-xs text-muted mt-1"><i class="fas fa-info-circle mr-1"></i> Tidak ada keterangan tambahan (-)</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($isUnread)
                                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-envelope mr-1"></i>Belum Dibaca</span>
                                            @else
                                                <span class="badge badge-light border text-muted px-2 py-1"><i class="fas fa-envelope-open mr-1"></i>Sudah Dibaca</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('notifications.read', $n->id) }}" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
                                                <i class="fas fa-external-link-alt mr-1"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $userNotifications->links() }}
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-bell-slash fa-3x mb-3 text-secondary"></i>
                        <h6 class="font-weight-bold">Belum Ada Notifikasi Masuk</h6>
                        <p class="text-sm">Saat pasien binaan mencatat minum obat, notifikasi akan langsung muncul di sini.</p>
                    </div>
                @endif
            </div>

            <!-- TAB 2: BROADCAST & PENGUMUMAN SISTEM -->
            <div class="tab-pane fade" id="tab-broadcast" role="tabpanel">
                <!-- Filter Card -->
                <div class="card card-light border mb-3">
                    <div class="card-header py-2">
                        <h6 class="card-title font-weight-bold text-sm mb-0"><i class="fas fa-filter mr-1"></i> Filter Broadcast Pengumuman</h6>
                        <div class="card-tools">
                            <a href="{{ route('notifications.create') }}" class="btn btn-xs btn-primary">
                                <i class="fas fa-paper-plane mr-1"></i> Kirim Broadcast Baru
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <form method="GET" action="{{ route('notifications.index') }}" class="row">
                            <div class="col-md-3 mb-2">
                                <label class="small text-muted font-weight-bold">Tipe Notifikasi</label>
                                <select name="type" class="form-control select2">
                                    <option value="">-- Semua Tipe --</option>
                                    <option value="Pengumuman" {{ $typeFilter == 'Pengumuman' ? 'selected' : '' }}>Pengumuman Umum</option>
                                    <option value="Pengingat Minum Obat" {{ $typeFilter == 'Pengingat Minum Obat' ? 'selected' : '' }}>Pengingat Minum Obat</option>
                                    <option value="Peringatan Kasus" {{ $typeFilter == 'Peringatan Kasus' ? 'selected' : '' }}>Peringatan Kasus Klinis</option>
                                    <option value="Edukasi TB" {{ $typeFilter == 'Edukasi TB' ? 'selected' : '' }}>Edukasi TB</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="small text-muted font-weight-bold">Target Penerima</label>
                                <select name="target" class="form-control select2">
                                    <option value="">-- Semua Target --</option>
                                    <option value="Semua" {{ $targetFilter == 'Semua' ? 'selected' : '' }}>Semua Pengguna</option>
                                    <option value="Pasien" {{ $targetFilter == 'Pasien' ? 'selected' : '' }}>Pasien TB</option>
                                    <option value="Petugas" {{ $targetFilter == 'Petugas' ? 'selected' : '' }}>Petugas / Nakes</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="small text-muted font-weight-bold">Kata Kunci Judul / Pesan</label>
                                <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari isi pesan atau judul...">
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary mr-1 flex-fill"><i class="fas fa-search"></i> Cari</button>
                                <a href="{{ route('notifications.index') }}" class="btn btn-default"><i class="fas fa-undo"></i></a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="notificationsTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>Judul Notifikasi</th>
                                <th>Ringkasan Pesan</th>
                                <th class="text-center">Tipe</th>
                                <th>Target Audience</th>
                                <th class="text-center">Penerima</th>
                                <th class="text-center">Status</th>
                                <th>Waktu Kirim</th>
                                <th style="width: 120px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $idx => $notif)
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td>
                                    <strong class="text-dark">{{ $notif->title }}</strong>
                                    <div class="small text-muted">Oleh: {{ $notif->sender->name ?? 'Sistem TB Care' }}</div>
                                </td>
                                <td>
                                    <div class="small text-muted text-truncate" style="max-width: 250px;">{{ $notif->message }}</div>
                                </td>
                                <td class="text-center">
                                    @if($notif->type == 'Pengingat Minum Obat')
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-pills mr-1"></i>Pengingat Obat</span>
                                    @elseif($notif->type == 'Peringatan Kasus')
                                        <span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>Peringatan</span>
                                    @else
                                        <span class="badge badge-primary px-2 py-1"><i class="fas fa-bullhorn mr-1"></i>Pengumuman</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light border font-weight-bold text-dark">{{ $notif->target_role }}</span>
                                    @if($notif->puskesmas)
                                        <div class="small text-muted"><i class="fas fa-clinic-medical mr-1"></i>{{ $notif->puskesmas->name }}</div>
                                    @endif
                                </td>
                                <td class="text-center" data-order="{{ $notif->sent_count }}">
                                    <span class="badge badge-secondary px-2 py-1">{{ $notif->sent_count }} Target</span>
                                </td>
                                <td class="text-center" data-order="{{ $notif->status }}">
                                    @if($notif->status == 'Terkirim')
                                        <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Terkirim</span>
                                    @else
                                        <span class="badge badge-warning"><i class="fas fa-pencil-alt mr-1"></i>Draft</span>
                                    @endif
                                </td>
                                <td data-order="{{ $notif->created_at ? $notif->created_at->timestamp : 0 }}">
                                    <small class="text-muted">{{ $notif->created_at ? $notif->created_at->format('d/m/Y, H:i') : '-' }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('notifications.show', $notif->encrypted_id) }}" class="btn btn-info" title="Lihat Rincian">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('notifications.edit', $notif->encrypted_id) }}" class="btn btn-warning" title="Edit Notifikasi">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger" title="Hapus Notifikasi" onclick="confirmDelete('{{ route('notifications.destroy', $notif->encrypted_id) }}', '{{ addslashes($notif->title) }}')">
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
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function confirmDelete(url, title) {
    Swal.fire({
        title: 'Hapus Notifikasi?',
        html: `Apakah Anda yakin ingin menghapus notifikasi <b>${title}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            let form = document.getElementById('delete-form');
            form.action = url;
            form.submit();
        }
    });
}
</script>
@endpush
