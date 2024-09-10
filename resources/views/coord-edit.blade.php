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
                <form action="{{ route('coord.update', isset($data) ? base64_encode($data->id) : '') }}" method="PUT"
                    enctype="multipart/form-data" id="form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group row">
                        <label for="coord_id" class="col-sm-3 col-form-label">Nama</label>
                        <div class="col-sm-9">
                            <select name="coord_id" id="coord_id" class="form-control select2" style="width: 100%;">
                                @foreach ($coords as $key => $value)
                                    <option value="{{ base64_encode($key) }}" {{ $data->id == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-coord_id" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="coord_type_id" class="col-sm-3 col-form-label">PJTB/Kader</label>
                        <div class="col-sm-9">
                            <select name="coord_type_id" id="coord_type_id" class="form-control select2"
                                style="width: 100%;">
                                <option value="">Pilih PJTB/Kader</option>
                                @foreach ($coord_types as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ isset($data) && $data->coord_type_id == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            <span id="error-coord_type_id" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="puskesmas_id" class="col-sm-3 col-form-label">Puskesmas</label>
                        <div class="col-sm-9">
                            <select name="puskesmas_id" id="puskesmas_id" class="form-control select2" style="width: 100%;">
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
            $('#coord_id').change(function() {
                var coordId = $(this).val();
                if (coordId) {
                    var url = '{{ route('coord.edit', ['id' => ':id']) }}';
                    window.location.href = url.replace(':id', coordId);
                }
            });
        });
    </script>
@endsection
