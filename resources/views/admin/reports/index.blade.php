@extends('admin.layouts.app')

@section('content')
<!-- Navigation Tabs for Report Types -->
<div class="row mb-3">
    <div class="col-12">
        <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
            <a href="{{ route('admin.reports.index', ['type' => 'screening']) }}" class="btn {{ $type == 'screening' ? 'btn-primary active' : 'btn-outline-primary' }} flex-fill font-weight-bold">
                <i class="fas fa-clipboard-check mr-1"></i> Laporan Skrining TB
            </a>
            <a href="{{ route('admin.reports.index', ['type' => 'patient']) }}" class="btn {{ $type == 'patient' ? 'btn-primary active' : 'btn-outline-primary' }} flex-fill font-weight-bold">
                <i class="fas fa-procedures mr-1"></i> Laporan Register Pasien
            </a>
            <a href="{{ route('admin.reports.index', ['type' => 'regional']) }}" class="btn {{ $type == 'regional' ? 'btn-primary active' : 'btn-outline-primary' }} flex-fill font-weight-bold">
                <i class="fas fa-map-marked-alt mr-1"></i> Laporan Agregat Wilayah
            </a>
            <a href="{{ route('admin.reports.index', ['type' => 'user']) }}" class="btn {{ $type == 'user' ? 'btn-primary active' : 'btn-outline-primary' }} flex-fill font-weight-bold">
                <i class="fas fa-users mr-1"></i> Laporan Pengguna Terdaftar
            </a>
        </div>
    </div>
</div>

<!-- Filter Card -->
<div class="card card-primary card-outline shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-filter mr-1 text-primary"></i> Parameter Filter Laporan
        </h3>
        <div class="card-tools">
            <a href="{{ route('admin.reports.print', request()->all()) }}" target="_blank" class="btn btn-sm btn-info mr-1">
                <i class="fas fa-print mr-1"></i> Cetak / Simpan PDF
            </a>
            <a href="{{ route('admin.reports.export', request()->all()) }}" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel mr-1"></i> Ekspor CSV / Excel
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="row">
            <input type="hidden" name="type" value="{{ $type }}">
            
            <div class="col-md-3 mb-2">
                <label class="small text-muted font-weight-bold">Periode Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control">
            </div>

            <div class="col-md-3 mb-2">
                <label class="small text-muted font-weight-bold">Periode Tanggal Akhir</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control">
            </div>

            <div class="col-md-3 mb-2">
                <label class="small text-muted font-weight-bold">Wilayah Puskesmas</label>
                <select name="puskesmas_id" class="form-control select2">
                    <option value="">-- Semua Puskesmas --</option>
                    @foreach($puskesmasList as $pkm)
                        <option value="{{ $pkm->id }}" {{ $puskesmasId == $pkm->id ? 'selected' : '' }}>
                            {{ $pkm->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if($type == 'screening')
            <div class="col-md-3 mb-2">
                <label class="small text-muted font-weight-bold">Tingkat Risiko</label>
                <select name="risk_level" class="form-control select2">
                    <option value="">-- Semua Kategori Risiko --</option>
                    <option value="tinggi" {{ $riskLevel == 'tinggi' ? 'selected' : '' }}>Risiko Tinggi</option>
                    <option value="sedang" {{ $riskLevel == 'sedang' ? 'selected' : '' }}>Risiko Sedang</option>
                    <option value="rendah" {{ $riskLevel == 'rendah' ? 'selected' : '' }}>Risiko Rendah</option>
                </select>
            </div>
            @endif

            <div class="col-12 mt-2 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary mr-2 px-4">
                    <i class="fas fa-search mr-1"></i> Terapkan Filter
                </button>
                <a href="{{ route('admin.reports.index', ['type' => $type]) }}" class="btn btn-default">
                    <i class="fas fa-undo mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Report Table Output -->
<div class="card card-outline card-navy shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-table mr-1 text-navy"></i> Hasil Data: 
            @if($type == 'screening') Skrining TB Gejala 
            @elseif($type == 'patient') Register Pasien TB 
            @elseif($type == 'regional') Agregat Sebaran Wilayah 
            @else Registrasi Pengguna @endif
        </h3>
        <div class="card-tools">
            <span class="badge badge-secondary p-2">
                Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            </span>
        </div>
    </div>
    
    <div class="card-body">
        @if($type == 'screening')
            <div class="table-responsive">
                <table id="reportScreeningsTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Kode Skrining</th>
                            <th>Nama Peserta</th>
                            <th>JK / Usia</th>
                            <th>Puskesmas / Wilayah</th>
                            <th class="text-center">Skor</th>
                            <th>Kategori Risiko</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $idx => $row)
                        <tr>
                            <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                            <td><span class="badge badge-light border text-primary font-weight-bold">{{ $row->code }}</span></td>
                            <td><strong>{{ $row->person_name ?? $row->name }}</strong><div class="small text-muted">NIK: {{ $row->nik ?? ($row->user->nik ?? '-') }}</div></td>
                            <td>{{ $row->gender == 'L' || $row->gender == 'male' ? 'L' : 'P' }} / {{ $row->age }} Thn</td>
                            <td>{{ $row->puskesmas->name ?? '-' }}<div class="small text-muted">{{ $row->subdistrict->name ?? '-' }}</div></td>
                            <td class="text-center" data-order="{{ $row->total_score }}"><span class="badge badge-light border">{{ $row->total_score }}</span></td>
                            <td>
                                @if(stripos($row->risk_level, 'tinggi') !== false)
                                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>Tinggi</span>
                                @elseif(stripos($row->risk_level, 'sedang') !== false)
                                    <span class="badge badge-warning px-2 py-1"><i class="fas fa-exclamation-circle mr-1"></i>Sedang</span>
                                @else
                                    <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Rendah</span>
                                @endif
                            </td>
                            <td><span class="badge badge-light border">{{ strtoupper($row->status) }}</span></td>
                            <td data-order="{{ $row->screened_at ? $row->screened_at->timestamp : ($row->created_at ? $row->created_at->timestamp : 0) }}">
                                <small class="text-muted">{{ $row->screened_at ? $row->screened_at->format('d/m/Y') : ($row->created_at ? $row->created_at->format('d/m/Y') : '-') }}</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @elseif($type == 'patient')
            <div class="table-responsive">
                <table id="reportPatientsTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>No Registrasi</th>
                            <th>Nama Pasien</th>
                            <th>NIK</th>
                            <th>JK / Usia</th>
                            <th>Faskes Pembina</th>
                            <th>Status Pengobatan</th>
                            <th>Tanggal Register</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $idx => $pat)
                        <tr>
                            <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                            <td><code>{{ $pat->patient_number ?? ('TB-' . str_pad($loop->iteration, 4, '0', STR_PAD_LEFT)) }}</code></td>
                            <td><strong>{{ $pat->user->name ?? $pat->name ?? 'Pasien' }}</strong></td>
                            <td>{{ $pat->nik ?? ($pat->user->nik ?? '-') }}</td>
                            <td data-order="{{ $pat->age ?? 0 }}">{{ ($pat->gender ?? ($pat->user->gender ?? '')) == 'male' || ($pat->gender ?? '') == 'L' ? 'L' : 'P' }} / {{ $pat->age ?? '-' }} Thn</td>
                            <td>{{ $pat->puskesmas->name ?? '-' }}</td>
                            <td>
                                <span class="badge badge-info px-2 py-1">{{ $pat->activeTreatment->treatment_status ?? 'Dalam Pemantauan' }}</span>
                            </td>
                            <td data-order="{{ $pat->created_at ? $pat->created_at->timestamp : 0 }}">
                                <small class="text-muted">{{ $pat->created_at ? $pat->created_at->format('d/m/Y') : '-' }}</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @elseif($type == 'regional')
            <div class="table-responsive">
                <table id="reportRegionsTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Nama Puskesmas</th>
                            <th>Kecamatan</th>
                            <th>Kabupaten / Kota</th>
                            <th class="text-center">Total Pasien</th>
                            <th class="text-center">Total Skrining</th>
                            <th class="text-center text-danger">Risiko Tinggi</th>
                            <th class="text-center text-warning">Risiko Sedang</th>
                            <th class="text-center text-success">Risiko Rendah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($regionalSummary as $idx => $pkm)
                        <tr>
                            <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                            <td><strong><i class="fas fa-clinic-medical text-teal mr-1"></i>{{ $pkm->name }}</strong></td>
                            <td>{{ $pkm->subdistrict->name ?? '-' }}</td>
                            <td>{{ $pkm->subdistrict->district->name ?? '-' }}</td>
                            <td class="text-center font-weight-bold" data-order="{{ $pkm->patients_count }}">{{ $pkm->patients_count }}</td>
                            <td class="text-center font-weight-bold" data-order="{{ $pkm->screenings_count }}">{{ $pkm->screenings_count }}</td>
                            <td class="text-center" data-order="{{ $pkm->high_risk_count }}"><span class="badge badge-danger px-2 py-1">{{ $pkm->high_risk_count }}</span></td>
                            <td class="text-center" data-order="{{ $pkm->medium_risk_count }}"><span class="badge badge-warning px-2 py-1">{{ $pkm->medium_risk_count }}</span></td>
                            <td class="text-center" data-order="{{ $pkm->low_risk_count }}"><span class="badge badge-success px-2 py-1">{{ $pkm->low_risk_count }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <!-- User Report -->
            <div class="table-responsive">
                <table id="reportUsersTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Nama Pengguna</th>
                            <th>Username / Email</th>
                            <th>No HP</th>
                            <th>Role / Tipe</th>
                            <th>Tanggal Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $idx => $u)
                        <tr>
                            <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                            <td><strong>{{ $u->name }}</strong></td>
                            <td>{{ $u->username }}<div class="small text-muted">{{ $u->email }}</div></td>
                            <td>{{ $u->phone_number ?? '-' }}</td>
                            <td><span class="badge badge-primary px-2 py-1">{{ $u->role->name ?? 'User' }}</span></td>
                            <td data-order="{{ $u->created_at ? $u->created_at->timestamp : 0 }}">
                                <small class="text-muted">{{ $u->created_at ? $u->created_at->format('d/m/Y, H:i') : '-' }}</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
