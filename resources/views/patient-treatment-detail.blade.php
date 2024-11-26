@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">{{ 'Informasi ' . $title }}</h3>
                <div class="card-tools">
                    <a href="{{ route('treatments') }}" class="btn btn-outline-primary btn-sm"
                        title="{{ 'Daftar ' . $title }}">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="{{ route('treatment.edit', ['id' => base64_encode($data['id'])]) }}"
                        class="btn btn-primary btn-sm" title="Edit {{ $title }}">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item" style="padding-top: 0px; border-top: 0px;">
                        <span>Nama Pasien</span> <a
                            href="{{ route('user.show', ['id' => base64_encode($data['user_id'])]) }}" class="float-right"
                            title="Lihat Data Pasien">{{ $data['full_name'] . ' (' . $data['username'] . ')' }}</a>
                    </li>
                    @php
                        $infoPengobatan = [
                            'Jenis Pengobatan' => $data['treatment_type'],
                            'Tanggal Diagnosa' => \App\Helpers\DateHelper::convertDate($data['diagnosis_date']),
                            'Tgl. Mulai Pengobatan' => \App\Helpers\DateHelper::convertDate($data['start_date']),
                            'Jam Minum Obat' => \Carbon\Carbon::parse($data['medication_time'])->format('H:i'),
                        ];
                    @endphp
                    @foreach ($infoPengobatan as $key => $value)
                        <li class="list-group-item">
                            <span>{{ $key }}</span> <span class="float-right">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header py-2">
                <h3 class="card-title pt-1">Riwayat Minum Obat</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 5%; text-align: center;">No</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dateRange as $key => $date)
                                <tr style="{{ \Carbon\Carbon::parse($date)->isToday() ? 'background-color: #ececf6;' : '' }}">
                                    <td style="text-align: center;">{{ $key + 1 }}</td>
                                    <td>{{ \App\Helpers\DateHelper::convertDate($date) }}</td>
                                    <td>-</td>
                                    <td>-</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
