@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ empty($data['id']) ? 'Daftar ' . $title : 'Edit ' . $title }}</h3>
                <div class="card-tools">
                    @if (empty($data['id']))
                        <a href="javascript:void(0)" id="add-data-toggle" class="btn btn-primary btn-sm"
                            title="Tambah {{ $title }}">
                            <i class="fas fa-plus"></i>
                        </a>
                    @else
                        <a href="{{ url()->previous() }}" class="btn btn-primary btn-sm" title="Sebelumnya">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="tab-content p-0">
                    @if (empty($data['id']))
                        <div class="tab-pane active" id="tab1">
                            <div class="table-responsive">
                                <table id="datatable" class="table table-bordered table-hover" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%; text-align: center;">No</th>
                                            <th>Nama<span style="color: #fff; font-size: 10px;">_</span>Pasien</th>
                                            <th>Tanggal<span style="color: #fff; font-size: 10px;">_</span>Diagnosis</th>
                                            <th>Jenis<span style="color: #fff; font-size: 10px;">_</span>Pengobatan</th>
                                            <th>Keterangan</th>
                                            <th style="width: 5%; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($treatments as $key => $item)
                                            <tr>
                                                <td style="text-align: center;">{{ $key + 1 }}</td>
                                                <td>{{ $item['full_name'] }}</td>
                                                <td>{{ $item['diagnosis_date'] ? \App\Helpers\DateHelper::convertDate($item['diagnosis_date']) : '-' }}
                                                </td>
                                                <td>{{ $item['treatment_type'] }}</td>
                                                <td>{{ $item['treatment_status'] ?? '-' }}</td>
                                                <td style="text-align: center;">
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-primary btn-sm dropdown-toggle"
                                                            data-toggle="dropdown"><i class="fas fa-cogs"></i></button>
                                                        <div class="dropdown-menu" role="menu">
                                                            <a class="dropdown-item"
                                                                href="{{ route('treatment.show', ['id' => base64_encode($item['id'])]) }}">Detail</a>
                                                            <div class="dropdown-divider"></div>
                                                            {!! Form::open([
                                                                'route' => ['treatment.delete', base64_encode($item['id'])],
                                                                'method' => 'DELETE',
                                                                'id' => 'remove-' . md5($item['id']),
                                                            ]) !!}
                                                            <a class="dropdown-item" href="javascript:void(0)"
                                                                onclick="deleteData('{{ md5($item['id']) }}')">Hapus</a>
                                                            {!! Form::close() !!}
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    <div class="tab-pane {{ isset($data['id']) ? 'active' : '' }}" id="tab2">
                        <form action="{{ route('treatment.save', isset($data) ? base64_encode($data['id']) : '') }}"
                            method="POST" enctype="multipart/form-data" id="form-data">
                            @csrf
                            @if (isset($data))
                                @method('PUT')
                            @endif
                            <div class="form-group row">
                                <label for="patient_id" class="col-sm-3 col-form-label">Nama Pasien<small
                                        class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    @if (isset($data['id']) && isset($data['patient_id']))
                                        @php
                                            $patientName = $patients[$data['patient_id']] ?? null;
                                        @endphp
                                        <input type="hidden" name="patient_id" value="{{ $data['patient_id'] }}">
                                        <input type="text" name="full_name" id="patient_id" class="form-control"
                                            value="{{ $patientName }}" disabled>
                                    @else
                                        <select name="patient_id" id="patient_id" class="form-control select2"
                                            style="width: 100%;">
                                            <option value="">Masukan Nama Pasien</option>
                                            @foreach ($patients as $key => $value)
                                                <option value="{{ $key }}"
                                                    {{ isset($data) && $data['patient_id'] == $key ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <span id="error-patient_id" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="treatment_type_id" class="col-sm-3 col-form-label">Jenis Pengobatan<small
                                        class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <select name="treatment_type_id" id="treatment_type_id" class="form-control select2"
                                        style="width: 100%;">
                                        <option value="">Pilih Jenis Pengobatan</option>
                                        @foreach ($trtypes as $key => $item)
                                            <option value="{{ $item['id'] }}"
                                                {{ isset($data) && $data['treatment_type_id'] == $item['id'] ? 'selected' : '' }}>
                                                @php
                                                    $treatment_type = $item['treatment_type'] . ' ' . $item['duration'];
                                                @endphp
                                                {{ $treatment_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span id="error-treatment_type_id" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="diagnosis_date" class="col-sm-3 col-form-label">Tanggal
                                    Diagnosis<small class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control datetimepicker-input"
                                        data-target="#reservationdate" data-toggle="datetimepicker" name="diagnosis_date"
                                        id="diagnosis_date" placeholder="Masukan Tanggal Diagnosis"
                                        value="{{ isset($data) && $data['diagnosis_date'] ? \Carbon\Carbon::parse($data['diagnosis_date'])->format('d/m/Y') : '' }}"
                                        autocomplete="off">
                                    <span id="error-diagnosis_date" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="start_date" class="col-sm-3 col-form-label">Tgl. Mulai Pengobatan<small
                                        class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control datetimepicker-input"
                                        data-target="#reservationdate" data-toggle="datetimepicker" name="start_date"
                                        id="start_date" placeholder="Masukan Tgl. Mulai Pengobatan"
                                        value="{{ isset($data) && $data['start_date'] ? \Carbon\Carbon::parse($data['start_date'])->format('d/m/Y') : '' }}"
                                        autocomplete="off">
                                    <span id="error-start_date" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="medication_time" class="col-sm-3 col-form-label">Jam Minum Obat<small
                                        class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control timepicker-input" data-target="#timepicker"
                                        data-toggle="datetimepicker" name="medication_time" id="medication_time"
                                        placeholder="Masukan Jam Minum Obat"
                                        value="{{ isset($data) && $data['medication_time'] ? \Carbon\Carbon::parse($data['medication_time'])->format('H:i') : '' }}"
                                        autocomplete="off">
                                    <span id="error-medication_time" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="offset-sm-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary btn-sm"><i
                                            class="fas fa-save mr-1"></i>
                                        Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
