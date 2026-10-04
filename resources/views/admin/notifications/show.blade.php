@extends('admin.layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-envelope-open-text mr-1 text-primary"></i> Detail Pesan Notifikasi
                </h3>
                <div class="card-tools">
                    @if($notification->status == 'Terkirim')
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Terkirim</span>
                    @else
                        <span class="badge badge-secondary px-2 py-1"><i class="fas fa-pencil-alt mr-1"></i>Draft</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="border-bottom pb-3 mb-3">
                    <h4 class="font-weight-bold text-dark mb-1">{{ $notification->title }}</h4>
                    <div class="text-muted small">
                        <span><i class="fas fa-user-edit mr-1"></i>Pengirim: <strong>{{ $notification->sender->name ?? 'Sistem TB Care' }}</strong></span> &bull; 
                        <span><i class="far fa-clock mr-1"></i>Waktu: <strong>{{ $notification->created_at ? $notification->created_at->translatedFormat('d F Y, H:i') : '-' }} WIB</strong></span>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="bg-light p-3 rounded border text-center">
                            <span class="text-muted small d-block mb-1">Tipe Pesan</span>
                            <span class="badge badge-primary px-2 py-1">{{ $notification->type }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-3 rounded border text-center">
                            <span class="text-muted small d-block mb-1">Target Segmen</span>
                            <span class="badge badge-info px-2 py-1">{{ $notification->target_role }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-3 rounded border text-center">
                            <span class="text-muted small d-block mb-1">Total Penerima</span>
                            <span class="badge badge-success px-2 py-1">{{ $notification->sent_count }} Pengguna</span>
                        </div>
                    </div>
                </div>

                @if($notification->puskesmas)
                <div class="alert alert-info py-2">
                    <i class="fas fa-clinic-medical mr-1"></i> Ditargetkan khusus untuk wilayah binaan: <strong>{{ $notification->puskesmas->name }}</strong>
                </div>
                @endif

                <div class="p-4 bg-light rounded border mb-4">
                    <label class="font-weight-bold text-dark mb-2"><i class="fas fa-comment-alt mr-1"></i> Isi Notifikasi:</label>
                    <div class="text-dark" style="line-height: 1.8; white-space: pre-wrap; font-size: 1.05rem;">{{ $notification->message }}</div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Notifikasi
                    </a>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete('{{ route('admin.notifications.destroy', $notification->encrypted_id) }}', '{{ addslashes($notification->title) }}')">
                        <i class="fas fa-trash mr-1"></i> Hapus Notifikasi
                    </button>
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
