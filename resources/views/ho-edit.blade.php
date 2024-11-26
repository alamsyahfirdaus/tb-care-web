@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ 'Edit Detail ' . $title }}</h3>
                <div class="card-tools">
                    <a href="{{ route('user.show', ['id' => base64_encode($data->user_id)]) }}" class="btn btn-primary btn-sm"
                        title="Sebelumnya">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('ho.update', isset($data) ? base64_encode($data->id) : '') }}" method="PUT"
                    enctype="multipart/form-data" id="form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group row">
                        <label for="health_office_id" class="col-sm-3 col-form-label">Nama</label>
                        <div class="col-sm-9">
                            <select name="health_office_id" id="health_office_id" class="form-control select2"
                                style="width: 100%;">
                                @foreach ($health_offices as $key => $value)
                                    <option value="{{ base64_encode($key) }}" {{ $data->id == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-health_office_id" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="office_type_id" class="col-sm-3 col-form-label">Dinas Kesehatan<small
                            class="text-danger">*</small></label>
                        <div class="col-sm-9">
                            <select name="office_type_id" id="office_type_id" class="form-control select2"
                                style="width: 100%;">
                                <option value="">Pilih Dinas Kesehatan</option>
                                @foreach ($office_types as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ isset($data) && $data->office_type_id == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-office_type_id" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="office_address" class="col-sm-3 col-form-label">Alamat Kantor<small
                            class="text-danger">*</small></label>
                        <div class="col-sm-9">
                            <textarea class="form-control" name="office_address" id="office_address" placeholder="Masukan Alamat Kantor">{{ isset($data->office_address) ? $data->office_address : '' }}</textarea>
                            <span id="error-office_address" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="district_id" class="col-sm-3 col-form-label">Kabupaten/Kota<small
                            class="text-danger">*</small></label>
                        <div class="col-sm-9">
                            <select name="district_id" id="district_id" class="form-control select2" style="width: 100%;">
                                <option value="">Pilih Kabupaten/Kota</option>
                                @foreach ($districts as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ isset($data) && $data->district_id == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-district_id" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="office_phone" class="col-sm-3 col-form-label">Telepon Kantor</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="office_phone" id="office_phone"
                                placeholder="Masukan Telepon Kantor" autocomplete="off"
                                value="{{ isset($data) ? $data->office_phone : '' }}">
                            <span id="error-office_phone" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="office_email" class="col-sm-3 col-form-label">Email Kantor</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="office_email" id="office_email"
                                placeholder="Masukan Email Kantor" autocomplete="off"
                                value="{{ isset($data) ? $data->office_email : '' }}">
                            <span id="error-office_email" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="offset-sm-3 col-sm-9">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>
                                Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $(function() {
            $('#health_office_id').change(function() {
                var HoId = $(this).val();
                if (HoId) {
                    var url = '{{ route('ho.edit', ['id' => ':id']) }}';
                    window.location.href = url.replace(':id', HoId);
                }
            });
        });
    </script>
@endsection
