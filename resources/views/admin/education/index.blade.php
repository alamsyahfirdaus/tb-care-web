@extends('admin.layouts.app')

@section('content')
<!-- Summary Stat Boxes -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalAll }}</h3>
                <p>Total Konten Edukasi</p>
            </div>
            <div class="icon">
                <i class="fas fa-book-reader"></i>
            </div>
            <a href="{{ route('admin.education.index') }}" class="small-box-footer">Semua Konten <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $totalVideo }}</h3>
                <p>Video Edukasi (YouTube)</p>
            </div>
            <div class="icon">
                <i class="fab fa-youtube"></i>
            </div>
            <a href="{{ route('admin.education.index', ['type' => 'video']) }}" class="small-box-footer">Filter Video <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalImage }}</h3>
                <p>Poster & Infografis</p>
            </div>
            <div class="icon">
                <i class="fas fa-images"></i>
            </div>
            <a href="{{ route('admin.education.index', ['type' => 'image']) }}" class="small-box-footer">Filter Poster <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $totalPublish }}</h3>
                <p>Konten Terpublikasi</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="{{ route('admin.education.index', ['status' => '1']) }}" class="small-box-footer">Filter Terbit <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- Filter & Search Card -->
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter & Manajemen Konten</h3>
        <div class="card-tools">
            <a href="{{ route('admin.education.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus-circle mr-1"></i> Buat Konten Edukasi Baru
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.education.index') }}" class="row">
            <div class="col-md-3 mb-2">
                <label class="small text-muted font-weight-bold">Tipe Konten</label>
                <select name="type" class="form-control select2">
                    <option value="">-- Semua Tipe --</option>
                    <option value="video" {{ $typeFilter == 'video' ? 'selected' : '' }}>Video (YouTube)</option>
                    <option value="image" {{ $typeFilter == 'image' ? 'selected' : '' }}>Poster / Gambar</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="small text-muted font-weight-bold">Status Publikasi</label>
                <select name="status" class="form-control select2">
                    <option value="">-- Semua Status --</option>
                    <option value="1" {{ $statusFilter === '1' ? 'selected' : '' }}>Terpublikasi</option>
                    <option value="0" {{ $statusFilter === '0' ? 'selected' : '' }}>Draft (Disembunyikan)</option>
                </select>
            </div>
            <div class="col-md-4 mb-2">
                <label class="small text-muted font-weight-bold">Kata Kunci Judul / Deskripsi</label>
                <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari judul materi...">
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary mr-1 flex-fill"><i class="fas fa-search"></i> Cari</button>
                <a href="{{ route('admin.education.index') }}" class="btn btn-default"><i class="fas fa-undo"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card card-outline card-primary card-tabs">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="educationViewTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active font-weight-bold" id="tab-table-view" data-toggle="pill" href="#tableView" role="tab">
                    <i class="fas fa-table mr-1 text-primary"></i> Tabel Data ({{ $materials->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold" id="tab-grid-view" data-toggle="pill" href="#gridView" role="tab">
                    <i class="fas fa-th-large mr-1 text-success"></i> Galeri / Kartu
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="educationViewTabsContent">
            
            <!-- TAB 1: DATATABLES -->
            <div class="tab-pane fade show active" id="tableView" role="tabpanel">
                <div class="table-responsive">
                    <table id="educationTable" class="table table-bordered table-hover data-table">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th style="width: 70px;" class="text-center">Media</th>
                                <th>Judul Materi Edukasi</th>
                                <th class="text-center">Tipe</th>
                                <th>Penulis / Pembuat</th>
                                <th>Tanggal Terbit</th>
                                <th class="text-center">Status</th>
                                <th style="width: 140px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($materials as $item)
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td class="text-center">
                                    @if($item->material_type == 'video')
                                        <span class="badge badge-danger p-2"><i class="fab fa-youtube fa-lg"></i></span>
                                    @elseif($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="" class="img-thumbnail" style="width: 50px; height: 35px; object-fit: cover;">
                                    @else
                                        <span class="badge badge-primary p-2"><i class="fas fa-image fa-lg"></i></span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.education.show', $item->id) }}" class="font-weight-bold text-dark">
                                        {{ $item->title_material }}
                                    </a>
                                    <div class="text-xs text-muted" style="display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $item->description ?: '-' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($item->material_type == 'video')
                                        <span class="badge badge-danger px-2 py-1"><i class="fab fa-youtube mr-1"></i>Video</span>
                                    @else
                                        <span class="badge badge-primary px-2 py-1"><i class="fas fa-image mr-1"></i>Poster</span>
                                    @endif
                                </td>
                                <td>{{ $item->author->name ?? 'Admin TB Care' }}</td>
                                <td data-order="{{ $item->created_at ? $item->created_at->timestamp : 0 }}">
                                    {{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}
                                </td>
                                <td class="text-center" data-order="{{ $item->is_publish ? 1 : 0 }}">
                                    @if($item->is_publish)
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Terbit</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1"><i class="fas fa-eye-slash mr-1"></i>Draft</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.education.show', $item->id) }}" class="btn btn-info" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.education.edit', $item->id) }}" class="btn btn-warning" title="Edit Konten">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger" title="Hapus Konten" onclick="confirmDelete('{{ route('admin.education.destroy', $item->id) }}', '{{ addslashes($item->title_material) }}')">
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

            <!-- TAB 2: GRID / CARD VIEW -->
            <div class="tab-pane fade" id="gridView" role="tabpanel">
                <div class="row">
                    @forelse($materials as $item)
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="card h-100 shadow-sm border-top border-{{ $item->material_type == 'video' ? 'danger' : 'primary' }}" style="border-top-width: 4px !important;">
                            <!-- Media Preview Header -->
                            <div class="position-relative bg-dark text-center" style="height: 180px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                @if($item->material_type == 'video')
                                    @if($item->embed_url)
                                        <iframe width="100%" height="180" src="{{ $item->embed_url }}" frameborder="0" allowfullscreen style="pointer-events: none;"></iframe>
                                        <div class="position-absolute" style="top: 10px; right: 10px;">
                                            <span class="badge badge-danger px-2 py-1"><i class="fab fa-youtube mr-1"></i>Video</span>
                                        </div>
                                    @else
                                        <div class="p-4 text-center text-white">
                                            <i class="fab fa-youtube fa-3x text-danger mb-2"></i>
                                            <div>Video YouTube</div>
                                        </div>
                                    @endif
                                @else
                                    @if($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->title_material }}" style="width: 100%; height: 180px; object-fit: cover;">
                                    @else
                                        <div class="p-4 text-center text-white">
                                            <i class="fas fa-file-image fa-3x text-primary mb-2"></i>
                                            <div>Poster / Infografis</div>
                                        </div>
                                    @endif
                                    <div class="position-absolute" style="top: 10px; right: 10px;">
                                        <span class="badge badge-primary px-2 py-1"><i class="fas fa-image mr-1"></i>Poster</span>
                                    </div>
                                @endif
                                <div class="position-absolute" style="bottom: 10px; left: 10px;">
                                    @if($item->is_publish)
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Terbit</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1"><i class="fas fa-eye-slash mr-1"></i>Draft</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Body Content -->
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title font-weight-bold text-dark mb-2" style="font-size: 1.05rem;">
                                    <a href="{{ route('admin.education.show', $item->id) }}" class="text-dark text-decoration-none">
                                        {{ $item->title_material }}
                                    </a>
                                </h5>
                                <p class="card-text text-muted small flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    {{ $item->description ?: 'Tidak ada deskripsi tambahan.' }}
                                </p>
                                <div class="text-muted small mt-2 pt-2 border-top d-flex justify-content-between">
                                    <span><i class="fas fa-user-edit mr-1"></i>{{ $item->author->name ?? 'Admin TB Care' }}</span>
                                    <span><i class="far fa-calendar-alt mr-1"></i>{{ $item->created_at ? $item->created_at->format('d/m/Y') : '-' }}</span>
                                </div>
                            </div>

                            <!-- Card Actions Footer -->
                            <div class="card-footer bg-light p-2 d-flex justify-content-between align-items-center">
                                <form action="{{ route('admin.education.toggle-publish', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-xs {{ $item->is_publish ? 'btn-outline-secondary' : 'btn-outline-success' }}" title="Ubah status publikasi">
                                        <i class="fas {{ $item->is_publish ? 'fa-eye-slash' : 'fa-check' }}"></i> {{ $item->is_publish ? 'Jadikan Draft' : 'Terbitkan' }}
                                    </button>
                                </form>

                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.education.show', $item->id) }}" class="btn btn-info" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.education.edit', $item->id) }}" class="btn btn-warning" title="Edit Konten">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger" title="Hapus Konten" onclick="confirmDelete('{{ route('admin.education.destroy', $item->id) }}', '{{ addslashes($item->title_material) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 py-5 text-center text-muted">
                        <i class="fas fa-book-open fa-3x mb-3 text-secondary"></i>
                        <p class="font-weight-bold">Tidak ada konten materi edukasi yang cocok dengan kriteria filter.</p>
                        <a href="{{ route('admin.education.create') }}" class="btn btn-primary mt-2">
                            <i class="fas fa-plus-circle mr-1"></i> Buat Konten Sekarang
                        </a>
                    </div>
                    @endforelse
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
        html: `Apakah Anda yakin ingin menghapus materi <b>${title}</b>?<br><small class="text-danger">File gambar/poster yang tersimpan juga akan dihapus permanen.</small>`,
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
