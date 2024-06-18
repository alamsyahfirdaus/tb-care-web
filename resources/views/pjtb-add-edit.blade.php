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
                    <form action="{{ route('pjtb.save', isset($data) ? base64_encode($data->id) : '') }}" method="POST"
                        enctype="multipart/form-data" id="form-data">
                        @csrf
                        @if (isset($data))
                            @method('PUT')
                        @endif
                        <div class="card-body">
                            <div class="form-group">
                                <label for="user_id">PJ TB<small style="color: #dc3545;">*</small></label>
                                <select name="user_id" id="user_id" class="form-control select2" style="width: 100%;">
                                    <option value="">Pilih PJ TB</option>
                                    @foreach ($users as $field)
                                        <option value="{{ $field->id }}"
                                            {{ isset($data) && $data->user_id == $field->id ? 'selected' : '' }}>
                                            {{ $field->name .' - '. $field->email }}</option>
                                    @endforeach
                                </select>
                                <span id="error-user_id" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="puskesmas_id">Puskesmas<small style="color: #dc3545;">*</small></label>
                                <select name="puskesmas_id" id="puskesmas_id" class="form-control select2"
                                    style="width: 100%;">
                                    <option value="">Pilih Puskesmas</option>
                                    @foreach ($pkm as $field)
                                        <option value="{{ $field->id }}"
                                            {{ isset($data) && $data->puskesmas_id == $field->id ? 'selected' : '' }}>
                                            {{ $field->kode .' - '. $field->nama }}</option>
                                    @endforeach
                                </select>
                                <span id="error-puskesmas_id" class="error invalid-feedback"></span>
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
