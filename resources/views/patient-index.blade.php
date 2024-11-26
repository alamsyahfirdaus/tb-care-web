@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">Daftar {{ $title }}</h3>
                <div class="card-tools">
                    <a href="{{ route('user.add', ['id' => base64_encode(4)]) }}"
                        class="btn btn-primary btn-sm" title="Tambah {{ $title }}">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-hover" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 5%; text-align: center;">No</th>
                                <th>Nama</th>
                                <th>Jenis<span style="color: #fff; font-size: 10px;">_</span>Kelamin</th>
                                <th>No.<span style="color: #fff; font-size: 10px;">_</span>Handphone</th>
                                <th style="width: 5%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($patients as $key => $item)
                                <tr>
                                    <td style="text-align: center;">{{ $key + 1 }}</td>
                                    <td>{{ $item['full_name'] }}</td>
                                    <td>{{ $item['gender'] }}</td>
                                    <td>{{ $item['telephone'] }}</td>
                                    <td style="text-align: center;">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle"
                                                data-toggle="dropdown"><i class="fas fa-cogs"></i></button>
                                            <div class="dropdown-menu" role="menu">
                                                <a class="dropdown-item"
                                                    href="{{ route('user.show', ['id' => base64_encode($item['user_id'])]) }}">Detail</a>
                                                <div class="dropdown-divider"></div>
                                                {!! Form::open([
                                                    'route' => ['user.delete', base64_encode($item['user_id'])],
                                                    'method' => 'DELETE',
                                                    'id' => 'remove-' . md5($item['user_id']),
                                                ]) !!}
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="deleteData('{{ md5($item['user_id']) }}')">Hapus</a>
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
