@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Daftar {{ $title }}</h3>
                <div class="card-tools">
                    <a href="{{ route('user.add') }}" class="btn btn-tool" title="Tambah {{ $title }}">
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
                                <th>Email</th>
                                <th>Ponsel</th>
                                <th>Level</th>
                                <th style="width: 5%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $key => $user)
                                <tr>
                                    <td style="text-align: center;">{{ $key + 1 }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->ponsel ?? '-' }}</td>
                                    <td>{{ optional($user->userType)->user_type ?? '-' }}</td>
                                    <td style="text-align: center;">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle"
                                                data-toggle="dropdown">Aksi </button>
                                            <div class="dropdown-menu" role="menu">
                                                <a class="dropdown-item"
                                                    href="{{ route('user.edit', ['id' => base64_encode($user->id)]) }}">Edit</a>
                                                @if ($user->id != Auth::id())
                                                    <div class="dropdown-divider"></div>
                                                    {!! Form::open([
                                                        'route' => ['user.delete', base64_encode($user->id)],
                                                        'method' => 'DELETE',
                                                        'id' => 'remove-' . md5($user->id),
                                                    ]) !!}
                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                        onclick="deleteData('{{ md5($user->id) }}')">Hapus</a>
                                                    {!! Form::close() !!}
                                                @endif
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
