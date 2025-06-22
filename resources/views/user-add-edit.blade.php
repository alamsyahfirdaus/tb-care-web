@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ empty($data->id) ? 'Tambah ' . $title : 'Edit ' . $title }}</h3>
                <div class="card-tools">
                    <a href="{{ url()->previous() }}" class="btn btn-primary btn-sm" title="Sebelumnya">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                </div>
            </div>
            <div class="card-body px-lg-0">
                <form class="form-horizontal"
                    action="{{ route('user.save', isset($data) ? base64_encode($data->id) : '') }}" method="POST"
                    enctype="multipart/form-data" id="form-data">
                    @csrf
                    @if (isset($user))
                        @method('PUT')
                    @endif

                    <div class="bs-stepper">
                        <div class="bs-stepper-header" role="tablist" style="display: none;">
                            @php
                                $stepper = [
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
                            <div id="information-part" class="content" role="tabpanel"
                                aria-labelledby="information-part-trigger">
                                <div class="form-group row">
                                    <label for="name" class="col-sm-3 col-form-label">Nama<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="name" id="name"
                                            placeholder="Masukan Nama" autocomplete="off"
                                            value="{{ isset($data) ? $data->name : '' }}">
                                        <span id="error-name" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="gender" class="col-sm-3 col-form-label">Jenis Kelamin<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        @php
                                            $listGender = ['Laki-laki', 'Perempuan'];
                                        @endphp
                                        <select class="form-control select2" name="gender" id="gender"
                                            style="width: 100%;">
                                            <option value="">Pilih Jenis Kelamin</option>
                                            @foreach ($listGender as $gender)
                                                <option value="{{ $gender }}"
                                                    {{ isset($data) && $data->gender == $gender ? 'selected' : '' }}>
                                                    {{ $gender }}</option>
                                            @endforeach
                                        </select>
                                        <span id="error-gender" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="place_of_birth" class="col-sm-3 col-form-label">Tempat Lahir<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="place_of_birth" id="place_of_birth"
                                            placeholder="Masukan Tempat Lahir" autocomplete="off"
                                            value="{{ isset($data) ? $data->place_of_birth : '' }}">
                                        <span id="error-place_of_birth" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="date_of_birth" class="col-sm-3 col-form-label">Tanggal Lahir<small
                                            class="text-danger">*</small></label>
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
                                    <label for="phone" class="col-sm-3 col-form-label">No. Handphone<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="phone" id="phone"
                                            placeholder="Masukan No. Handphone" autocomplete="off"
                                            value="{{ isset($data) ? $data->phone : '' }}">
                                        <span id="error-phone" class="error invalid-feedback"></span>
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
                                <div class="form-group" style="display: none">
                                    <input type="text" class="form-control" name="user_id" id="user_id"
                                        value="{{ isset($data) && $data->id ? md5($data->id) : '' }}">
                                    <input type="text" class="form-control" name="user_type"
                                        value="{{ $title }}">
                                    <input type="text" class="form-control" name="user_type_id" id="user_type_id"
                                        value="{{ $user_type_id }}">
                                    <input type="file" class="form-control" name="image" id="image">
                                </div>
                                <div @if (isset($data) && $data->id) style="display: none;" @endif>
                                    <hr>
                                    @if ($user_type_id == 2)
                                        <div class="form-group row">
                                            <label for="office_type_id" class="col-sm-3 col-form-label">Dinas
                                                Kesehatan<small class="text-danger">*</small></label>
                                            <div class="col-sm-9">
                                                <select class="form-control select2" name="office_type_id"
                                                    id="office_type_id" style="width: 100%;">
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
                                            <label for="office_address" class="col-sm-3 col-form-label">Alamat
                                                Kantor<small class="text-danger">*</small></label>
                                            <div class="col-sm-9">
                                                <textarea class="form-control" name="office_address" id="office_address" placeholder="Masukan Alamat Kantor">{{ isset($healthOffice) ? $healthOffice->office_address : '' }}</textarea>
                                                <span id="error-office_address" class="error invalid-feedback"></span>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="district_id" class="col-sm-3 col-form-label">Kabupaten/Kota<small
                                                    class="text-danger">*</small></label>
                                            <div class="col-sm-9">
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
                                                    class="col-sm-3 col-form-label">Koordinator<small
                                                        class="text-danger">*</small></label>
                                                <div class="col-sm-9">
                                                    <select name="coord_type_id" id="coord_type_id"
                                                        class="form-control select2" style="width: 100%;">
                                                        <option value="">Pilih Koordinator</option>
                                                        @foreach ($coord_types as $key => $value)
                                                            <option value="{{ $key }}"
                                                                {{ isset($data) && $pjtb->coord_type_id == $key ? 'selected' : '' }}>
                                                                {{ $value }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <span id="error-coord_type_id" class="error invalid-feedback"></span>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="form-group row">
                                            <label for="puskesmas_id" class="col-sm-3 col-form-label">Puskesmas<small
                                                    class="text-danger">*</small></label>
                                            <div class="col-sm-9">
                                                <select name="puskesmas_id" id="puskesmas_id"
                                                    class="form-control select2" style="width: 100%;">
                                                    <option value="">Pilih Puskesmas</option>
                                                    @foreach ($puskesmas as $key => $item)
                                                        <option value="{{ $item['id'] }}"
                                                            {{ isset($data) && $data->puskesmas_id == $item['id'] ? 'selected' : '' }}>
                                                            {{ $item['puskesmas'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span id="error-puskesmas_id" class="error invalid-feedback"></span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-3 col-sm-9">
                                        @if (isset($data) && $data->id)
                                            <button type="button" class="btn btn-primary btn-sm" id="next-button">
                                                <i class="fas fa-angle-double-right"></i> Selanjutnya
                                            </button>
                                        @else
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save mr-1"></i> Simpan
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div id="profile-part" class="content" role="tabpanel"
                                aria-labelledby="profile-part-trigger">
                                <div class="form-group row">
                                    <label for="username" class="col-sm-3 col-form-label">Username<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="username" id="username"
                                            placeholder="Masukan Username" autocomplete="off"
                                            value="{{ isset($data) ? $data->username : '' }}">
                                        <span id="error-username" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="password" class="col-sm-3 col-form-label">Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" name="password" id="password"
                                            placeholder="{{ empty($data) ? 'Masukkan Password' : 'Masukkan Password (biarkan kosong jika tidak ingin mengubahnya)' }}">
                                        <span id="error-password" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="profile" class="col-sm-3 col-form-label">Foto Profil</label>
                                    <div class="col-sm-9">
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
                                    <div class="offset-sm-3 col-sm-4">
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
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-3 col-sm-9">
                                        <button type="button" class="btn btn-secondary btn-sm"
                                            onclick="stepper.previous()">
                                            <i class="fas fa-angle-double-left"></i> Sebelumnya
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-sm">
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
                const name = $('#name').val().trim();
                const phone = $('#phone').val().trim();
                const gender = $('#gender').val();
                const placeOfBirth = $('#place_of_birth').val().trim();
                const dateOfBirth = $('#date_of_birth').val().trim();

                let valid = true;
                clearValidationErrors([
                    'name', 'phone', 'gender', 'place_of_birth', 'date_of_birth',
                    'office_type_id', 'office_address', 'district_id', 'puskesmas_id'
                ]);

                if (name === '') {
                    setValidationError('name', 'Nama harus diisi.');
                    valid = false;
                }

                if (phone === '') {
                    setValidationError('phone', 'No. Handphone harus diisi.');
                    valid = false;
                } else if (!/^\d+$/.test(phone)) {
                    setValidationError('phone', 'No. Handphone harus berupa angka.');
                    valid = false;
                }

                if (gender === '') {
                    setValidationError('gender', 'Jenis Kelamin harus dipilih.');
                    valid = false;
                }

                if (placeOfBirth === '') {
                    setValidationError('place_of_birth', 'Tempat Lahir harus diisi.');
                    valid = false;
                }

                if (dateOfBirth === '') {
                    setValidationError('date_of_birth', 'Tanggal Lahir harus diisi.');
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
                            setValidationError('office_type_id', 'Dinas Kesehatan harus dipilih.');
                            valid = false;
                        }

                        if (officeAddress === '') {
                            setValidationError('office_address', 'Alamat Kantor harus diisi.');
                            valid = false;
                        }

                        if (districtId === '') {
                            setValidationError('district_id', 'Kabupaten/Kota harus dipilih.');
                            valid = false;
                        }
                    } else {
                        const coordinatorType = $('#coord_type_id').val();

                        if (coordinatorType === '') {
                            setValidationError('coord_type_id', 'Koordinator harus dipilih.');
                            valid = false;
                        }

                        const puskesmasId = $('#puskesmas_id').val();

                        if (puskesmasId === '') {
                            setValidationError('puskesmas_id', 'Puskesmas harus dipilih.');
                            valid = false;
                        }
                    }
                }

                if (valid) {
                    stepper.next();
                }
            }

            $('#next-button').click(function() {
                validateStep1();
            });

            $('#name, #phone, #gender, #place_of_birth, #date_of_birth, #office_type_id, #office_address, #district_id, #puskesmas_id')
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
