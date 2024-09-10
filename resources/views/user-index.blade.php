@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ empty($data->id) ? 'Daftar ' . $title : 'Edit ' . $title }}</h3>
                <div class="card-tools">
                    @if ($user_type_id == 1)
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
                    @else
                        <a href="{{ route('user.add', ['id' => base64_encode($user_type_id)]) }}"
                            class="btn btn-primary btn-sm" title="Tambah {{ $title }}">
                            <i class="fas fa-plus"></i>
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
                                            @if ($user_type_id == 2)
                                                <th>Dinas<span style="color: #fff;">_</span>Kesehatan</th>
                                                <th>Alamat<span style="color: #fff;">_</span>Kantor</th>
                                            @elseif ($user_type_id == 3)
                                                <th>PJTB/Kader</th>
                                                <th>Puskesmas</th>
                                            @elseif ($user_type_id == 4)
                                                <th>Puskesmas</th>
                                            @endif
                                            <th style="width: 5%; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $key => $item)
                                            <tr>
                                                <td style="text-align: center;">{{ $key + 1 }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->email }}</td>
                                                <td>{{ $item->telephone ?? '-' }}</td>
                                                @if ($user_type_id == 2)
                                                    @php
                                                        $user = \App\Models\HealthOffice::with('district.province')
                                                            ->where('user_id', $item->id)
                                                            ->first();

                                                        $officeType = $user->office_type_id ? \App\Models\HealthOffice::getOfficeTypes($user->office_type_id) : '-';
                                                        $officeAddress = $user->office_address ?? '-';
                                                        $districtName = $user->district->name ?? '-';
                                                        $provinceName = $user->district->province->name ?? '-';
                                                    @endphp

                                                    <td>{{ $officeType }}</td>
                                                    <td>
                                                        {{ $officeAddress }}
                                                        @if ($user && $user->district_id)
                                                            <hr style="margin: 8px 0;">
                                                            {{ $districtName }} - Prov. {{ $provinceName }}
                                                        @endif
                                                    </td>
                                                @elseif (in_array($user_type_id, [3, 4]))
                                                    @php
                                                        $model =
                                                            $user_type_id == 3
                                                                ? \App\Models\Pjtb::class
                                                                : \App\Models\Patient::class;
                                                        $user = $model::where('user_id', $item->id)->first();
                                                        $puskesmas = $user
                                                            ? \App\Models\Puskesmas::getPuskesmasById(
                                                                $user->puskesmas_id,
                                                            )
                                                            : null;
                                                    @endphp
                                                    @if ($user_type_id == 3)
                                                        <td>{{ $user->coord_type_id ? \App\Models\Coordinator::getCoordTypes($user->coord_type_id,
                                                        ) : '-' }}</td>
                                                    @endif
                                                    <td>
                                                        {{ $puskesmas ? $puskesmas['name'] : '-' }}
                                                    </td>
                                                @endif

                                                <td style="text-align: center;">
                                                    <div class="btn-group">
                                                        <button type="button"
                                                            class="btn btn-primary btn-sm dropdown-toggle"
                                                            data-toggle="dropdown"><i class="fas fa-cogs"></i></button>
                                                        <div class="dropdown-menu" role="menu">
                                                            @if ($user_type_id == 1)
                                                                <a class="dropdown-item"
                                                                    href="{{ route('user.edit', ['id' => base64_encode($item->id)]) }}">Edit</a>
                                                            @else
                                                                <a class="dropdown-item"
                                                                    href="{{ route('user.show', ['id' => base64_encode($item->id)]) }}">Detail</a>
                                                            @endif
                                                            @if ($item->id != Auth::id())
                                                                <div class="dropdown-divider"></div>
                                                                {!! Form::open([
                                                                    'route' => ['user.delete', base64_encode($item->id)],
                                                                    'method' => 'DELETE',
                                                                    'id' => 'remove-' . md5($item->id),
                                                                ]) !!}
                                                                <a class="dropdown-item" href="javascript:void(0)"
                                                                    onclick="deleteData('{{ md5($item->id) }}')">Hapus</a>
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
                                <label for="name" class="col-sm-3 col-form-label">Nama</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name" id="name"
                                        placeholder="Masukan Nama" autocomplete="off"
                                        value="{{ isset($data) ? $data->name : '' }}">
                                    <span id="error-name" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="gender" class="col-sm-3 col-form-label">Jenis Kelamin</label>
                                <div class="col-sm-9">
                                    <select class="form-control select2" name="gender" id="gender" style="width: 100%;">
                                        <option value="">Pilih Jenis Kelamin</option>
                                        <option value="Laki-laki"
                                            {{ isset($data) && $data->gender == 'Laki-laki' ? 'selected' : '' }}>
                                            Laki-laki
                                        </option>
                                        <option value="Perempuan"
                                            {{ isset($data) && $data->gender == 'Perempuan' ? 'selected' : '' }}>
                                            Perempuan
                                        </option>
                                    </select>
                                    <span id="error-gender" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="place_of_birth" class="col-sm-3 col-form-label">Tempat Lahir</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="place_of_birth" id="place_of_birth"
                                        placeholder="Masukan Tempat Lahir" autocomplete="off"
                                        value="{{ isset($data) ? $data->place_of_birth : '' }}">
                                    <span id="error-place_of_birth" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="date_of_birth" class="col-sm-3 col-form-label">Tanggal Lahir</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control datetimepicker-input"
                                        data-target="#reservationdate" data-toggle="datetimepicker" name="date_of_birth"
                                        id="date_of_birth" placeholder="Tanggal Lahir"
                                        value="{{ isset($data) && $data->date_of_birth ? \Carbon\Carbon::parse($data->date_of_birth)->format('d/m/Y') : '' }}"
                                        autocomplete="off">
                                    <span id="error-date_of_birth" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="telephone" class="col-sm-3 col-form-label">Telepon</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="telephone" id="telephone"
                                        placeholder="Masukan Telepon (Handphone)" autocomplete="off"
                                        value="{{ isset($data) ? $data->telephone : '' }}">
                                    <span id="error-telephone" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="email" class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="email" id="email"
                                        placeholder="Masukan Email" autocomplete="off"
                                        value="{{ isset($data) ? $data->email : '' }}">
                                    <span id="error-email" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="password" class="col-sm-3 col-form-label">Password</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" id="password"
                                            placeholder="{{ empty($data) ? 'Masukkan Password (password default adalah email)' : 'Biarkan kosong jika tidak ingin mengubah password' }}">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-default" id="btn-password"><i
                                                    class="fas fa-eye-slash"></i></button>
                                        </div>
                                    </div>
                                    <span id="error-password" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="profile" class="col-sm-3 col-form-label">Foto Profile</label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="profile" id="profile"
                                            placeholder="Pilih File" readonly
                                            value="{{ isset($data) && $data->profile ? $data->profile : '' }}"
                                            style="background-color: #ffffff;">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-default" id="btn-profile"><i
                                                    class="fas fa-image"></i></button>
                                        </div>
                                    </div>
                                    <span id="error-profile" class="error invalid-feedback"></span>
                                </div>
                            </div>
                            @if (isset($data) && $data->profile)
                                <div class="form-group row">
                                    <div class="offset-sm-2 col-sm-9">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="terms" class="custom-control-input"
                                                id="remove-profile">
                                            <label class="custom-control-label" for="remove-profile"
                                                style="font-weight: normal; font-size: 14px; padding-top: 2px;">
                                                Hapus Foto Profile
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="form-group row">
                                <input type="hidden" name="user_type_id" value="{{ $user_type_id }}">
                                <input type="file" name="image" id="image" style="display: none;">
                                <div class="offset-sm-3 col-sm-9">
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

            $('#remove-profile').change(function() {
                if ($(this).is(':checked')) {
                    if ($('#remove_image').length === 0) {
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'remove_image',
                            name: 'remove_image',
                            value: '1'
                        }).appendTo('form');
                    } else {
                        $('#remove_image').val('1');
                    }
                    $('#image').val('');
                    $('#profile').val('');
                } else {
                    var previousProfile = '{{ isset($data) && $data->profile ? $data->profile : '' }}';
                    $('#profile').val(previousProfile);

                    if ($('#remove_image').length > 0) {
                        $('#remove_image').val('0');
                    } else {
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'remove_image',
                            name: 'remove_image',
                            value: '0'
                        }).appendTo('form');
                    }
                }
            });


        });
    </script>

@endsection
