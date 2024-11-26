@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ empty($data->id) ? 'Daftar ' . $title : 'Edit ' . $title }}</h3>
                <div class="card-tools">
                    @if (empty($data->id))
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
                    @if (empty($data->id))
                        <div class="tab-pane active" id="tab1">
                            <div class="table-responsive">
                                <table id="datatable" class="table table-bordered table-hover" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%; text-align: center;">No</th>
                                            <th>Jenis<span style="color: #fff; font-size: 10px;">_</span>Pengobatan</th>
                                            <th>Durasi</th>
                                            <th>Deskripsi</th>
                                            <th style="width: 5%; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($trtypes as $key => $item)
                                            <tr>
                                                <td style="text-align: center;">{{ $key + 1 }}</td>
                                                <td>{{ $item['treatment_type'] }}</td>
                                                <td>{{ $item['duration'] }}</td>
                                                <td>{{ $item['description   '] ?? '-' }}</td>
                                                <td style="text-align: center;">
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-primary btn-sm dropdown-toggle"
                                                            data-toggle="dropdown"><i class="fas fa-cogs"></i></button>
                                                        <div class="dropdown-menu" role="menu">
                                                            <a class="dropdown-item"
                                                                href="{{ route('trtype.edit', ['id' => base64_encode($item['id'])]) }}">Edit</a>
                                                            <div class="dropdown-divider"></div>
                                                            {!! Form::open([
                                                                'route' => ['trtype.delete', base64_encode($item['id'])],
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
                    <div class="tab-pane {{ isset($data->id) ? 'active' : '' }}" id="tab2">
                        <form action="{{ route('trtype.save', isset($data) ? base64_encode($data->id) : '') }}"
                            method="POST" enctype="multipart/form-data" id="form-data">
                            @csrf
                            @if (isset($data))
                                @method('PUT')
                            @endif
                            <div class="form-group row">
                                <label for="treatment_type" class="col-sm-3 col-form-label">Jenis Pengobatan<small
                                    class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="treatment_type" id="treatment_type"
                                        placeholder="Masukan Jenis Pengobatan" autocomplete="off"
                                        value="{{ isset($data) ? $data->treatment_type : '' }}">
                                    <span id="error-treatment_type" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="treatment_duration" class="col-sm-3 col-form-label">Durasi Pengobatan<small
                                    class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="treatment_duration"
                                        id="treatment_duration" placeholder="Masukan Durasi Pengobatan" autocomplete="off"
                                        value="{{ isset($data) ? $data->treatment_duration : '' }}">
                                    <span id="error-treatment_duration" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="duration_unit" class="col-sm-3 col-form-label">Satuan Durasi<small
                                    class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <select name="duration_unit" id="duration_unit" class="form-control select2"
                                        style="width: 100%;">
                                        <option value="">Pilih Satuan Durasi</option>
                                        @foreach (['minggu', 'bulan','tahun'] as $duration_unit)
                                            <option value="{{ $duration_unit }}"
                                                {{ isset($data) && $data->duration_unit == $duration_unit ? 'selected' : '' }}>{{ Str::ucfirst($duration_unit) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span id="error-duration_unit" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="description" class="col-sm-3 col-form-label">Deskripsi</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control" name="description" id="description" placeholder="Masukan Deskripsi">{{ isset($data) ? $data->description : '' }}</textarea>
                                    <span id="error-description" class="error invalid-feedback"></span>
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
        </div>
    </div>
@endsection
