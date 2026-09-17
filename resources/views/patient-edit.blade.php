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
                        <label for="nik" class="col-sm-3 col-form-label">NIK<small
                            class="text-danger">*</small></label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="nik" id="nik"
                                placeholder="Masukan NIK" autocomplete="off" value="{{ isset($data) ? $data->nik : '' }}">
                            <span id="error-nik" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="address" class="col-sm-3 col-form-label">Alamat<small
                            class="text-danger">*</small></label>
                        <div class="col-sm-9">
                            <textarea class="form-control" name="address" id="address" placeholder="Masukan Alamat">{{ isset($data->address) ? $data->address : '' }}</textarea>
                            <span id="error-address" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="subdistrict_id" class="col-sm-3 col-form-label">Kecamatan<small
                            class="text-danger">*</small></label>
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
                        <label for="village_id" class="col-sm-3 col-form-label">Desa / Kelurahan</label>
                        <div class="col-sm-9">
                            <select name="village_id" id="village_id" class="form-control select2"
                                style="width: 100%;">
                                <option value="">Pilih Desa / Kelurahan</option>
                                @foreach ($villages as $village)
                                    <option value="{{ $village->id }}"
                                        {{ isset($data) && $data->village_id == $village->id ? 'selected' : '' }}>
                                        {{ $village->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-village_id" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="rw" class="col-sm-3 col-form-label">RW</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control" name="rw" id="rw"
                                placeholder="Contoh: 05" maxlength="5" autocomplete="off"
                                value="{{ isset($data) ? $data->rw : '' }}">
                            <span id="error-rw" class="error invalid-feedback"></span>
                        </div>
                        <label for="rt" class="col-sm-2 col-form-label text-sm-right">RT</label>
                        <div class="col-sm-4">
                            <input type="text" class="form-control" name="rt" id="rt"
                                placeholder="Contoh: 01" maxlength="5" autocomplete="off"
                                value="{{ isset($data) ? $data->rt : '' }}">
                            <span id="error-rt" class="error invalid-feedback"></span>
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
                    {{-- <div class="form-group row">
                        <label for="diagnosis_date" class="col-sm-3 col-form-label">Tanggal
                            Diagnosis<small
                            class="text-danger">*</small></label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control datetimepicker-input"
                                data-target="#reservationdate" data-toggle="datetimepicker" name="diagnosis_date"
                                id="diagnosis_date" placeholder="Masukan Tanggal Diagnosis"
                                value="{{ isset($data) && $data->diagnosis_date ? \Carbon\Carbon::parse($data->diagnosis_date)->format('d/m/Y') : '' }}"
                                autocomplete="off">
                            <span id="error-diagnosis_date" class="error invalid-feedback"></span>
                        </div>
                    </div> --}}
                    <div class="form-group row">
                        <label for="treatment_start_date" class="col-sm-3 col-form-label">Tanggal Mulai Pengobatan</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" name="treatment_start_date"
                                id="treatment_start_date" max="{{ date('Y-m-d') }}"
                                value="{{ isset($data) && $data->treatment_start_date ? \Carbon\Carbon::parse($data->treatment_start_date)->format('Y-m-d') : '' }}">
                            <span id="error-treatment_start_date" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="puskesmas_id" class="col-sm-3 col-form-label">Puskesmas<small
                            class="text-danger">*</small></label>
                        <div class="col-sm-9">
                            <select name="puskesmas_id" id="puskesmas_id" class="form-control select2"
                                style="width: 100%;">
                                <option value="">Pilih Puskesmas</option>
                                @foreach ($puskesmas as $item)
                                    <option value="{{ $item['id'] }}"
                                        {{ isset($data) && $data->puskesmas_id == $item['id'] ? 'selected' : '' }}>
                                        {{ $item['puskesmas'] }}
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

            $('#subdistrict_id').change(function() {
                var subdistrictId = $(this).val();
                var $villageSelect = $('#village_id');
                $villageSelect.empty().append('<option value="">Memuat...</option>');
                if (subdistrictId) {
                    $.ajax({
                        url: '{{ url("kader-area/villages") }}/' + subdistrictId,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            $villageSelect.empty();
                            $villageSelect.append('<option value="">Pilih Desa / Kelurahan</option>');
                            $.each(data, function(index, item) {
                                $villageSelect.append('<option value="' + item.id + '">' + item.name + '</option>');
                            });
                            $villageSelect.trigger('change');
                        },
                        error: function() {
                            $villageSelect.empty().append('<option value="">Gagal memuat desa</option>');
                            $villageSelect.trigger('change');
                        }
                    });
                } else {
                    $villageSelect.empty().append('<option value="">Pilih Desa / Kelurahan</option>');
                    $villageSelect.trigger('change');
                }
            });
        });
    </script>
@endsection
