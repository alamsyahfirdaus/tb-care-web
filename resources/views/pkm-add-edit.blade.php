@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div id="alert-message"></div>
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ isset($data) ? 'Edit' : 'Tambah' }} {{ $title }}</h3>
                    </div>
                    <form action="{{ route('pkm.save', isset($data) ? base64_encode($data->id) : '') }}" method="POST"
                        enctype="multipart/form-data" id="form-data">
                        @csrf
                        @if (isset($data))
                            @method('PUT')
                        @endif
                        <div class="card-body">
                            <div class="form-group">
                                <label for="kode">Kode</label>
                                <input type="text" class="form-control" name="kode" id="kode"
                                    placeholder="Masukan Kode" autocomplete="off"
                                    value="{{ isset($data->kode) ? $data->kode : $nextKode }}">
                                <span id="error-kode" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="nama">Nama<small style="color: #dc3545;">*</small></label>
                                <input type="text" class="form-control" name="nama" id="nama"
                                    placeholder="Masukan Nama" autocomplete="off"
                                    value="{{ isset($data) ? $data->nama : '' }}">
                                <span id="error-nama" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="lokasi">Lokasi<small style="color: #dc3545;">*</small></label>
                                <input type="text" class="form-control" name="lokasi" id="lokasi" placeholder="Masukan Lokasi" value="{{ isset($data) ? $data->lokasi : '' }}" autocomplete="off">
                                <span id="error-lokasi" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="subdistrict_id">Kecamatan<small style="color: #dc3545;">*</small></label>
                                <select name="subdistrict_id" id="subdistrict_id" class="form-control select2"
                                    style="width: 100%;">
                                    <option value="">Pilih Kecamatan</option>
                                    @foreach ($subdistricts as $subdistrict)
                                        <option value="{{ $subdistrict->id }}"
                                            {{ isset($data) && $data->subdistrict_id == $subdistrict->id ? 'selected' : '' }}>
                                            {{ $subdistrict->subdistrict_name }}</option>
                                    @endforeach
                                </select>
                                <span id="error-subdistrict_id" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="district_name">Kota/Kab.</label>
                                <input type="text" name="district_name" id="district_name" class="form-control"
                                    value="{{ isset($data) ? $data->subdistrict->district->district_name : '' }}" placeholder="Kota/Kab." disabled>
                            </div>
                            <div class="form-group">
                                <label for="province_name">Provinsi</label>
                                <input type="text" name="province_name" id="province_name" class="form-control"
                                    value="{{ isset($data) ? $data->subdistrict->district->province->province_name : '' }}" placeholder="Provinsi" disabled>
                            </div>
                            <small style="color: #dc3545;">{{ '*) Bidang Wajib Diisi.' }}</small>
                        </div>
                        <div class="card-footer">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times-circle"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm float-right"><i class="fas fa-save"></i>
                                Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
