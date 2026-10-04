@extends('admin.layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas {{ $material->material_type == 'video' ? 'fab fa-youtube text-danger' : 'fas fa-image text-primary' }} mr-1"></i>
                    {{ $material->title_material }}
                </h3>
                <div class="card-tools">
                    @if($material->is_publish)
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Terpublikasi</span>
                    @else
                        <span class="badge badge-secondary px-2 py-1"><i class="fas fa-eye-slash mr-1"></i>Draft</span>
                    @endif
                </div>
            </div>

            <!-- Media Preview Body -->
            <div class="card-body p-0">
                @if($material->material_type == 'video')
                    @if($material->embed_url)
                        <div class="embed-responsive embed-responsive-16by9 bg-dark">
                            <iframe class="embed-responsive-item" src="{{ $material->embed_url }}" allowfullscreen></iframe>
                        </div>
                    @else
                        <div class="p-5 text-center bg-light">
                            <i class="fab fa-youtube fa-4x text-danger mb-3"></i>
                            <p class="text-muted">URL Video: <a href="{{ $material->video_url }}" target="_blank">{{ $material->video_url }}</a></p>
                        </div>
                    @endif
                @else
                    <div class="p-3 text-center bg-light">
                        @if($material->image_url)
                            <img src="{{ $material->image_url }}" alt="{{ $material->title_material }}" class="img-fluid rounded shadow-sm" style="max-height: 550px;">
                        @else
                            <div class="py-5 text-muted">
                                <i class="fas fa-image fa-4x text-secondary mb-3"></i>
                                <p>Poster / Infografis tidak memiliki file gambar fisik.</p>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="p-4">
                    <h5 class="font-weight-bold text-dark border-bottom pb-2">Deskripsi & Isi Materi</h5>
                    <div class="text-dark" style="line-height: 1.8; font-size: 1.05rem;">
                        {!! nl2br(e($material->description ?: 'Tidak ada rincian deskripsi materi edukasi.')) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Metadata & Controls -->
    <div class="col-md-4">
        <div class="card card-outline card-secondary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-info-circle mr-1"></i> Informasi Materi</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Tipe Materi</b> 
                        <span class="float-right badge {{ $material->material_type == 'video' ? 'badge-danger' : 'badge-primary' }} px-2 py-1">
                            {{ $material->material_type == 'video' ? 'Video YouTube' : 'Poster / Gambar' }}
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Status Publikasi</b> 
                        <span class="float-right badge {{ $material->is_publish ? 'badge-success' : 'badge-secondary' }} px-2 py-1">
                            {{ $material->is_publish ? 'Tayang di Aplikasi' : 'Draft (Nonaktif)' }}
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Pembuat / Penulis</b> 
                        <span class="float-right text-muted">{{ $material->author->name ?? 'Admin TB Care' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Tanggal Dibuat</b> 
                        <span class="float-right text-muted">{{ $material->created_at ? $material->created_at->translatedFormat('d M Y, H:i') : '-' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Terakhir Diperbarui</b> 
                        <span class="float-right text-muted">{{ $material->updated_at ? $material->updated_at->translatedFormat('d M Y, H:i') : '-' }}</span>
                    </li>
                    @if($material->video_url)
                    <li class="list-group-item">
                        <b>Tautan Sumber</b> 
                        <div class="mt-1">
                            <a href="{{ $material->video_url }}" target="_blank" class="small text-break">
                                <i class="fas fa-external-link-alt mr-1"></i>{{ $material->video_url }}
                            </a>
                        </div>
                    </li>
                    @endif
                </ul>

                <div class="mb-3">
                    <form action="{{ route('admin.education.toggle-publish', $material->encrypted_id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-block {{ $material->is_publish ? 'btn-outline-secondary' : 'btn-success' }} mb-2">
                            <i class="fas {{ $material->is_publish ? 'fa-eye-slash' : 'fa-check' }} mr-1"></i>
                            {{ $material->is_publish ? 'Ubah Menjadi Draft (Sembunyikan)' : 'Terbitkan Konten ke Aplikasi' }}
                        </button>
                    </form>

                    <a href="{{ route('admin.education.edit', $material->encrypted_id) }}" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-pencil-alt mr-1"></i> Edit Materi
                    </a>

                    <button type="button" class="btn btn-danger btn-block mb-2" onclick="confirmDelete('{{ route('admin.education.destroy', $material->encrypted_id) }}', '{{ addslashes($material->title_material) }}')">
                        <i class="fas fa-trash mr-1"></i> Hapus Konten
                    </button>

                    <a href="{{ route('admin.education.index') }}" class="btn btn-default btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                    </a>
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
        title: 'Hapus Materi Edukasi?',
        html: `Apakah Anda yakin ingin menghapus materi <b>${title}</b>?`,
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
