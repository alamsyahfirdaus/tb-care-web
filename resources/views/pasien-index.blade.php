@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Daftar {{ $title }}</h3>
                <div class="card-tools">
                    <a href="{{ route('patient.add') }}" class="btn btn-tool" title="Tambah {{ $title }}">
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
                                <th>Nama Lengkap</th>
                                <th>Ponsel</th>
                                <th>Status<span>_</span>Pengobatan</th>
                                <th>Tanggal<span>_</span>Diagnosis</th>
                                <th>Puskesmas</th>
                                <th style="width: 5%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pasien as $key => $item)
                                <tr>
                                    <td style="text-align: center;">{{ $key + 1 }}</td>
                                    <td>{{ $item->user->name }}</td>
                                    <td>{{ $item->user->ponsel ?? '-' }}</td>
                                    <td>{{ $item->StatusPengobatan->nama ?? '-' }}</td>
                                    <td>{{ $item->tanggal_diagnosis ? date('d F Y', strtotime($item->tanggal_diagnosis)) : '-' }}
                                    </td>
                                    <td>{{ isset($item->puskesmas) ? $item->puskesmas->kode . ' - ' . $item->puskesmas->nama : '-' }}</td>
                                    <td style="text-align: center;">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle"
                                                data-toggle="dropdown">Aksi </button>
                                            <div class="dropdown-menu" role="menu">
                                                <a class="dropdown-item"
                                                    href="{{ route('patient.show', ['id' => base64_encode($item->id)]) }}">Detail</a>
                                                <div class="dropdown-divider"></div>
                                                {!! Form::open([
                                                    'route' => ['patient.delete', base64_encode($item->id)],
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
