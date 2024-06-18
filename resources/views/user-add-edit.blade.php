@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div id="alert-message"></div>
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ isset($user) ? 'Edit' : 'Tambah' }} {{ $title }}</h3>
                    </div>
                    <form action="{{ route('user.save', isset($user) ? base64_encode($user->id) : '') }}" method="POST"
                        enctype="multipart/form-data" id="form-data">
                        @csrf
                        @if (isset($user))
                            @method('PUT')
                        @endif
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">Nama Lengkap</label>
                                <input type="text" class="form-control" name="name" id="name"
                                    placeholder="Masukan Nama" autocomplete="off"
                                    value="{{ isset($user) ? $user->name : '' }}">
                                <span id="error-name" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" class="form-control" name="email" id="email"
                                    placeholder="Masukan Email" autocomplete="off"
                                    value="{{ isset($user) ? $user->email : '' }}">
                                <span id="error-email" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="ponsel">Ponsel</label>
                                <input type="text" class="form-control" name="ponsel" id="ponsel"
                                    placeholder="Masukan Ponsel" autocomplete="off"
                                    value="{{ isset($user) ? $user->ponsel : '' }}">
                                <span id="error-ponsel" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password"
                                        placeholder="Masukan Password {{ empty($user) ? '(Default Email)' : '' }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" id="btn-password"><i
                                                class="fas fa-eye-slash"></i></button>
                                    </div>
                                </div>
                                <span id="error-password" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="user_type_id">Level</label>
                                {!! Form::select(
                                    'user_type_id',
                                    $user_types->pluck('user_type', 'id'),
                                    isset($user) ? $user->user_type_id : null,
                                    ['class' => 'form-control select2', 'id' => 'user_type_id', 'placeholder' => 'Pilih Level'],
                                ) !!}
                                <span id="error-user_type_id" class="error invalid-feedback"></span>
                            </div>
                            <div class="form-group">
                                <label for="profile">Foto Profile</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" name="profile" id="profile">
                                        <label class="custom-file-label" for="profile">Pilih File</label>
                                    </div>
                                </div>
                                <span id="error-profile" class="error invalid-feedback"></span>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times-circle"></i> Batal
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm float-right"><i class="fas fa-save"></i>
                                Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @if (empty($user))
        <script>
            $('#email').keyup(function(e) {
                $('#password').val($(this).val());
            });
        </script>
    @endif
@endsection
