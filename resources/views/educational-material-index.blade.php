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
                                            <th>Judul</th>
                                            <th>Deskripsi</th>
                                            <th>Publish</th>
                                            <th style="width: 5%; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($materials as $key => $item)
                                            <tr>
                                                <td style="text-align: center;">{{ $key + 1 }}</td>
                                                <td>
                                                    <a href="{{ route('material.show', ['id' => base64_encode($item['id'])]) }}"
                                                        title="Lihat Materi">{{ $item['title_material'] }}</a>
                                                </td>
                                                <td>{{ $item['description'] ?? '-' }}</td>
                                                <td>{{ $item['is_publish'] === 1 ? 'Ya' : ($item['is_publish'] === 0 ? 'Tidak' : '-') }}
                                                </td>
                                                <td style="text-align: center;">
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-primary btn-sm dropdown-toggle"
                                                            data-toggle="dropdown"><i class="fas fa-cogs"></i></button>
                                                        <div class="dropdown-menu" role="menu">
                                                            <a class="dropdown-item"
                                                                href="{{ route('material.edit', ['id' => base64_encode($item['id'])]) }}">Edit</a>
                                                            <div class="dropdown-divider"></div>
                                                            {!! Form::open([
                                                                'route' => ['material.delete', base64_encode($item['id'])],
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
                        <form action="{{ route('material.save', isset($data) ? base64_encode($data['id']) : '') }}"
                            method="POST" enctype="multipart/form-data" id="form-data">
                            @csrf
                            @if (isset($data))
                                @method('PUT')
                            @endif
                            <div class="form-group row">
                                <label for="title_material" class="col-sm-3 col-form-label">Judul Materi<small
                                        class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="title_material" id="title_material"
                                        placeholder="Masukan Judul Materi" autocomplete="off"
                                        value="{{ @$data['title_material'] }}">
                                    <span id="error-title_material" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="material_type" class="col-sm-3 col-form-label">Jenis Materi<small
                                        class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <select name="material_type" id="material_type" class="form-control select2"
                                        style="width: 100%;">
                                        <option value="">Pilih Jenis Materi</option>
                                        @php
                                            $mediaType = [
                                                'file' => 'Unggah File',
                                                'url' => 'Tautan Video',
                                            ];
                                        @endphp
                                        @foreach ($mediaType as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ isset($data) && $data['material_type'] == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span id="error-material_type" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="material_file" class="col-sm-3 col-form-label">Unggah File<small id="required-material_file"
                                    class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="material_file" placeholder="Pilih File Materi"
                                        readonly value="{{ @$data['material_file'] }}" style="background-color: #ffffff;"
                                        {{ empty($data) ? 'disabled' : '' }} autocomplete="off">
                                    <span id="error-material_file" class="error invalid-feedback"></span>
                                </div>
                                <input type="file" name="material_file" style="display: none">
                            </div>
                            <div class="form-group row">
                                <label for="material_url" class="col-sm-3 col-form-label">Tautan Video<small id="required-material_url"
                                    class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="material_url" id="material_url"
                                        placeholder="Masukan Tautan YouTube" autocomplete="off"
                                        value="{{ @$data['material_url'] }}" {{ empty($data) ? 'disabled' : '' }}>
                                    <span id="error-material_url" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="thumbnail" class="col-sm-3 col-form-label">Gambar Sampul<small id="required-thumbnail"
                                        class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="thumbnail"
                                        placeholder="Pilih File Gambar" value="{{ @$data['thumbnail'] }}"
                                        style="background-color: #ffffff;" readonly autocomplete="off" {{ empty($data) ? 'disabled' : '' }}>
                                    <span id="error-thumbnail" class="error invalid-feedback"></span>
                                </div>
                                <input type="file" name="thumbnail" style="display: none">
                            </div>
                            <div class="form-group row">
                                <label for="description" class="col-sm-3 col-form-label">Deskripsi<small
                                        class="text-danger">*</small></label>
                                <div class="col-sm-9">
                                    <textarea class="form-control" name="description" id="description" placeholder="Masukan Deskripsi">{{ @$data['description'] }}</textarea>
                                    <span id="error-description" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            @if (isset($data))
                                <div class="form-group row">
                                    <label for="is_publish" class="col-sm-3 col-form-label">Publish<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <select name="is_publish" id="is_publish" class="form-control select2"
                                            style="width: 100%;">
                                            <option value="">Pilih Publish</option>
                                            @php
                                                $isPublish = [
                                                    '1' => 'Ya',
                                                    '0' => 'Tidak',
                                                ];
                                            @endphp
                                            @foreach ($isPublish as $key => $value)
                                                <option value="{{ $key }}"
                                                    {{ isset($data) && $data['is_publish'] == $key ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span id="error-is_publish" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                            @endif
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
    <script>
        $('#material_type').change(function() {
            var selectedType = $(this).val();

            $('#material_file, #material_url, #required-thumbnail').prop('disabled', true).val('');
            $('#material_file, #material_url, #required-thumbnail').removeClass('is-invalid');
            $('#error-material_file, #error-material_url').text('').hide();
            $('#required-material_file, #required-material_url, #required-thumbnail').hide();

            if (selectedType === 'file') {
                $('#material_file').prop('disabled', false);
                $('#required-material_file').show();
                setTimeout(function() {
                    $('#material_file').focus();
                }, 100);
            } else if (selectedType === 'url') {
                $('#material_url, #thumbnail').prop('disabled', false);
                $('#required-material_url, #required-thumbnail').show();
                $('[name="material_file"]').val(null);
                setTimeout(function() {
                    $('#material_url').focus();
                }, 100);
            }
        });

        $('#material_file').click(function() {
            $('[name="material_file"]').click();
        });

        $('[name="material_file"]').change(function() {
            $('#material_file').val($(this).val().split('\\').pop());
        });

        $('#thumbnail').click(function() {
            $('[name="thumbnail"]').click();
        });

        $('[name="thumbnail"]').change(function() {
            $('#thumbnail').val($(this).val().split('\\').pop());
        });
    </script>
@endsection
