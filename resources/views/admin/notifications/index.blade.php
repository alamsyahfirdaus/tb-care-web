@extends('admin.layouts.app')

@section('content')
<!-- Stats Row -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalAll }}</h3>
                <p>Total Notifikasi</p>
            </div>
            <div class="icon">
                <i class="fas fa-bell"></i>
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="small-box-footer">Semua Notifikasi <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalBroadcast }}</h3>
                <p>Broadcast Pengumuman</p>
            </div>
            <div class="icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <a href="{{ route('admin.notifications.index', ['type' => 'Pengumuman']) }}" class="small-box-footer">Filter Pengumuman <i class="fas fa-arrow-circle-right"></i></a>
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
            <a href="{{ route('admin.notifications.index', ['type' => 'Pengingat Minum Obat']) }}" class="small-box-footer">Filter Pengingat <i class="fas fa-arrow-circle-right"></i></a>
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
            <a href="{{ route('admin.notifications.index', ['type' => 'Peringatan Kasus']) }}" class="small-box-footer">Filter Peringatan <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter & Buat Notifikasi</h3>
        <div class="card-tools">
            <a href="{{ route('admin.notifications.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-paper-plane mr-1"></i> Buat / Kirim Notifikasi Baru
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.notifications.index') }}" class="row">
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
                <a href="{{ route('admin.notifications.index') }}" class="btn btn-default"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card card-outline card-navy">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-list mr-1 text-navy"></i> Riwayat Pengiriman Notifikasi</h3>
        <div class="card-tools">
            <span class="badge badge-secondary p-2">Total: {{ $notifications->count() }} Pesan</span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="notificationsTable" class="table table-bordered table-hover data-table align-middle mb-0">
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
                                <a href="{{ route('admin.notifications.show', $notif->id) }}" class="btn btn-info" title="Lihat Rincian">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-danger" title="Hapus Notifikasi" onclick="confirmDelete('{{ route('admin.notifications.destroy', $notif->id) }}', '{{ addslashes($notif->title) }}')">
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
