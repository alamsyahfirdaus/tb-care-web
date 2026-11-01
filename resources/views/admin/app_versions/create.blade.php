@extends('admin.layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-upload mr-2 text-primary"></i>Publikasikan Rilis Versi APK Baru
                </h3>
                <div class="card-tools">
                    <a href="{{ route('app-versions.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
            <form action="{{ route('app-versions.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <input type="hidden" name="platform" value="android">

                    <div class="row">
                        <!-- Nama Versi -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nama Versi (Version Name) <span class="text-danger">*</span></label>
                            <input type="text" name="version_name" class="form-control @error('version_name') is-invalid @enderror"
                                   placeholder="Contoh: 1.0.1" value="{{ old('version_name') }}" required>
                            <small class="text-muted">Format standar semver: Mayor.Minor.Patch (contoh: 1.0.1).</small>
                        </div>
                        <!-- Version Code -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Version Code (Build Number) <span class="text-danger">*</span></label>
                            <input type="number" name="version_code" class="form-control @error('version_code') is-invalid @enderror"
                                   placeholder="Contoh: 2" value="{{ old('version_code', $suggestedVersionCode) }}" min="1" required>
                            <small class="text-muted">Bilangan bulat positif yang meningkat tiap build (contoh: 2).</small>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Min Supported Version Code -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Batas Minimum Version Code yang Didukung <span class="text-danger">*</span></label>
                            <input type="number" name="min_supported_version_code" class="form-control @error('min_supported_version_code') is-invalid @enderror"
                                   placeholder="Contoh: 1" value="{{ old('min_supported_version_code', $suggestedMinVersionCode) }}" min="1" required>
                            <small class="text-muted">Jika version code ponsel di bawah angka ini, aplikasi akan <strong>mewajibkan update</strong>.</small>
                        </div>
                        <!-- Judul Pembaruan -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Judul Pembaruan (Opsional)</label>
                            <input type="text" name="title" class="form-control"
                                   placeholder="Contoh: Pembaruan Stabilitas TB Care v1.0.1" value="{{ old('title') }}">
                            <small class="text-muted">Judul pengumuman pada dialog aplikasi mobile.</small>
                        </div>
                    </div>

                    <hr class="my-3">

                    <h5 class="text-primary font-weight-bold mb-3"><i class="fas fa-file-archive mr-1"></i> File APK & Lokasi Unduhan</h5>

                    <div class="form-group">
                        <label class="font-weight-bold">Unggah File APK Rilis (Disarankan)</label>
                        <div class="custom-file">
                            <input type="file" name="apk_file" class="custom-file-input @error('apk_file') is-invalid @enderror" id="apkFileInput" accept=".apk">
                            <label class="custom-file-label" for="apkFileInput">Pilih file APK (.apk, maks 100MB)...</label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            File akan disimpan secara aman di storage server. Checksum SHA-256 dan ukuran file akan <strong>dihitung otomatis</strong>.
                        </small>
                    </div>

                    <div class="text-center font-weight-bold text-muted my-2">-- ATAU --</div>

                    <div class="form-group">
                        <label class="font-weight-bold">URL Unduhan Eksternal (Jika dihosting di Cloud/CDN terpisah)</label>
                        <input type="url" name="download_url" class="form-control @error('download_url') is-invalid @enderror"
                               placeholder="https://example.com/downloads/tbcare.apk" value="{{ old('download_url') }}">
                        <small class="text-muted">Gunakan opsi ini jika APK dihosting pada GitHub Releases, Google Drive langsung, atau storage CDN.</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">SHA-256 Checksum (Wajib diisi jika menggunakan URL Eksternal)</label>
                        <input type="text" name="sha256_checksum" class="form-control text-monospace"
                               placeholder="64 karakter hash heksadesimal" value="{{ old('sha256_checksum') }}" maxlength="64">
                        <small class="text-muted">Digunakan oleh aplikasi mobile untuk memvalidasi integritas file APK sebelum dipasang.</small>
                    </div>

                    <hr class="my-3">

                    <!-- Catatan Rilis -->
                    <div class="form-group">
                        <label class="font-weight-bold">Catatan Rilis (Release Notes) <span class="text-danger">*</span></label>
                        <textarea name="release_notes" rows="4" class="form-control" placeholder="- Perbaikan sistem pengingat minum obat&#10;- Peningkatan stabilitas koneksi&#10;- Fitur pemeriksaan versi mandiri">{{ old('release_notes') }}</textarea>
                        <small class="text-muted">Catatan pembaruan yang akan dibaca oleh pengguna pada dialog update.</small>
                    </div>

                    <!-- Kebijakan & Opsi -->
                    <div class="row bg-light p-3 rounded mx-0 border">
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="forceUpdateCheck" name="is_force_update" value="1" {{ old('is_force_update') ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-danger" for="forceUpdateCheck">
                                    Wajibkan Pembaruan (Force Update)
                                </label>
                                <small class="text-muted d-block">Pengguna tidak dapat menggunakan aplikasi sebelum memperbarui versi ini.</small>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2 mt-md-0">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="activeCheck" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success" for="activeCheck">
                                    Aktifkan Rilis Ini Sekarang
                                </label>
                                <small class="text-muted d-block">Versi aktif terkini akan langsung disajikan kepada aplikasi mobile.</small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('app-versions.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="fas fa-paper-plane mr-1"></i> Rilis Versi Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#apkFileInput').on('change', function () {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName || 'Pilih file APK (.apk, maks 100MB)...');
        });
    });
</script>
@endpush
