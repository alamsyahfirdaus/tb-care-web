@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ empty($data->id) ? 'Tambah ' . $title : 'Edit ' . $title }}</h3>
                <div class="card-tools">
                    <a href="{{ url()->previous() }}" class="btn btn-primary btn-sm" title="Tutup Formulir">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="card-body px-lg-0 py-1">
                <form class="form-horizontal"
                    action="{{ route('user.save', isset($data) ? base64_encode($data->id) : '') }}" method="POST"
                    enctype="multipart/form-data" id="form-data">
                    @csrf
                    @if (isset($user))
                        @method('PUT')
                    @endif

                    <div class="bs-stepper">
                        <div class="bs-stepper-header" role="tablist">
                            @php
                                $stepper = [
                                    'account-part' => 'Akun ' . $title,
                                    'information-part' => 'Data ' . $title,
                                    'profile-part' => 'Foto Profil',
                                ];
                            @endphp

                            @foreach ($stepper as $key => $value)
                                <div class="step" data-target="#{{ $key }}">
                                    <button type="button" class="step-trigger" role="tab"
                                        aria-controls="{{ $key }}" id="{{ $key }}-trigger">
                                        <span class="bs-stepper-circle">{{ $loop->iteration }}</span>
                                        <span class="bs-stepper-label">{{ $value }}</span>
                                    </button>
                                </div>
                                @if (!$loop->last)
                                    <div class="line"></div>
                                @endif
                            @endforeach
                        </div>
                        <div class="bs-stepper-content">
                            <div id="account-part" class="content" role="tabpanel" aria-labelledby="account-part-trigger">
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
                                    <label for="password" class="col-sm-2 col-form-label">Password</label>
                                    <div class="col-sm-10">
                                        <input type="password" class="form-control" name="password" id="password"
                                            placeholder="{{ empty($data) ? 'Masukkan Password (password default adalah email)' : 'Biarkan kosong jika tidak ingin mengubah password' }}">
                                        <span id="error-password" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-2 col-sm-10">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="terms" class="custom-control-input"
                                                id="show-password">
                                            <label class="custom-control-label" for="show-password"
                                                style="font-weight: normal; font-size: 14px; padding-top: 2px;">
                                                Tampilkan Password
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-2 col-sm-10">
                                        <button type="button" class="btn btn-primary btn-sm float-right" id="next-button1">
                                            <i class="fas fa-angle-double-right"></i> Selanjutnya
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="information-part" class="content" role="tabpanel"
                                aria-labelledby="information-part-trigger">
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
                                    <label for="gender" class="col-sm-2 col-form-label">Jenis Kelamin</label>
                                    <div class="col-sm-10">
                                        <select class="form-control select2" name="gender" id="gender"
                                            style="width: 100%;">
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
                                    <label for="place_of_birth" class="col-sm-2 col-form-label">Tempat Lahir</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" name="place_of_birth"
                                            id="place_of_birth" placeholder="Masukan Tempat Lahir" autocomplete="off"
                                            value="{{ isset($data) ? $data->place_of_birth : '' }}">
                                        <span id="error-place_of_birth" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="date_of_birth" class="col-sm-2 col-form-label">Tanggal Lahir</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control datetimepicker-input"
                                            data-target="#reservationdate" data-toggle="datetimepicker"
                                            name="date_of_birth" id="date_of_birth" placeholder="Tanggal Lahir"
                                            value="{{ isset($data) && $data->date_of_birth ? \Carbon\Carbon::parse($data->date_of_birth)->format('d/m/Y') : '' }}"
                                            autocomplete="off">
                                        <span id="error-date_of_birth" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="telephone" class="col-sm-2 col-form-label">Telepon</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" name="telephone" id="telephone"
                                            placeholder="Masukan Telepon (Handphone)" autocomplete="off"
                                            value="{{ isset($data) ? $data->telephone : '' }}">
                                        <span id="error-telephone" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div @if (isset($data) && $data->id) style="display: none;" @endif>
                                    <hr>
                                    @if ($user_type_id == 2)
                                        <div class="form-group row">
                                            <label for="office_type_id" class="col-sm-2 col-form-label">Dinas
                                                Kesehatan</label>
                                            <div class="col-sm-10">
                                                <select class="form-control select2" name="office_type_id" id="office_type_id"
                                                    style="width: 100%;">
                                                    <option value="">Pilih Dinas Kesehatan</option>
                                                    @foreach ($office_types as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($healthOffice) && $healthOffice->office_type_id === $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span id="error-office_type_id" class="error invalid-feedback"></span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="office_address" class="col-sm-2 col-form-label">Alamat
                                                Kantor</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" name="office_address" id="office_address" placeholder="Masukan Alamat Kantor">{{ isset($healthOffice) ? $healthOffice->office_address : '' }}</textarea>
                                                <span id="error-office_address" class="error invalid-feedback"></span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="district_id"
                                                class="col-sm-2 col-form-label">Kabupaten/Kota</label>
                                            <div class="col-sm-10">
                                                <select name="district_id" id="district_id" class="form-control select2"
                                                    style="width: 100%;">
                                                    <option value="">Pilih Kabupaten/Kota</option>
                                                    @foreach ($districts as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($healthOffice) && $healthOffice->district_id == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span id="error-district_id" class="error invalid-feedback"></span>
                                            </div>
                                        </div>
                                    @else
                                        @if ($user_type_id == 3)
                                            <div class="form-group row">
                                                <label for="coord_type_id"
                                                    class="col-sm-2 col-form-label">PJTB/Kader</label>
                                                <div class="col-sm-10">
                                                    <select name="coord_type_id" id="coord_type_id"
                                                        class="form-control select2" style="width: 100%;">
                                                        <option value="">Pilih PJTB/Kader</option>
                                                        @foreach ($coord_types as $key => $value)
                                                            <option value="{{ $key }}"
                                                                {{ isset($data) && $pjtb->coord_type_id == $key ? 'selected' : '' }}>
                                                                {{ $value }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <span id="error-coord_type_id"
                                                        class="error invalid-feedback"></span>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="puskesmas_id"
                                                class="col-sm-2 col-form-label">{{ $user_type_id == 3 ? 'Puskesmas' : 'Tempat Berobat (Puskesmas)' }}</label>
                                            <div class="col-sm-10">
                                                <select name="puskesmas_id" id="puskesmas_id"
                                                    class="form-control select2" style="width: 100%;">
                                                    <option value="">Pilih Puskesmas</option>
                                                    @foreach ($puskesmas as $key => $value)
                                                        <option value="{{ $key }}"
                                                            {{ isset($data) && $puskesmas_id == $key ? 'selected' : '' }}>
                                                            {{ $value }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span id="error-puskesmas_id" class="error invalid-feedback"></span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-2 col-sm-10">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="stepper.previous()">
                                            <i class="fas fa-angle-double-left"></i> Sebelumnya
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm float-right"
                                            id="next-button2">
                                            <i class="fas fa-angle-double-right"></i> Selanjutnya
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="profile-part" class="content" role="tabpanel"
                                aria-labelledby="profile-part-trigger">
                                <div class="form-group row">
                                    <label for="profile" class="col-sm-2 col-form-label">Foto Profil</label>
                                    <div class="col-sm-10">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="profile" id="profile"
                                                placeholder="Pilih File" readonly
                                                value="{{ isset($data) && $data->profile ? $data->profile : '' }}"
                                                style="background-color: #ffffff;">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-default" id="btn-profile">
                                                    <i class="fas fa-image"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <span id="error-profile" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-2 col-sm-4">
                                        <div style="position: relative; display: inline-block;">
                                            <img id="profile-preview"
                                                src="{{ isset($data) && $data->profile ? asset('upload_images/' . $data->profile) : asset('assets/img/profile.png') }}"
                                                alt="{{ isset($data) && isset($data->username) ? $data->username : '' }}"
                                                style="width: 200px; height: auto; display: block;">
                                            <button type="button" class="btn btn-secondary btn-flat btn-sm"
                                                id="btn-remove-profile"
                                                style="position: absolute; top: 0px; right: 0px; border: none; color: #fff; {{ isset($data) && $data->profile ? '' : 'display: none;' }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-sm-6" style="display: none;">
                                        <input type="text" name="user_id" id="user_id"
                                            value="{{ isset($data) && $data->id ? md5($data->id) : '' }}">
                                        <input type="text" name="user_type" value="{{ $title }}">
                                        <input type="text" name="user_type_id" id="user_type_id"
                                            value="{{ $user_type_id }}">
                                        <input type="file" name="image" id="image">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-2 col-sm-10">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="stepper.previous()">
                                            <i class="fas fa-angle-double-left"></i> Sebelumnya
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-sm float-right">
                                            <i class="fas fa-save"></i> Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
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

            $('#btn-remove-profile').click(function() {
                $('#profile-preview').attr('src', '{{ asset('assets/img/profile.png') }}');

                $('#image').val('');
                $('#profile').val('');

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

                $(this).hide();
            });


            $('#image').change(function() {
                var file = this.files[0];
                if (file) {
                    var fileName = file.name;
                    $('#profile').val(fileName);

                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#profile-preview').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(file);

                    $('#btn-remove-profile').show();
                } else {
                    $('#btn-remove-profile').hide();
                }
            });

            $('#show-password').change(function() {
                const passwordField = $('#password');
                if ($(this).is(':checked')) {
                    passwordField.attr('type', 'text');
                } else {
                    passwordField.attr('type', 'password');
                }
            });

            function clearValidationErrors(fields) {
                fields.forEach(field => {
                    $(`#error-${field}`).text('').hide();
                    $(`#${field}`).removeClass('is-invalid');
                    if ($(`#${field}`).next().find('.select2-selection').length) {
                        $(`#${field}`).next().find('.select2-selection').removeClass(
                            'border border-danger');
                    }
                });
            }

            function setValidationError(field, message) {
                $(`#error-${field}`).text(message).show();
                $(`#${field}`).addClass('is-invalid');
                if ($(`#${field}`).next().find('.select2-selection').length) {
                    $(`#${field}`).next().find('.select2-selection').addClass('border border-danger');
                }
            }

            function validateStep1() {
                const email = $('#email').val().trim();
                const password = $('#password').val().trim();

                let valid = true;
                clearValidationErrors(['email', 'password']);

                if (email === '') {
                    setValidationError('email', 'Bidang email wajib diisi.');
                    valid = false;
                } else if (!/\S+@\S+\.\S+/.test(email)) {
                    setValidationError('email', 'Email harus alamat email yang valid.');
                    valid = false;
                }

                @if (empty($data->id))
                    if (password === '') {
                        setValidationError('password', 'Bidang password wajib diisi.');
                        valid = false;
                    }
                @endif

                if (valid) {
                    stepper.next();
                }
            }

            function validateStep2() {
                const name = $('#name').val().trim();
                const telephone = $('#telephone').val().trim();
                const gender = $('#gender').val();
                const placeOfBirth = $('#place_of_birth').val().trim();
                const dateOfBirth = $('#date_of_birth').val().trim();

                let valid = true;
                clearValidationErrors([
                    'name', 'telephone', 'gender', 'place_of_birth', 'date_of_birth',
                    'office_type_id', 'office_address', 'district_id', 'puskesmas_id'
                ]);

                if (name === '') {
                    setValidationError('name', 'Bidang nama wajib diisi.');
                    valid = false;
                }

                if (telephone === '') {
                    setValidationError('telephone', 'Bidang telepon wajib diisi.');
                    valid = false;
                } else if (!/^\d+$/.test(telephone)) {
                    setValidationError('telephone', 'Telepon harus berupa angka.');
                    valid = false;
                }

                if (gender === '') {
                    setValidationError('gender', 'Bidang jenis kelamin wajib dipilih.');
                    valid = false;
                }

                if (placeOfBirth === '') {
                    setValidationError('place_of_birth', 'Bidang tempat lahir wajib diisi.');
                    valid = false;
                }

                if (dateOfBirth === '') {
                    setValidationError('date_of_birth', 'Bidang tanggal lahir wajib diisi.');
                    valid = false;
                }

                const userId = $('#user_id').val().trim();
                const userTypeId = $('#user_type_id').val().trim();
                if (userId === '') {
                    if (userTypeId === '2') {
                        const officeType = $('#office_type_id').val();
                        const officeAddress = $('#office_address').val().trim();
                        const districtId = $('#district_id').val();

                        if (officeType === '') {
                            setValidationError('office_type_id', 'Bidang dinas kesehatan wajib dipilih.');
                            valid = false;
                        }

                        if (officeAddress === '') {
                            setValidationError('office_address', 'Bidang alamat kantor wajib diisi.');
                            valid = false;
                        }

                        if (districtId === '') {
                            setValidationError('district_id', 'Bidang kabupaten/kota wajib dipilih.');
                            valid = false;
                        }
                    } else {
                        const coordinatorType = $('#coord_type_id').val();

                        if (coordinatorType === '') {
                            setValidationError('coord_type_id', 'Bidang pjtb/kader wajib dipilih.');
                            valid = false;
                        }

                        const puskesmasId = $('#puskesmas_id').val();

                        if (puskesmasId === '') {
                            setValidationError('puskesmas_id', 'Bidang puskesmas wajib dipilih.');
                            valid = false;
                        }
                    }
                }

                if (valid) {
                    stepper.next();
                }
            }

            $('#next-button1').click(function() {
                validateStep1();
            });

            $('#next-button2').click(function() {
                validateStep2();
            });

            $('#email, #password, #name, #telephone, #gender, #place_of_birth, #date_of_birth, #office_type_id, #office_address, #district_id, #puskesmas_id')
                .on('keyup change', function() {
                    $(this).removeClass('is-invalid');
                    $('#error-' + $(this).attr('id')).text('').hide();
                    if ($(this).next().find('.select2-selection').length) {
                        $(this).next().find('.select2-selection').removeClass('border border-danger');
                    }
                });
        });

        document.addEventListener('DOMContentLoaded', function() {
            window.stepper = new Stepper(document.querySelector('.bs-stepper'))
        })
    </script>
@endsection
