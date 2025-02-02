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
                            'Jadwal Minum Obat' => \Carbon\Carbon::parse($data['medication_time'])->format('H:i'),
                        ];
                    @endphp
                    @foreach ($infoPengobatan as $key => $value)
                        <li class="list-group-item">
                            <span>{{ $key }}</span> <span class="float-right">{{ $value }}</span>
                        </li>
                    @endforeach
                    @if ($prescriptions = json_decode($data['prescription'], true))
                        <li class="list-group-item">
                            <span>Resep Obat</span>
                        </li>
                        @foreach ($prescriptions as $index => $prescription)
                            <li class="list-group-item p-2">
                                <small>
                                    @if (count($prescriptions) > 1)
                                        {{ $index + 1 }}.
                                    @endif
                                    {{ $prescription }}
                                </small>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Riwayat Minum Obat</h3>
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
                                @php
                                    $treatment = \App\Models\MedicationRecord::getRecordByDate(
                                        $data['id'],
                                        $date,
                                    );
                                    $isToday = \Carbon\Carbon::parse($date)->isToday();
                                    $statusBadge = $treatment
                                        ? 'success|SUDAH MINUM OBAT'
                                        : ($date > date('Y-m-d')
                                            ? 'warning|BELUM MINUM OBAT'
                                            : 'danger|TIDAK MINUM OBAT');
                                    [$badgeClass, $badgeText] = explode('|', $statusBadge);
                                @endphp
                                <tr style="{{ $isToday ? 'background-color: #ececf6;' : '' }}">
                                    <td style="text-align: center;">{{ $key + 1 }}</td>
                                    <td>{{ \App\Helpers\DateHelper::convertDate($date) }}</td>
                                    <td>{{ $treatment ? \Carbon\Carbon::parse($treatment->taken_at)->format('H:i:s') : '-' }}
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $badgeClass }} p-2">{{ $badgeText }}</span>
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
