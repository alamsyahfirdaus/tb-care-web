@extends('admin.layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas {{ $isEdit ? 'fa-pencil-alt' : 'fa-plus-circle' }} mr-1 text-primary"></i>
                    {{ $isEdit ? 'Edit Materi Edukasi' : 'Tambah Materi Edukasi Baru' }}
                </h3>
            </div>
            <form action="{{ $isEdit ? route('admin.education.update', $material->encrypted_id) : route('admin.education.store') }}" 
                  method="POST" enctype="multipart/form-data">
                @csrf
                @if($isEdit)
                    @method('PUT')
                    <input type="hidden" name="encrypted_id" value="{{ $material->encrypted_id }}">
                @endif

                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Terjadi Kesalahan Validasi</h5>
                        <ul class="mb-0">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Judul Materi <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="title_material" class="form-control @error('title_material') is-invalid @enderror" 
                                   value="{{ old('title_material', $material->title_material) }}" 
                                   placeholder="Contoh: Pentingnya Minum Obat TB Teratur Tanpa Putus" required>
                            @error('title_material')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Tipe Konten <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="type_video" name="material_type" value="video" class="custom-control-input"
                                       {{ old('material_type', $material->material_type ?? 'video') == 'video' ? 'checked' : '' }} onchange="toggleTypeFields()">
                                <label class="custom-control-label font-weight-normal text-danger" for="type_video">
                                    <i class="fab fa-youtube mr-1"></i> Video Edukasi (YouTube)
                                </label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="type_image" name="material_type" value="image" class="custom-control-input"
                                       {{ old('material_type', $material->material_type) == 'image' ? 'checked' : '' }} onchange="toggleTypeFields()">
                                <label class="custom-control-label font-weight-normal text-primary" for="type_image">
                                    <i class="fas fa-image mr-1"></i> Poster / Infografis / Gambar
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Input Video URL (shown if video) -->
                    <div class="form-group row" id="video_url_container">
                        <label class="col-sm-3 col-form-label font-weight-bold">URL Video YouTube <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fab fa-youtube text-danger"></i></span>
                                </div>
                                <input type="url" name="video_url" id="video_url" class="form-control @error('video_url') is-invalid @enderror" 
                                       value="{{ old('video_url', $material->video_url) }}" 
                                       placeholder="https://www.youtube.com/watch?v=..." oninput="updateVideoPreview(this.value)">
                            </div>
                            <small class="form-text text-muted">Mendukung format link YouTube biasa maupun format share pendek (https://youtu.be/...)</small>
                            <div id="video_preview_box" class="mt-2" style="display: none;">
                                <div class="embed-responsive embed-responsive-16by9 border rounded" style="max-height: 250px;">
                                    <iframe id="video_preview_iframe" class="embed-responsive-item" src="" allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Input Image (shown if image) -->
                    <div class="form-group row" id="image_container" style="display: none;">
                        <label class="col-sm-3 col-form-label font-weight-bold">File Gambar / Poster <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            @if($material->image_url)
                                <div class="mb-2">
                                    <img src="{{ $material->image_url }}" alt="Current Image" class="img-thumbnail" style="max-height: 150px;">
                                    <div class="small text-muted">Gambar saat ini. Pilih file baru jika ingin mengganti.</div>
                                </div>
                            @endif
                            <div class="custom-file">
                                <input type="file" name="image" class="custom-file-input @error('image') is-invalid @enderror" id="customFile" accept="image/*" onchange="previewImage(this)">
                                <label class="custom-file-label" for="customFile" id="customFileLabel">Pilih file gambar poster (JPG, PNG, WebP maks 5MB)...</label>
                            </div>
                            <div class="mt-2" id="image_preview_box" style="display: none;">
                                <img id="image_preview" src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Deskripsi / Konten Edukasi</label>
                        <div class="col-sm-9">
                            <textarea name="description" rows="5" class="form-control @error('description') is-invalid @enderror" 
                                      placeholder="Tuliskan ringkasan, petunjuk, atau poin-poin penting materi edukasi ini...">{{ old('description', $material->description) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Status Publikasi <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select name="is_publish" class="form-control select2" style="width: 100%;">
                                <option value="1" {{ old('is_publish', $material->is_publish ?? 1) == 1 ? 'selected' : '' }}>Publikasikan (Dapat dilihat pengguna di aplikasi mobile)</option>
                                <option value="0" {{ old('is_publish', $material->is_publish ?? 1) == 0 ? 'selected' : '' }}>Simpan sebagai Draft (Sembunyikan dari publik)</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="card-footer bg-light d-flex justify-content-between">
                    <a href="{{ route('admin.education.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left mr-1"></i> Batal / Kembali
                    </a>
                    <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Terbitkan Materi' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleTypeFields() {
    let isVideo = document.getElementById('type_video').checked;
    let videoContainer = document.getElementById('video_url_container');
    let imageContainer = document.getElementById('image_container');

    if (isVideo) {
        videoContainer.style.display = 'flex';
        imageContainer.style.display = 'none';
    } else {
        videoContainer.style.display = 'none';
        imageContainer.style.display = 'flex';
    }
}

function updateVideoPreview(url) {
    let previewBox = document.getElementById('video_preview_box');
    let iframe = document.getElementById('video_preview_iframe');
    
    if (!url) {
        previewBox.style.display = 'none';
        return;
    }

    let match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/);
    if (match && match[1]) {
        iframe.src = 'https://www.youtube.com/embed/' + match[1];
        previewBox.style.display = 'block';
    } else {
        previewBox.style.display = 'none';
    }
}

function previewImage(input) {
    let previewBox = document.getElementById('image_preview_box');
    let previewImg = document.getElementById('image_preview');
    let label = document.getElementById('customFileLabel');

    if (input.files && input.files[0]) {
        let file = input.files[0];
        label.innerText = file.name;

        let reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewBox.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}

$(document).ready(function() {
    toggleTypeFields();
    let initialUrl = document.getElementById('video_url').value;
    if (initialUrl) {
        updateVideoPreview(initialUrl);
    }
});
</script>
@endpush
