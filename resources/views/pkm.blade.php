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
                        <a href="{{ url()->previous() }}" class="btn btn-primary btn-sm" title="Tutup Formulir">
                            <i class="fas fa-times"></i>
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
                                            <th>Kode</th>
                                            <th>Puskesmas</th>
                                            <th>Alamat</th>
                                            <th style="width: 5%; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($puskesmas as $key => $item)
                                            <tr>
                                                <td style="text-align: center;">{{ $key + 1 }}</td>
                                                <td>{{ $item->code ?? '-' }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->location }}</td>
                                                <td style="text-align: center;">
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-primary btn-sm dropdown-toggle"
                                                            data-toggle="dropdown"><i class="fas fa-cogs"></i></button>
                                                        <div class="dropdown-menu" role="menu">
                                                            <a class="dropdown-item"
                                                                href="{{ route('pkm.edit', ['id' => base64_encode($item->id)]) }}">Edit</a>
                                                            <div class="dropdown-divider"></div>
                                                            {!! Form::open([
                                                                'route' => ['pkm.delete', base64_encode($item->id)],
                                                                'method' => 'DELETE',
                                                                'id' => 'remove-' . md5($item->id),
                                                            ]) !!}
                                                            <a class="dropdown-item" href="javascript:void(0)"
                                                                onclick="deleteData('{{ md5($item->id) }}')">Hapus</a>
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
                        <form class="form-horizontal"
                            action="{{ route('user.save', isset($data) ? base64_encode($data->id) : '') }}" method="POST"
                            enctype="multipart/form-data" id="form-data">
                            @csrf
                            @if (isset($user))
                                @method('PUT')
                            @endif
                            <div class="form-group row">
                                <label for="code" class="col-sm-2 col-form-label">Kode</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="code" id="code"
                                        placeholder="Masukan Kode" autocomplete="off"
                                        value="{{ isset($data) ? $data->name : '' }}">
                                    <span id="error-code" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="name" class="col-sm-2 col-form-label">Nama</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="name" id="name"
                                        placeholder="Masukan Nama" autocomplete="off"
                                        value="{{ isset($data) ? $data->name : '' }}">
                                    <span id="error-name" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <input type="file" name="image" id="image" style="display: none;">
                                <div class="offset-sm-2 col-sm-10">
                                    <button type="submit" class="btn btn-primary btn-sm"><i
                                            class="fas fa-save mr-1"></i> Simpan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
