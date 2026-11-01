@extends('admin.layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-9">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-edit mr-2 text-info"></i>Edit Konfigurasi Versi v{{ $version->version_name }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('app-versions.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
            <form action="{{ route('app-versions.update', $version->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
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

                    <div class="row">
                        <!-- Nama Versi -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nama Versi (Version Name) <span class="text-danger">*</span></label>
                            <input type="text" name="version_name" class="form-control"
                                   value="{{ old('version_name', $version->version_name) }}" required>
                        </div>
                        <!-- Version Code -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Version Code (Build Number)</label>
                            <input type="number" class="form-control bg-light"
                                   value="{{ $version->version_code }}" readonly>
                            <small class="text-muted">Version code tidak dapat diubah setelah dirilis untuk menjaga integritas urutan pembaruan.</small>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Min Supported Version Code -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Batas Minimum Version Code yang Didukung <span class="text-danger">*</span></label>
                            <input type="number" name="min_supported_version_code" class="form-control"
                                   value="{{ old('min_supported_version_code', $version->min_supported_version_code) }}" min="1" required>
                        </div>
                        <!-- Judul Pembaruan -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Judul Pembaruan</label>
                            <input type="text" name="title" class="form-control"
                                   value="{{ old('title', $version->title) }}">
                        </div>
                    </div>

                    <hr class="my-3">

                    <h5 class="text-info font-weight-bold mb-3"><i class="fas fa-file-archive mr-1"></i> Berkas APK & Tautan</h5>

                    @if($version->apk_path)
                    <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <i class="fab fa-android text-success mr-2"></i>
                            <strong>Berkas tersimpan di server:</strong> <code>{{ basename($version->apk_path) }}</code> ({{ $version->formatted_file_size }})
                        </div>
                        <a href="{{ $version->resolved_download_url }}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-download mr-1"></i> Unduh File Saat Ini
                        </a>
                    </div>
                    @endif

                    <div class="form-group">
                        <label class="font-weight-bold">Ganti File APK (Biarkan kosong jika tidak ingin mengganti)</label>
                        <div class="custom-file">
                            <input type="file" name="apk_file" class="custom-file-input" id="apkFileInput" accept=".apk">
                            <label class="custom-file-label" for="apkFileInput">Pilih file APK baru...</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">URL Unduhan Eksternal</label>
                        <input type="url" name="download_url" class="form-control"
                               value="{{ old('download_url', $version->download_url) }}">
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">SHA-256 Checksum</label>
                        <input type="text" name="sha256_checksum" class="form-control text-monospace"
                               value="{{ old('sha256_checksum', $version->sha256_checksum) }}" maxlength="64">
                    </div>

                    <hr class="my-3">

                    <!-- Catatan Rilis -->
                    <div class="form-group">
                        <label class="font-weight-bold">Catatan Rilis (Release Notes) <span class="text-danger">*</span></label>
                        <textarea name="release_notes" rows="4" class="form-control">{{ old('release_notes', $version->release_notes) }}</textarea>
                    </div>

                    <!-- Kebijakan & Opsi -->
                    <div class="row bg-light p-3 rounded mx-0 border">
                        <div class="col-md-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="forceUpdateCheck" name="is_force_update" value="1"
                                       {{ old('is_force_update', $version->is_force_update) ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-danger" for="forceUpdateCheck">
                                    Wajibkan Pembaruan (Force Update)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2 mt-md-0">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="activeCheck" name="is_active" value="1"
                                       {{ old('is_active', $version->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success" for="activeCheck">
                                    Status Aktif
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('app-versions.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-info font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
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
            $(this).next('.custom-file-label').addClass("selected").html(fileName || 'Pilih file APK baru...');
        });
    });
</script>
@endpush
