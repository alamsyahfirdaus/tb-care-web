@extends('admin.layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-teal shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas {{ $isEdit ? 'fa-pencil-alt' : 'fa-plus-circle' }} mr-1 text-teal"></i>
                    {{ $isEdit ? 'Edit Fasilitas Kesehatan' : 'Tambah Fasilitas Kesehatan Baru' }}
                </h3>
            </div>
            <form action="{{ $isEdit ? route('admin.puskesmas.update', $puskesmas->encrypted_id) : route('admin.puskesmas.store') }}" method="POST">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Terjadi Kesalahan Input</h5>
                        <ul class="mb-0">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Kode Faskes</label>
                        <div class="col-sm-9">
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" 
                                   value="{{ old('code', $puskesmas->code) }}" 
                                   placeholder="Contoh: PKM-CKP-001 (Kosongkan untuk otomatis generate)">
                            <small class="form-text text-muted">Kode unik faskes resmi Kementerian Kesehatan atau kode internal sistem.</small>
                            @error('code')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Nama Fasilitas Kesehatan <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $puskesmas->name) }}" 
                                   placeholder="Contoh: UPTD Puskesmas Ciawi" required>
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Kecamatan Binaan <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select name="subdistrict_id" class="form-control select2 @error('subdistrict_id') is-invalid @enderror" required style="width: 100%;">
                                <option value="">-- Pilih Kecamatan --</option>
                                @foreach($subdistricts as $sub)
                                    <option value="{{ $sub->id }}" {{ old('subdistrict_id', $puskesmas->subdistrict_id) == $sub->id ? 'selected' : '' }}>
                                        {{ $sub->name }} ({{ $sub->district->name ?? 'Tasikmalaya' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('subdistrict_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Alamat Lengkap</label>
                        <div class="col-sm-9">
                            <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror" 
                                      placeholder="Masukkan jalan, nomor gedung, RT/RW, dan kode pos...">{{ old('address', $puskesmas->address) }}</textarea>
                            @error('address')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="card-footer bg-light d-flex justify-content-between">
                    <a href="{{ route('admin.puskesmas.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left mr-1"></i> Batal / Kembali
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Daftarkan Puskesmas' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
