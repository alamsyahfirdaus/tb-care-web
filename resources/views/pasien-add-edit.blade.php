@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div id="alert-message"></div>
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">{{ isset($data) ? 'Edit' : 'Tambah' }} {{ $title }}</h3>
                    </div>
                    <form action="{{ route('patient.save', isset($data) ? base64_encode($data->id) : '') }}" method="POST"
                        enctype="multipart/form-data" id="form-data">
                        @csrf
                        @if (isset($data))
                            @method('PUT')
                        @endif
                        <div class="card-body">
                            <div class="bs-stepper">
                                <div class="bs-stepper-header" role="tablist">
                                    <div class="step" data-target="#stepper1-part">
                                        <button type="button" class="step-trigger" role="tab"
                                            aria-controls="stepper1-part" id="stepper1-part-trigger">
                                            <span class="bs-stepper-circle">1</span>
                                            <span class="bs-stepper-label">Data Akun</span>
                                        </button>
                                    </div>
                                    <div class="line"></div>
                                    <div class="step" data-target="#stepper2-part">
                                        <button type="button" class="step-trigger" role="tab"
                                            aria-controls="stepper2-part" id="stepper2-part-trigger">
                                            <span class="bs-stepper-circle">2</span>
                                            <span class="bs-stepper-label">Data Pasien</span>
                                        </button>
                                    </div>
                                    <div class="line"></div>
                                    <div class="step" data-target="#stepper3-part">
                                        <button type="button" class="step-trigger" role="tab"
                                            aria-controls="stepper3-part" id="stepper3-part-trigger">
                                            <span class="bs-stepper-circle">3</span>
                                            <span class="bs-stepper-label">Data Pengobatan</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="bs-stepper-content">
                                    <div id="stepper1-part" class="content" role="tabpanel"
                                        aria-labelledby="stepper1-part-trigger">
                                        <div class="form-group">
                                            <label for="email">Email<small style="color: #dc3545;">*</small></label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="email" id="email"
                                                    placeholder="Masukan Email" autocomplete="off"
                                                    value="{{ isset($data) ? $data->user->email : '' }}">
                                                <div class="input-group-append">
                                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                </div>
                                            </div>
                                            <span id="error-email" class="error invalid-feedback"></span>
                                        </div>
                                        <div class="form-group">
                                            <label for="password">Password<small style="color: #dc3545;">*</small></label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" name="password" id="password"
                                                    placeholder="{{ isset($data) ? 'Biarkan Password Kosong Jika Tidak Akan Diubah' : 'Password Default (Sama dengan Email)' }}">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-default" id="btn-password"><i
                                                            class="fas fa-eye-slash"></i></button>
                                                </div>
                                            </div>
                                            <span id="error-password" class="error invalid-feedback"></span>
                                        </div>
                                        <small style="color: #dc3545;">{{ '*) Bidang Wajib Diisi.' }}</small>
                                        <hr style="margin-top: 8px;">
                                        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-times-circle"></i> Batal
                                        </a>
                                        <button type="button" class="btn btn-primary btn-sm float-right"
                                            onclick="validateStep1()"><i class="fas fa-angle-double-right"></i>
                                            Selanjutnya</button>
                                    </div>
                                    <div id="stepper2-part" class="content" role="tabpanel"
                                        aria-labelledby="stepper2-part-trigger">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="nik">NIK</label>
                                                    <input type="text" class="form-control" name="nik"
                                                        id="nik" placeholder="Masukan NIK" autocomplete="off"
                                                        value="{{ isset($data->nik) ? $data->nik : $nextNik }}">
                                                    <span id="error-nik" class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="name">Nama Lengkap<small
                                                            style="color: #dc3545;">*</small></label>
                                                    <input type="text" class="form-control" name="name"
                                                        id="name" placeholder="Masukan Nama" autocomplete="off"
                                                        value="{{ isset($data) ? $data->user->name : '' }}">
                                                    <span id="error-name" class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="jenis_kelamin">Jenis Kelamin<small
                                                            style="color: #dc3545;">*</small></label>
                                                    {!! Form::select(
                                                        'jenis_kelamin',
                                                        ['Laki-laki' => 'Laki-laki', 'Perempuan' => 'Perempuan'],
                                                        isset($data) ? $data->user->jenis_kelamin : null,
                                                        ['class' => 'form-control select2', 'id' => 'jenis_kelamin', 'placeholder' => 'Pilih Jenis Kelamin'],
                                                    ) !!}
                                                    <span id="error-jenis_kelamin" class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="tempat_lahir">Tempat Lahir<small
                                                            style="color: #dc3545;">*</small></label>
                                                    <input type="text" class="form-control" name="tempat_lahir"
                                                        id="tempat_lahir" placeholder="Masukan Tempat Lahir"
                                                        autocomplete="off"
                                                        value="{{ isset($data) ? $data->user->tempat_lahir : '' }}">
                                                    <span id="error-tempat_lahir" class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="tanggal_lahir">Tanggal Lahir<small
                                                            style="color: #dc3545;">*</small></label>
                                                    <input type="text" class="form-control datetimepicker-input"
                                                        id="tanggal_lahir" name="tanggal_lahir"
                                                        data-target="#tanggal_lahir" data-toggle="datetimepicker"
                                                        placeholder="Masukan Tanggal Lahir" autocomplete="off"
                                                        value="{{ isset($data->user->tanggal_lahir) ? date('d-m-Y', strtotime($data->user->tanggal_lahir)) : '' }}">
                                                    <span id="error-tanggal_lahir" class="error invalid-feedback"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="ponsel">Ponsel</label>
                                                    <input type="text" class="form-control" name="ponsel"
                                                        id="ponsel" placeholder="Masukan Ponsel" autocomplete="off"
                                                        value="{{ isset($data) ? $data->user->ponsel : '' }}">
                                                    <span id="error-ponsel" class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="alamat">Alamat</label>
                                                    <input type="text" class="form-control" name="alamat"
                                                        id="alamat" placeholder="Masukan Alamat" autocomplete="off"
                                                        value="{{ isset($data) ? $data->user->alamat : '' }}">
                                                    <span id="error-alamat" class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="subdistrict_id">Kecamatan<small
                                                            style="color: #dc3545;">*</small></label>
                                                    {!! Form::select(
                                                        'subdistrict_id',
                                                        $subdistricts->pluck('subdistrict_name', 'id'),
                                                        isset($data) ? $data->subdistrict_id : null,
                                                        ['class' => 'form-control select2', 'id' => 'subdistrict_id', 'placeholder' => 'Pilih Kecamatan'],
                                                    ) !!}
                                                    <span id="error-subdistrict_id" class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="district_name">Kota/Kab.</label>
                                                    <input type="text" name="district_name" id="district_name"
                                                        class="form-control"
                                                        value="{{ isset($data) ? $data->subdistrict->district->district_name : '' }}"
                                                        placeholder="Kota/Kab." disabled>
                                                </div>
                                                <div class="form-group">
                                                    <label for="province_name">Provinsi</label>
                                                    <input type="text" name="province_name" id="province_name"
                                                        class="form-control"
                                                        value="{{ isset($data) ? $data->subdistrict->district->province->province_name : '' }}"
                                                        placeholder="Provinsi" disabled>
                                                </div>
                                            </div>
                                        </div>
                                        <small style="color: #dc3545;">{{ '*) Bidang Wajib Diisi.' }}</small>
                                        <hr style="margin-top: 8px;">
                                        <button class="btn btn-secondary btn-sm" onclick="stepper.previous()"><i
                                                class="fas fa-angle-double-left"></i> Sebelumnya</button>
                                        <button type="button" class="btn btn-primary btn-sm float-right"
                                            onclick="validateStep2()"><i class="fas fa-angle-double-right"></i>
                                            Selanjutnya</button>
                                    </div>
                                    <div id="stepper3-part" class="content" role="tabpanel"
                                        aria-labelledby="stepper3-part-trigger">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="puskesmas_id">Puskesmas<small
                                                            style="color: #dc3545;">*</small></label>
                                                    <select name="puskesmas_id" id="puskesmas_id"
                                                        class="form-control select2" style="width: 100%;">
                                                        <option value="">Pilih Puskesmas</option>
                                                        @foreach ($puskesmas as $field)
                                                            <option value="{{ $field->id }}"
                                                                {{ isset($data) && $data->puskesmas_id == $field->id ? 'selected' : '' }}>
                                                                {{ $field->kode . ' - ' . $field->nama }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <span id="error-puskesmas_id" class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="wilayah">Wilayah</label>
                                                    @php
                                                        if (isset($data)) {
                                                            $subdistrict_name =
                                                                $data->puskesmas->subdistrict->subdistrict_name;
                                                            $district_name =
                                                                $data->puskesmas->subdistrict->district->district_name;
                                                            $province_name =
                                                                $data->puskesmas->subdistrict->district->province
                                                                    ->province_name;
                                                            $wilayah =
                                                                $subdistrict_name .
                                                                ', ' .
                                                                $district_name .
                                                                ', ' .
                                                                $province_name;
                                                        } else {
                                                            $wilayah = '';
                                                        }

                                                    @endphp
                                                    <input type="text" name="wilayah" id="wilayah"
                                                        class="form-control" value="{{ $wilayah }}"
                                                        placeholder="Wilayah" disabled>
                                                </div>
                                                <div class="form-group">
                                                    <label for="tanggal_diagnosis">Tanggal Diagnosis<small
                                                            style="color: #dc3545;">*</small></label>
                                                    <input type="text" class="form-control datetimepicker-input"
                                                        id="tanggal_diagnosis" name="tanggal_diagnosis"
                                                        data-target="#tanggal_diagnosis" data-toggle="datetimepicker"
                                                        placeholder="Masukan Tanggal Diagnosis" autocomplete="off"
                                                        value="{{ isset($data->tanggal_diagnosis) ? date('d-m-Y', strtotime($data->tanggal_diagnosis)) : '' }}">
                                                    <span id="error-tanggal_diagnosis"
                                                        class="error invalid-feedback"></span>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="status_pengobatan_id">Status Pengobatan<small
                                                            style="color: #dc3545;">*</small></label>
                                                    <select name="status_pengobatan_id" id="status_pengobatan_id"
                                                        class="form-control select2" style="width: 100%;">
                                                        <option value="">Pilih Status Pengobatan</option>
                                                        @foreach ($status_pengobatan as $field)
                                                            <option value="{{ $field->id }}"
                                                                {{ isset($data) && $data->status_pengobatan_id == $field->id ? 'selected' : '' }}>
                                                                {{ $field->nama }}</option>
                                                        @endforeach
                                                    </select>
                                                    <span id="error-status_pengobatan_id"
                                                        class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="tanggal_mulai_pengobatan">Tgl. Mulai Pengobatan<small
                                                            style="color: #dc3545;">*</small></label>
                                                    <input type="text" class="form-control datetimepicker-input"
                                                        id="tanggal_mulai_pengobatan" name="tanggal_mulai_pengobatan"
                                                        data-target="#tanggal_mulai_pengobatan"
                                                        data-toggle="datetimepicker"
                                                        placeholder="Masukan Tgl. Mulai Pengobatan" autocomplete="off"
                                                        value="{{ isset($data->tanggal_mulai_pengobatan) ? date('d-m-Y', strtotime($data->tanggal_mulai_pengobatan)) : '' }}">
                                                    <span id="error-tanggal_mulai_pengobatan"
                                                        class="error invalid-feedback"></span>
                                                </div>
                                                <div class="form-group">
                                                    <label for="tanggal_selesai_pengobatan">Tgl. Selesai Pengobatan<small
                                                            style="color: #dc3545;">*</small></label>
                                                    <input type="text" class="form-control datetimepicker-input"
                                                        id="tanggal_selesai_pengobatan" name="tanggal_selesai_pengobatan"
                                                        data-target="#tanggal_selesai_pengobatan"
                                                        data-toggle="datetimepicker"
                                                        placeholder="Masukan Tgl. Mulai Pengobatan" autocomplete="off"
                                                        value="{{ isset($data->tanggal_selesai_pengobatan) ? date('d-m-Y', strtotime($data->tanggal_selesai_pengobatan)) : '' }}">
                                                    <span id="error-tanggal_selesai_pengobatan"
                                                        class="error invalid-feedback"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <small style="color: #dc3545;">{{ '*) Bidang Wajib Diisi.' }}</small>
                                        <hr style="margin-top: 8px;">
                                        <button class="btn btn-secondary btn-sm" onclick="stepper.previous()"><i
                                                class="fas fa-angle-double-left"></i> Sebelumnya</button>
                                        <button type="button" class="btn btn-primary btn-sm float-right"
                                            onclick="validateStep3()"><i class="fas fa-save"></i>
                                            Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.stepper = new Stepper(document.querySelector('.bs-stepper'))
        });

        @if (empty($data->id))
            $('#email').keyup(function(e) {
                $('#password').val($(this).val()).keyup();
            });
        @endif

        function validateStep1() {
            $('#email, #password').keyup(function() {
                var inputField = $(this);
                var errorField = $('#error-' + inputField.attr('id'));
                if (inputField.val().trim() !== '') {
                    errorField.text('').hide();
                    inputField.removeClass('is-invalid');
                }
            });

            var email = $('#email').val().trim();
            var password = $('#password').val().trim();

            if (email === '') {
                $('#error-email').text('Email harus diisi.').show();
                $('#email').addClass('is-invalid');
            }

            @if (empty($data->id))
                if (password === '') {
                    $('#error-password').text('Password harus diisi.').show();
                    $('#password').addClass('is-invalid');
                }
            @endif

            if ($('.is-invalid').length === 0) {
                stepper.next();
            }
        }

        function validateStep2() {
            var fields = [{
                    id: $('#nik'),
                    error: $('#error-nik'),
                    message: 'NIK harus diisi.'
                },
                {
                    id: $('#name'),
                    error: $('#error-name'),
                    message: 'Nama Lengkap harus diisi.'
                },
                {
                    id: $('#jenis_kelamin'),
                    error: $('#error-jenis_kelamin'),
                    message: 'Jenis Kelamin harus dipilih.'
                },
                {
                    id: $('#tempat_lahir'),
                    error: $('#error-tempat_lahir'),
                    message: 'Tempat Lahir harus diisi.'
                },
                {
                    id: $('#tanggal_lahir'),
                    error: $('#error-tanggal_lahir'),
                    message: 'Tanggal Lahir harus diisi.'
                },
                {
                    id: $('#subdistrict_id'),
                    error: $('#error-subdistrict_id'),
                    message: 'Kecamatan harus dipilih.'
                }
            ];

            $.each(fields, function(index, field) {
                field.id.on('change keyup', function() {
                    $(this).removeClass('is-invalid');
                    field.error.text('').hide();
                });

                if (field.id.val().trim() === '') {
                    field.error.text(field.message).show();
                    field.id.addClass('is-invalid');
                } else {
                    field.error.text('').hide();
                    field.id.removeClass('is-invalid');
                }

                if (field.id.hasClass('select2')) {
                    if (field.id.val() === '') {
                        field.id.next().find('.select2-selection').addClass('border border-danger');
                    }
                    field.id.change(function() {
                        $(this).next().find('.select2-selection').removeClass('border border-danger');
                    });
                }
            });

            if ($('.is-invalid').length === 0) {
                stepper.next();
            }
        }

        function validateStep3() {
            $('#tanggal_diagnosis, #tanggal_mulai_pengobatan, #tanggal_selesai_pengobatan').each(function() {
                var inputField = $(this);
                var errorField = $('#error-' + inputField.attr('id'));
                if (inputField.hasClass('is-invalid')) {
                    errorField.text('').hide();
                    inputField.removeClass('is-invalid');
                }
            });

            if ($('.is-invalid').length > 0) {
                Swal.fire({
                    title: "Gagal!",
                    text: "Periksa isian yang tidak valid.",
                    icon: "error",
                    showConfirmButton: false,
                    timer: 1750
                });
            } else {
                $('#form-data').submit();
            }
        }
    </script>
@endsection
