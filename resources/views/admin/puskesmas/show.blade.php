@extends('admin.layouts.app')

@section('content')
<div class="row">
    <!-- Left Column: Puskesmas Info Card -->
    <div class="col-md-4">
        <div class="card card-teal card-outline">
            <div class="card-body box-profile text-center">
                <div class="mb-3">
                    <span class="rounded-circle p-3 bg-teal text-white d-inline-block shadow-sm" style="width: 80px; height: 80px; line-height: 50px;">
                        <i class="fas fa-clinic-medical fa-2x"></i>
                    </span>
                </div>
                <h3 class="profile-username font-weight-bold text-dark">{{ $pkm->name }}</h3>
                <p class="text-muted mb-2">
                    <span class="badge badge-light border px-2 py-1 font-weight-bold text-teal">
                        <i class="fas fa-barcode mr-1"></i>{{ $pkm->code ?? ('PKM-' . str_pad($pkm->id, 3, '0', STR_PAD_LEFT)) }}
                    </span>
                </p>

                <ul class="list-group list-group-unbordered mb-3 text-left">
                    <li class="list-group-item">
                        <b>Wilayah Kecamatan</b> <span class="float-right text-dark font-weight-bold">{{ $pkm->subdistrict->name ?? '-' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Kabupaten / Kota</b> <span class="float-right text-muted">{{ $pkm->subdistrict->district->name ?? '-' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Provinsi</b> <span class="float-right text-muted">{{ $pkm->subdistrict->district->province->name ?? '-' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Alamat Lengkap</b> 
                        <div class="text-muted small mt-1 font-italic">{{ $pkm->address ?: 'Belum diisi' }}</div>
                    </li>
                </ul>

                <div class="row text-center mb-3">
                    <div class="col-4 border-right">
                        <div class="description-block">
                            <h5 class="description-header text-success">{{ $pkm->officers_count }}</h5>
                            <span class="description-text small text-muted">Petugas</span>
                        </div>
                    </div>
                    <div class="col-4 border-right">
                        <div class="description-block">
                            <h5 class="description-header text-warning">{{ $pkm->patients_count }}</h5>
                            <span class="description-text small text-muted">Pasien</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="description-block">
                            <h5 class="description-header text-primary">{{ $pkm->screenings_count }}</h5>
                            <span class="description-text small text-muted">Skrining</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.puskesmas.edit', $pkm->encrypted_id) }}" class="btn btn-warning btn-block mr-1">
                        <i class="fas fa-pencil-alt mr-1"></i> Edit Data
                    </a>
                    <a href="{{ route('admin.puskesmas.index') }}" class="btn btn-default btn-block ml-1">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Tabs (Petugas, Pasien, Skrining) -->
    <div class="col-md-8">
        <div class="card card-outline card-teal card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="pkmTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="tab-officers" data-toggle="pill" href="#officers" role="tab">
                            <i class="fas fa-user-nurse mr-1 text-success"></i> Petugas TB & PJTB ({{ $pkm->officers->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-patients" data-toggle="pill" href="#patients" role="tab">
                            <i class="fas fa-procedures mr-1 text-warning"></i> Pasien Terdaftar ({{ $pkm->patients->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="tab-screenings" data-toggle="pill" href="#screenings" role="tab">
                            <i class="fas fa-clipboard-check mr-1 text-info"></i> Skrining Wilayah ({{ $pkm->screenings->count() }})
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="pkmTabsContent">
                    
                    <!-- TAB 1: PETUGAS -->
                    <div class="tab-pane fade show active" id="officers" role="tabpanel">
                        <div class="table-responsive">
                            <table id="pkmOfficersTable" class="table table-bordered table-hover data-table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th>Nama Petugas</th>
                                        <th>Role / Jabatan</th>
                                        <th>Kontak / No HP</th>
                                        <th>Tanggal Daftar</th>
                                        <th style="width: 80px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pkm->officers as $officer)
                                    <tr>
                                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $officer->user->name ?? 'User N/A' }}</div>
                                            <small class="text-muted"><i class="fas fa-id-card mr-1"></i>NIK: {{ $officer->user->nik ?? '-' }}</small>
                                        </td>
                                        <td>
                                            @if($officer->officer_type_id == 3)
                                                <span class="badge badge-success px-2 py-1">PJ TB Puskesmas</span>
                                            @elseif($officer->officer_type_id == 4)
                                                <span class="badge badge-info px-2 py-1">Kader TB</span>
                                            @else
                                                <span class="badge badge-secondary px-2 py-1">Petugas Faskes</span>
                                            @endif
                                        </td>
                                        <td>
                                            <i class="fas fa-phone-alt text-muted mr-1"></i>{{ $officer->user->phone_number ?? '-' }}
                                        </td>
                                        <td data-order="{{ $officer->created_at ? $officer->created_at->timestamp : 0 }}">
                                            <small class="text-muted">{{ $officer->created_at ? $officer->created_at->format('d/m/Y') : '-' }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($officer->user_id)
                                            <a href="{{ route('admin.users.show', optional($officer->user)->encrypted_id ?? encrypt_id($officer->user_id)) }}" class="btn btn-sm btn-info" title="Lihat Profil Petugas">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: PASIEN -->
                    <div class="tab-pane fade" id="patients" role="tabpanel">
                        <div class="table-responsive">
                            <table id="pkmPatientsTable" class="table table-bordered table-hover data-table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th>Nama Pasien</th>
                                        <th>NIK</th>
                                        <th>Usia / JK</th>
                                        <th>Desa/Kelurahan</th>
                                        <th style="width: 80px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pkm->patients as $pat)
                                    <tr>
                                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $pat->user->name ?? $pat->name ?? 'Pasien #' . $pat->id }}</div>
                                            <small class="text-muted">{{ $pat->user->phone_number ?? '-' }}</small>
                                        </td>
                                        <td>
                                            <code>{{ $pat->nik ?? ($pat->user->nik ?? '-') }}</code>
                                        </td>
                                        <td data-order="{{ $pat->age ?? 0 }}">
                                            {{ $pat->age ?? '-' }} Thn / 
                                            @if(($pat->gender ?? ($pat->user->gender ?? '')) == 'male' || ($pat->gender ?? '') == 'L')
                                                <span class="badge badge-info">L</span>
                                            @else
                                                <span class="badge badge-pink" style="background-color: #e83e8c; color: white;">P</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $pat->village->name ?? ($pat->subdistrict->name ?? '-') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.patients.show', $pat->encrypted_id) }}" class="btn btn-sm btn-info" title="Lihat Rekam Medis Pasien">
                                                <i class="fas fa-folder-open"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: SKRINING -->
                    <div class="tab-pane fade" id="screenings" role="tabpanel">
                        <div class="table-responsive">
                            <table id="pkmScreeningsTable" class="table table-bordered table-hover data-table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th>Kode Skrining</th>
                                        <th>Nama Peserta</th>
                                        <th>Skor & Kategori Risiko</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th style="width: 80px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pkm->screenings as $scr)
                                    <tr>
                                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="badge badge-light border font-weight-bold text-primary">{{ $scr->code }}</span>
                                        </td>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $scr->person_name ?? $scr->name }}</div>
                                            <small class="text-muted">{{ $scr->age }} Thn, {{ $scr->gender == 'L' || $scr->gender == 'male' ? 'L' : 'P' }}</small>
                                        </td>
                                        <td data-order="{{ $scr->total_score }}">
                                            @if($scr->risk_level == 'Risiko Tinggi' || $scr->risk_level == 'tinggi')
                                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>Risiko Tinggi (Skor {{ $scr->total_score }})</span>
                                            @elseif($scr->risk_level == 'Risiko Sedang' || $scr->risk_level == 'sedang')
                                                <span class="badge badge-warning px-2 py-1"><i class="fas fa-exclamation-circle mr-1"></i>Risiko Sedang (Skor {{ $scr->total_score }})</span>
                                            @else
                                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Risiko Rendah (Skor {{ $scr->total_score }})</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($scr->status == 'Selesai' || $scr->status == 'selesai')
                                                <span class="badge badge-success">Selesai</span>
                                            @elseif($scr->status == 'Dalam Pemantauan' || $scr->status == 'proses')
                                                <span class="badge badge-warning">Dalam Pemantauan</span>
                                            @else
                                                <span class="badge badge-danger">Perlu Tindak Lanjut</span>
                                            @endif
                                        </td>
                                        <td data-order="{{ $scr->screened_at ? $scr->screened_at->timestamp : ($scr->created_at ? $scr->created_at->timestamp : 0) }}">
                                            <small class="text-muted">{{ $scr->screened_at ? $scr->screened_at->format('d/m/Y') : ($scr->created_at ? $scr->created_at->format('d/m/Y') : '-') }}</small>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.screenings.show', $scr->encrypted_id) }}" class="btn btn-sm btn-info" title="Lihat Detail Skrining">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
