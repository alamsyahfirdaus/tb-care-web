@extends('admin.layouts.app')

@section('content')
<div class="row mb-3">
    <!-- Stat 1: Versi Terbaru -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-primary"><i class="fab fa-android"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted">Versi Aktif Terkini</span>
                <span class="info-box-number text-primary">
                    {{ $latestActive ? 'v' . $latestActive->version_name . ' (Build ' . $latestActive->version_code . ')' : 'Belum Ada' }}
                </span>
            </div>
        </div>
    </div>
    <!-- Stat 2: Batas Minimum -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-warning"><i class="fas fa-shield-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted">Min. Version Code</span>
                <span class="info-box-number text-dark">
                    {{ $latestActive ? 'Build ' . $latestActive->min_supported_version_code : '-' }}
                </span>
            </div>
        </div>
    </div>
    <!-- Stat 3: Kebijakan Update -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon {{ $latestActive && $latestActive->is_force_update ? 'bg-danger' : 'bg-success' }}">
                <i class="fas {{ $latestActive && $latestActive->is_force_update ? 'fa-exclamation-triangle' : 'fa-check-circle' }}"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text text-muted">Kebijakan Update</span>
                <span class="info-box-number">
                    @if($latestActive)
                        {!! $latestActive->is_force_update ? '<span class="text-danger font-weight-bold">Wajib (Force)</span>' : '<span class="text-success font-weight-bold">Opsional</span>' !!}
                    @else
                        -
                    @endif
                </span>
            </div>
        </div>
    </div>
    <!-- Stat 4: Total Rilis -->
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-info"><i class="fas fa-code-branch"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted">Total Riwayat Rilis</span>
                <span class="info-box-number text-info">{{ $versions->total() }} Rilis</span>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-mobile-alt mr-2 text-primary"></i>Daftar Versi Rilis APK Mobile
        </h3>
        <div class="card-tools ml-auto">
            <a href="{{ route('app-versions.create') }}" class="btn btn-primary btn-sm font-weight-bold">
                <i class="fas fa-plus-circle mr-1"></i> Rilis Versi Baru
            </a>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Versi & Build</th>
                    <th>Batas Min. Build</th>
                    <th class="text-center">Tipe Pembaruan</th>
                    <th>Ukuran & Checksum</th>
                    <th class="text-center">Status</th>
                    <th>Tanggal Rilis</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($versions as $index => $item)
                <tr>
                    <td class="text-center font-weight-bold">{{ $versions->firstItem() + $index }}</td>
                    <td>
                        <div class="font-weight-bold text-dark">
                            v{{ $item->version_name }}
                            @if($latestActive && $latestActive->id == $item->id)
                                <span class="badge badge-primary ml-1">Terkini</span>
                            @endif
                        </div>
                        <small class="text-muted"><i class="fas fa-hashtag mr-1"></i>Version Code: {{ $item->version_code }}</small>
                    </td>
                    <td>
                        <span class="badge badge-light border font-weight-bold">Build {{ $item->min_supported_version_code }}</span>
                    </td>
                    <td class="text-center">
                        @if($item->is_force_update)
                            <span class="badge badge-danger px-2 py-1"><i class="fas fa-lock mr-1"></i>Wajib (Force)</span>
                        @else
                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Opsional</span>
                        @endif
                    </td>
                    <td>
                        <div><small class="font-weight-bold">{{ $item->formatted_file_size }}</small></div>
                        @if($item->sha256_checksum)
                            <small class="text-muted text-monospace font-size-xs" title="SHA-256: {{ $item->sha256_checksum }}">
                                <i class="fas fa-fingerprint mr-1 text-info"></i>{{ substr($item->sha256_checksum, 0, 12) }}...{{ substr($item->sha256_checksum, -6) }}
                            </small>
                        @else
                            <small class="text-muted">-</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <form action="{{ route('app-versions.toggle_active', $item->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs {{ $item->is_active ? 'btn-success' : 'btn-secondary' }}" title="Klik untuk mengubah status">
                                <i class="fas {{ $item->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }} mr-1"></i>
                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <small class="text-dark">{{ $item->published_at ? $item->published_at->format('d M Y, H:i') : '-' }}</small>
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            @if($item->resolved_download_url)
                                <a href="{{ $item->resolved_download_url }}" class="btn btn-outline-primary btn-sm" title="Unduh File APK" target="_blank">
                                    <i class="fas fa-download"></i>
                                </a>
                            @endif
                            <a href="{{ route('app-versions.edit', $item->id) }}" class="btn btn-outline-info btn-sm" title="Edit Metadata">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('app-versions.destroy', $item->id) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete" title="Hapus Rilis">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle fa-2x mb-2 text-secondary"></i>
                        <p class="mb-0">Belum ada versi rilis yang terdaftar. Klik <strong>Rilis Versi Baru</strong> untuk memublikasikan update APK.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($versions->hasPages())
    <div class="card-footer bg-white clearfix">
        {{ $versions->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('.btn-delete').on('click', function (e) {
            e.preventDefault();
            const form = $(this).closest('form');
            Swal.fire({
                title: 'Hapus Rilis Versi Ini?',
                text: 'File APK fisik dan riwayat pembaruan ini akan dihapus secara permanen.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush
