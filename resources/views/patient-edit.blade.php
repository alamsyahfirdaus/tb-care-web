@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ 'Edit Detail ' . $title }}</h3>
                <div class="card-tools">
                    <a href="{{ route('user.show', ['id' => base64_encode($data->user_id)]) }}" class="btn btn-primary btn-sm"
                        title="Tutup Data {{ $title }}">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('patient.update', isset($data) ? base64_encode($data->id) : '') }}" method="PUT"
                    enctype="multipart/form-data" id="form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group row">
                        <label for="patient_id" class="col-sm-3 col-form-label">Nama</label>
                        <div class="col-sm-9">
                            <select name="patient_id" id="patient_id" class="form-control select2" style="width: 100%;">
                                @foreach ($patients as $key => $value)
                                    <option value="{{ base64_encode($key) }}" {{ $data->id == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-patient_id" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="nik" class="col-sm-3 col-form-label">NIK</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="nik" id="nik"
                                placeholder="Masukan NIK" autocomplete="off" value="{{ isset($data) ? $data->nik : '' }}">
                            <span id="error-nik" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="address" class="col-sm-3 col-form-label">Alamat</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" name="address" id="address" placeholder="Masukan Alamat">{{ isset($data->address) ? $data->address : '' }}</textarea>
                            <span id="error-address" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="subdistrict_id" class="col-sm-3 col-form-label">Kecamatan</label>
                        <div class="col-sm-9">
                            <select name="subdistrict_id" id="subdistrict_id" class="form-control select2"
                                style="width: 100%;">
                                <option value="">Pilih Kecamatan</option>
                                @foreach ($subdistricts as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ isset($data) && $data->subdistrict_id == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-subdistrict_id" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="offset-sm-3 col-sm-9">
                            <small style="font-style: italic;">*) NIK, alamat, dan kecamatan harus
                                sesuai dengan KTP pasien.</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="occupation" class="col-sm-3 col-form-label">Pekerjaan</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="occupation" id="occupation"
                                placeholder="Masukan Pekerjaan" autocomplete="off"
                                value="{{ isset($data) ? $data->occupation : '' }}">
                            <span id="error-occupation" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="height" class="col-sm-3 col-form-label">Tinggi Badan</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="height" id="height"
                                placeholder="Masukan Tinggi Badan" autocomplete="off"
                                value="{{ isset($data) ? $data->height : '' }}">
                            <span id="error-height" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="weight" class="col-sm-3 col-form-label">Berat Badan</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="weight" id="weight"
                                placeholder="Masukan Berat Badan" autocomplete="off"
                                value="{{ isset($data) ? $data->weight : '' }}">
                            <span id="error-weight" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="blood_type" class="col-sm-3 col-form-label">Golongan Darah</label>
                        <div class="col-sm-9">
                            <select name="blood_type" id="blood_type" class="form-control select2" style="width: 100%;">
                                <option value="">Pilih Golongan Darah</option>
                                @php
                                    $blood_types = ['A', 'B', 'AB', 'O'];
                                @endphp
                                @foreach ($blood_types as $item)
                                    <option value="{{ $item }}"
                                        {{ isset($data) && $data->blood_type == $item ? 'selected' : '' }}>
                                        {{ $item }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-blood_type" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="diagnosis_date" class="col-sm-3 col-form-label">Tanggal
                            Diagnosis</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control datetimepicker-input"
                                data-target="#reservationdate" data-toggle="datetimepicker" name="diagnosis_date"
                                id="diagnosis_date" placeholder="Tanggal Diagnosis"
                                value="{{ isset($data) && $data->diagnosis_date ? \Carbon\Carbon::parse($data->diagnosis_date)->format('d/m/Y') : '' }}"
                                autocomplete="off">
                            <span id="error-diagnosis_date" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="puskesmas_id" class="col-sm-3 col-form-label">Tempat Berobat
                            (Puskesmas)</label>
                        <div class="col-sm-9">
                            <select name="puskesmas_id" id="puskesmas_id" class="form-control select2"
                                style="width: 100%;">
                                <option value="">Pilih Puskesmas</option>
                                @foreach ($puskesmas as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ isset($data) && $data->puskesmas_id == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-puskesmas_id" class="error invalid-feedback"></span>
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
            $('#patient_id').change(function() {
                var patientId = $(this).val();
                if (patientId) {
                    var url = '{{ route('patient.edit', ['id' => ':id']) }}';
                    window.location.href = url.replace(':id', patientId);
                }
            });
        });
    </script>
@endsection
