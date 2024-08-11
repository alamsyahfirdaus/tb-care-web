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
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Telepon</th>
                                            {{-- <th>Level</th> --}}
                                            <th style="width: 5%; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $key => $user)
                                            <tr>
                                                <td style="text-align: center;">{{ $key + 1 }}</td>
                                                <td>{{ $user->name }}</td>
                                                <td>{{ $user->email }}</td>
                                                <td>{{ $user->telephone ?? '-' }}</td>
                                                {{-- <td>{{ optional($user->userType)->user_type ?? '-' }}</td> --}}
                                                <td style="text-align: center;">
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-primary btn-sm dropdown-toggle"
                                                            data-toggle="dropdown"><i class="fas fa-cogs"></i></button>
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
                                <label for="name" class="col-sm-2 col-form-label">Nama</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="name" id="name"
                                        placeholder="Masukan Nama" autocomplete="off"
                                        value="{{ isset($data) ? $data->name : '' }}">
                                    <span id="error-name" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="email" class="col-sm-2 col-form-label">Email</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="email" id="email"
                                        placeholder="Masukan Email" autocomplete="off"
                                        value="{{ isset($data) ? $data->email : '' }}">
                                    <span id="error-email" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="telephone" class="col-sm-2 col-form-label">Telepon</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="telephone" id="telephone"
                                        placeholder="Masukan Telepon" autocomplete="off"
                                        value="{{ isset($data) ? $data->telephone : '' }}">
                                    <span id="error-telephone" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="password" class="col-sm-2 col-form-label">Password</label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="password"
                                            placeholder="Masukan Password {{ empty($data) ? '(Default Email)' : '' }}">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-default" id="btn-password"><i
                                                    class="fas fa-eye-slash"></i></button>
                                        </div>
                                    </div>
                                    <span id="error-password" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="user_type_id" class="col-sm-2 col-form-label">Level</label>
                                <div class="col-sm-10">
                                    {!! Form::select(
                                        'user_type_id',
                                        $user_types->pluck('user_type', 'id'),
                                        isset($data) ? $data->user_type_id : null,
                                        [
                                            'class' => 'form-control select2',
                                            'id' => 'user_type_id',
                                            'placeholder' => 'Pilih Level',
                                            'style' => 'width: 100%;',
                                        ],
                                    ) !!}
                                    <span id="error-user_type_id" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="profile" class="col-sm-2 col-form-label">Foto Profile</label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="profile" id="profile"
                                            placeholder="Pilih File" readonly style="background-color: #ffffff;">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-default" id="btn-profile"><i
                                                    class="fas fa-image"></i></button>
                                        </div>
                                    </div>
                                    <span id="error-profile" class="error invalid-feedback"></span>
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
    <script>
        $(function() {
            @if (empty($data->id))
                $('#email').keyup(function(e) {
                    $('#password').val($(this).val()).keyup();
                });
            @endif

            $('#btn-profile, #profile').click(function() {
                $('#image').click();
            });

            $('#image').change(function() {
                var fileName = $(this).val().split('\\').pop();
                $('#profile').val(fileName);
            });

        });
    </script>

@endsection
