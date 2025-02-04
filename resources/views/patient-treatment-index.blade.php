@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ isset($data['id']) ? 'Edit ' : 'Daftar ' }} {{ $title }}</h3>
                @if (session('role') != 4)
                    <div class="card-tools">
                        @if (empty($data['id']))
                            <a href="javascript:void(0)" id="add-data-toggle" class="btn btn-primary btn-sm"
                                title="Tambah {{ $title }}">
                                <i class="fas fa-plus"></i>
                            </a>
                        @else
                            <a href="{{ url()->previous() }}" class="btn btn-primary btn-sm" title="Sebelumnya">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
            <div class="card-body">
                @if (session('role') == 4)
                    <div class="table-responsive">
                        <table id="datatable" class="table table-bordered" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center;">No</th>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Foto</th>
                                    <th style="width: 5%;">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($treatments as $key => $item)
                                    @php
                                        $isToday = \Carbon\Carbon::parse($item['date'])->isToday();
                                        $statusBadge = $item['status']
                                            ? 'success|SUDAH MINUM OBAT'
                                            : ($item['date'] >= date('Y-m-d')
                                                ? 'warning|BELUM MINUM OBAT'
                                                : 'danger|TIDAK MINUM OBAT');
                                        [$badgeClass, $badgeText] = explode('|', $statusBadge);
                                    @endphp
                                    <tr style="{{ $isToday ? 'background-color: #ececf6;' : '' }}">
                                        <td style="text-align: center;">{{ $key + 1 }}</td>
                                        <td>{{ \App\Helpers\DateHelper::convertDate($item['date']) }}</td>
                                        <td>{{ $item['hour'] ?? '-' }}</td>
                                        <td>{!! $item['photo'] ? '<img src="' . $item['photo'] . '" alt="Photo" class="img-fluid" style="width: 150px; height: 150px;">' : '' !!}</td>
                                        <td>
                                            <span class="badge badge-{{ $badgeClass }} p-2">{{ $badgeText }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="tab-content p-0">
                        @if (empty($data['id']))
                            <div class="tab-pane active" id="tab1">
                                <div class="table-responsive">
                                    <table id="datatable" class="table table-bordered table-hover" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%; text-align: center;">No</th>
                                                <th>Nama<span style="color: #fff; font-size: 10px;">_</span>Pasien</th>
                                                <th>Tanggal<span style="color: #fff; font-size: 10px;">_</span>Diagnosis
                                                </th>
                                                <th>Jenis<span style="color: #fff; font-size: 10px;">_</span>Pengobatan</th>
                                                <th>Status<span style="color: #fff; font-size: 10px;">_</span>Pengobatan
                                                </th>
                                                <th style="width: 5%; text-align: center;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($treatments as $key => $item)
                                                <tr>
                                                    <td style="text-align: center;">{{ $key + 1 }}</td>
                                                    <td>{{ $item['full_name'] }}</td>
                                                    <td>{{ $item['diagnosis_date'] ? \App\Helpers\DateHelper::convertDate($item['diagnosis_date']) : '-' }}
                                                    </td>
                                                    <td>{{ $item['treatment_type'] }}</td>
                                                    <td>
                                                        @php
                                                            $completedTreatment = \App\Models\MedicationRecord::countRecords(
                                                                $item['id'],
                                                            );
                                                            $totalTreatmentCount = count(
                                                                \App\Models\PatientTreatment::getTreatmentDateRange(
                                                                    $item['id'],
                                                                ),
                                                            );
                                                        @endphp

                                                        {{ $completedTreatment == $totalTreatmentCount ? 'Selesai' : 'Berjalan' }}
                                                        {{ $completedTreatment }} dari {{ $totalTreatmentCount }} Hari
                                                    </td>

                                                    <td style="text-align: center;">
                                                        <div class="btn-group">
                                                            <button type="button"
                                                                class="btn btn-primary btn-sm dropdown-toggle"
                                                                data-toggle="dropdown"><i class="fas fa-cogs"></i></button>
                                                            <div class="dropdown-menu" role="menu">
                                                                <a class="dropdown-item"
                                                                    href="{{ route('treatment.show', ['id' => base64_encode($item['id'])]) }}">Detail</a>
                                                                <div class="dropdown-divider"></div>
                                                                {!! Form::open([
                                                                    'route' => ['treatment.delete', base64_encode($item['id'])],
                                                                    'method' => 'DELETE',
                                                                    'id' => 'remove-' . md5($item['id']),
                                                                ]) !!}
                                                                <a class="dropdown-item" href="javascript:void(0)"
                                                                    onclick="deleteData('{{ md5($item['id']) }}')">Hapus</a>
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
                        @endif
                        <div class="tab-pane {{ isset($data['id']) ? 'active' : '' }}" id="tab2">
                            <form action="{{ route('treatment.save', isset($data) ? base64_encode($data['id']) : '') }}"
                                method="POST" enctype="multipart/form-data" id="form-data">
                                @csrf
                                @if (isset($data))
                                    @method('PUT')
                                @endif
                                <div class="form-group row">
                                    <label for="patient_id" class="col-sm-3 col-form-label">Nama Pasien<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        @if (isset($data['id']) && isset($data['patient_id']))
                                            @php
                                                $patientName = $patients[$data['patient_id']] ?? null;
                                            @endphp
                                            <input type="hidden" name="patient_id" value="{{ $data['patient_id'] }}">
                                            <input type="text" name="full_name" id="patient_id" class="form-control"
                                                value="{{ $patientName }}" disabled>
                                        @else
                                            <select name="patient_id" id="patient_id" class="form-control select2"
                                                style="width: 100%;">
                                                <option value="">Masukan Nama Pasien</option>
                                                @foreach ($patients as $key => $value)
                                                    <option value="{{ $key }}"
                                                        {{ isset($data) && $data['patient_id'] == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                        <span id="error-patient_id" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="treatment_type_id" class="col-sm-3 col-form-label">Jenis Pengobatan<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <select name="treatment_type_id" id="treatment_type_id" class="form-control select2"
                                            style="width: 100%;">
                                            <option value="">Pilih Jenis Pengobatan</option>
                                            @foreach ($trtypes as $key => $item)
                                                <option value="{{ $item['id'] }}"
                                                    {{ isset($data) && $data['treatment_type_id'] == $item['id'] ? 'selected' : '' }}>
                                                    @php
                                                        $treatment_type =
                                                            $item['treatment_type'] . ' ' . $item['duration'];
                                                    @endphp
                                                    {{ $treatment_type }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <span id="error-treatment_type_id" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="diagnosis_date" class="col-sm-3 col-form-label">Tanggal
                                        Diagnosis<small class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control datetimepicker-input"
                                            data-target="#reservationdate" data-toggle="datetimepicker"
                                            name="diagnosis_date" id="diagnosis_date"
                                            placeholder="Masukan Tanggal Diagnosis"
                                            value="{{ isset($data) && $data['diagnosis_date'] ? \Carbon\Carbon::parse($data['diagnosis_date'])->format('d/m/Y') : '' }}"
                                            autocomplete="off">
                                        <span id="error-diagnosis_date" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="start_date" class="col-sm-3 col-form-label">Tgl. Mulai Minum Obat<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control datetimepicker-input"
                                            data-target="#reservationdate" data-toggle="datetimepicker" name="start_date"
                                            id="start_date" placeholder="Masukan Tgl. Mulai Minum Obat"
                                            value="{{ isset($data) && $data['start_date'] ? \Carbon\Carbon::parse($data['start_date'])->format('d/m/Y') : '' }}"
                                            autocomplete="off">
                                        <span id="error-start_date" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="medication_time" class="col-sm-3 col-form-label">Jadwal Minum Obat<small
                                            class="text-danger">*</small></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control timepicker-input"
                                            data-target="#timepicker" data-toggle="datetimepicker" name="medication_time"
                                            id="medication_time" placeholder="Masukan Jadwal Minum Obat"
                                            value="{{ isset($data) && $data['medication_time'] ? \Carbon\Carbon::parse($data['medication_time'])->format('H:i') : '' }}"
                                            autocomplete="off">
                                        <span id="error-medication_time" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="prescription" class="col-sm-3 col-form-label">Resep Obat</label>
                                    <div class="col-sm-9" id="prescription-container">
                                        @if (isset($data) && ($prescriptions = json_decode($data['prescription'], true)))
                                            @foreach ($prescriptions as $key => $item)
                                                <div class="input-group {{ $key > 0 ? 'mt-2' : '' }}">
                                                    <input type="text" class="form-control" name="prescription[]"
                                                        placeholder="Masukan Resep Obat" value="{{ $item }}"
                                                        autocomplete="off">
                                                    <span class="input-group-append">
                                                        <button type="button" class="btn btn-danger btn-flat"
                                                            onclick="removePrescription(this);">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            @endforeach
                                        @endif
                                        <div
                                            class="input-group {{ isset($data) && ($prescriptions = json_decode($data['prescription'], true)) ? 'mt-2' : '' }}">
                                            <input type="text" class="form-control" name="prescription[]"
                                                placeholder="Masukan Resep Obat" autocomplete="off">
                                            <span class="input-group-append">
                                                <button type="button" class="btn btn-success btn-flat"
                                                    onclick="addPrescription();">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </span>
                                        </div>
                                        <span id="error-prescription" class="error invalid-feedback"></span>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="offset-sm-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary btn-sm"><i
                                                class="fas fa-save mr-1"></i>
                                            Simpan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <script>
                        function addPrescription() {
                            var isValid = true;

                            $('input[name="prescription[]"]').each(function() {
                                if ($(this).val().trim() === '') {
                                    isValid = false;
                                    $(this).addClass('is-invalid');
                                } else {
                                    $(this).removeClass('is-invalid');
                                }
                            });

                            if (!isValid) {
                                $('#error-prescription').text('Resep Obat harus diisi').show();
                            } else {
                                $('#error-prescription').text('').hide();

                                var newInput = `
                                    <div class="input-group mt-2">
                                        <input type="text" class="form-control" name="prescription[]" placeholder="Masukan Resep Obat" autocomplete="off">
                                        <span class="input-group-append">
                                            <button type="button" class="btn btn-danger btn-flat" onclick="removePrescription(this);"><i class="fas fa-minus"></i></button>
                                        </span>
                                    </div>
                                `;

                                $('#prescription-container').append(newInput);
                            }
                        }

                        function removePrescription(button) {
                            $(button).closest('.input-group').remove();

                            if ($('input[name="prescription[]"]').val().trim() !== '') {
                                $('#error-prescription').text('').hide();
                            }
                        }
                    </script>
                @endif
            </div>
        </div>
    </div>
@endsection
