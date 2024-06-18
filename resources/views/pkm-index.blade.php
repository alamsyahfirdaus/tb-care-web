@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Daftar {{ $title }}</h3>
                <div class="card-tools">
                    <a href="{{ route('pkm.add') }}" class="btn btn-tool" title="Tambah {{ $title }}">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%; text-align: center;">No</th>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Lokasi</th>
                                <th>Wilayah</th>
                                <th style="width: 5%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($puskesmas as $key => $item)
                                <tr>
                                    <td style="text-align: center;">{{ $key + 1 }}</td>
                                    <td>{{ $item->kode ?? '-' }}</td>
                                    <td>{{ $item->nama }}</td>
                                    <td>{{ $item->lokasi }}</td>
                                    <td>
                                        {{ $item->subdistrict->subdistrict_name . ', ' . $item->subdistrict->district->district_name . ', ' . $item->subdistrict->district->province->province_name }}
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle"
                                                data-toggle="dropdown">Aksi </button>
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
        </div>
    </div>
@endsection
