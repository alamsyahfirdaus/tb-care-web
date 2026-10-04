@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.patients.index') }}">Pasien TB</a></li>
    <li class="breadcrumb-item active">Detail: {{ optional($patient->user)->name }}</li>
@endsection

@section('content')
    <!-- Top Summary Banner -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-danger shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap align-items-center">
                        <img class="img-circle elevation-2 mr-3" 
                             src="{{ optional($patient->user)->photo ? asset('upload_images/' . $patient->user->photo) : asset('assets/img/profile.png') }}"
                             style="width: 75px; height: 75px; object-fit: cover;" alt="Avatar">
                        <div class="mr-auto my-1">
                            <h4 class="font-weight-bold text-dark mb-0">{{ optional($patient->user)->name ?? 'Pasien #' . $patient->id }}</h4>
                            <div class="text-muted">
                                <span><i class="fas fa-id-card mr-1"></i> NIK: <strong>{{ $patient->nik ?? '-' }}</strong></span> &bull; 
                                <span><i class="fas fa-venus-mars mr-1"></i> {{ optional($patient->user)->gender == 'L' ? 'Laki-laki' : 'Perempuan' }} ({{ $age }})</span> &bull;
                                <span><i class="fas fa-hospital mr-1"></i> Puskesmas: <strong>{{ optional($patient->puskesmas)->name ?? '-' }}</strong></span>
                            </div>
                        </div>
                        <div class="my-1">
                            @if($activeTreatment)
                                @if($activeTreatment->treatment_status == 'Berjalan')
                                    <span class="badge badge-primary px-3 py-2 text-md font-weight-bold"><i class="fas fa-pills mr-1"></i> Pengobatan Berjalan</span>
                                @elseif($activeTreatment->treatment_status == 'Selesai')
                                    <span class="badge badge-success px-3 py-2 text-md font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Selesai Pengobatan</span>
                                @else
                                    <span class="badge badge-secondary px-3 py-2 text-md font-weight-bold">{{ $activeTreatment->treatment_status }}</span>
                                @endif
                            @else
                                <span class="badge badge-warning px-3 py-2 text-md font-weight-bold">Belum Ada Pengobatan</span>
                            @endif
                            <a href="{{ route('admin.patients.edit', $patient->encrypted_id) }}" class="btn btn-sm btn-outline-secondary font-weight-bold ml-2">
                                <i class="fas fa-edit mr-1"></i> Edit Data
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Detail Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card card-primary card-outline card-outline-tabs shadow-sm">
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs" id="patient-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-bold" id="tab-profil-tab" data-toggle="pill" href="#tab-profil" role="tab">
                                <i class="fas fa-user-circle mr-1 text-primary"></i> Identitas & Wilayah
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-pengobatan-tab" data-toggle="pill" href="#tab-pengobatan" role="tab">
                                <i class="fas fa-pills mr-1 text-success"></i> Riwayat Pengobatan ({{ $patient->treatments->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-minum-obat-tab" data-toggle="pill" href="#tab-minum-obat" role="tab">
                                <i class="fas fa-camera mr-1 text-info"></i> Bukti Minum Obat ({{ $patient->treatments->flatMap->medicationRecords->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-pemeriksaan-tab" data-toggle="pill" href="#tab-pemeriksaan" role="tab">
                                <i class="fas fa-microscope mr-1 text-cyan"></i> Pemeriksaan Lab ({{ $patient->examinations->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-skrining-tab" data-toggle="pill" href="#tab-skrining" role="tab">
                                <i class="fas fa-stethoscope mr-1 text-warning"></i> Riwayat Skrining ({{ $patient->screenings->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="tab-kontak-tab" data-toggle="pill" href="#tab-kontak" role="tab">
                                <i class="fas fa-people-arrows mr-1 text-teal"></i> Kontak Erat ({{ $patient->closeContacts->count() }})
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="patient-tabs-content">
                        
                        <!-- TAB 1: IDENTITAS & WILAYAH & FISIK -->
                        <div class="tab-pane fade show active" id="tab-profil" role="tabpanel">
                            <div class="row">
                                <!-- Identitas & Kontak -->
                                <div class="col-md-4">
                                    <div class="card card-light shadow-none border">
                                        <div class="card-header bg-light">
                                            <h6 class="card-title font-weight-bold mb-0"><i class="fas fa-id-badge mr-1 text-primary"></i> [1] Data Identitas & Akun</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted" style="width: 40%;">Nama Lengkap:</td>
                                                    <td class="font-weight-bold">{{ optional($patient->user)->name ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">NIK:</td>
                                                    <td class="font-weight-bold text-dark">{{ $patient->nik ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Username:</td>
                                                    <td>@ {{ optional($patient->user)->username ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Jenis Kelamin:</td>
                                                    <td>{{ optional($patient->user)->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">TTL:</td>
                                                    <td>
                                                        {{ optional($patient->user)->place_of_birth ?? '-' }}, 
                                                        {{ optional($patient->user)->date_of_birth ? Carbon\Carbon::parse($patient->user->date_of_birth)->format('d F Y') : '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Umur:</td>
                                                    <td class="font-weight-bold">{{ $age }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">No. Telepon / WA:</td>
                                                    <td class="font-weight-bold text-success">{{ optional($patient->user)->phone ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Email:</td>
                                                    <td>{{ optional($patient->user)->email ?? '-' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Alamat & Faskes -->
                                <div class="col-md-4">
                                    <div class="card card-light shadow-none border">
                                        <div class="card-header bg-light">
                                            <h6 class="card-title font-weight-bold mb-0"><i class="fas fa-map-marker-alt mr-1 text-danger"></i> [2] Alamat & Fasilitas Kesehatan</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted" style="width: 40%;">Puskesmas:</td>
                                                    <td class="font-weight-bold text-primary">{{ optional($patient->puskesmas)->name ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Provinsi:</td>
                                                    <td>{{ optional(optional($patient->subdistrict)->district)->province->name ?? 'Jawa Barat' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Kabupaten / Kota:</td>
                                                    <td>{{ optional(optional($patient->subdistrict)->district)->name ?? 'Kota Tasikmalaya' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Kecamatan:</td>
                                                    <td class="font-weight-bold">{{ optional($patient->subdistrict)->name ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Kelurahan / Desa:</td>
                                                    <td class="font-weight-bold">{{ optional($patient->village)->name ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">RT / RW:</td>
                                                    <td>RT {{ $patient->rt ?? '-' }} / RW {{ $patient->rw ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Alamat Spesifik:</td>
                                                    <td>{{ $patient->address ?? '-' }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Fisik & Klinis -->
                                <div class="col-md-4">
                                    <div class="card card-light shadow-none border">
                                        <div class="card-header bg-light">
                                            <h6 class="card-title font-weight-bold mb-0"><i class="fas fa-heartbeat mr-1 text-info"></i> [3] Kondisi Fisik & Status Klinis</h6>
                                        </div>
                                        <div class="card-body p-3">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr>
                                                    <td class="text-muted" style="width: 45%;">Tinggi Badan:</td>
                                                    <td class="font-weight-bold">{{ $patient->height ? $patient->height . ' cm' : '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Berat Badan:</td>
                                                    <td class="font-weight-bold">{{ $patient->weight ? $patient->weight . ' kg' : '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Indeks Massa Tubuh:</td>
                                                    <td>
                                                        @if($bmi)
                                                            <span class="font-weight-bold text-dark">{{ $bmi }} kg/m²</span>
                                                            <div class="text-xs text-muted font-weight-bold">({{ $bmiStatus }})</div>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Golongan Darah:</td>
                                                    <td class="font-weight-bold text-danger">{{ $patient->blood_type ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Pekerjaan:</td>
                                                    <td class="font-weight-bold">{{ $patient->occupation ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Tgl Diagnosis TB:</td>
                                                    <td class="font-weight-bold">
                                                        {{ $patient->diagnosis_date ? Carbon\Carbon::parse($patient->diagnosis_date)->format('d F Y') : '-' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Mulai Pengobatan:</td>
                                                    <td class="font-weight-bold text-success">
                                                        {{ $patient->treatment_start_date ? Carbon\Carbon::parse($patient->treatment_start_date)->format('d F Y') : '-' }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: RIWAYAT PENGOBATAN -->
                        <div class="tab-pane fade" id="tab-pengobatan" role="tabpanel">
                            <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-pills mr-1 text-success"></i> Data Program Pengobatan Pasien</h6>
                            @forelse($patient->treatments as $tr)
                                <div class="card card-outline card-success shadow-none border mb-4">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="font-weight-bold mb-0">
                                            {{ optional($tr->treatmentType)->treatment_type ?? 'Kategori Pengobatan' }} 
                                            <span class="badge badge-light border ml-2">ID: #{{ $tr->id }}</span>
                                        </h6>
                                        <div>
                                            @if($tr->treatment_status == 'Berjalan')
                                                <span class="badge badge-primary px-3 py-1 font-weight-bold">Sedang Berjalan</span>
                                            @elseif($tr->treatment_status == 'Selesai')
                                                <span class="badge badge-success px-3 py-1 font-weight-bold">Selesai / Sembuh</span>
                                            @else
                                                <span class="badge badge-danger px-3 py-1 font-weight-bold">{{ $tr->treatment_status }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <small class="text-muted d-block">Tanggal Mulai:</small>
                                                <strong>{{ $tr->start_date ? Carbon\Carbon::parse($tr->start_date)->format('d F Y') : '-' }}</strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted d-block">Estimasi Selesai:</small>
                                                <strong>{{ $tr->end_date ? Carbon\Carbon::parse($tr->end_date)->format('d F Y') : '-' }}</strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted d-block">Target Durasi:</small>
                                                <strong>{{ $tr->treatment_days ?? '180' }} Hari</strong>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted d-block">Jadwal Minum Obat:</small>
                                                <strong>{{ $tr->medication_time ? Carbon\Carbon::parse($tr->medication_time)->format('H:i') : '08:00' }} WIB</strong>
                                            </div>
                                        </div>

                                        @if($tr->prescription)
                                            <div class="mt-3 p-2 bg-light rounded border text-sm">
                                                <strong class="text-dark"><i class="fas fa-prescription mr-1"></i> Resep & Dosis Obat:</strong>
                                                <div class="text-muted mt-1">{{ $tr->prescription }}</div>
                                            </div>
                                        @endif

                                        <!-- Visits Subtable -->
                                        <div class="mt-3">
                                            <h6 class="font-weight-bold text-sm text-dark mb-2"><i class="fas fa-calendar-check mr-1 text-primary"></i> Jadwal Kunjungan & Kontrol Puskesmas:</h6>
                                            @if($tr->visits->count() > 0)
                                                <div class="table-responsive">
                                                    <table id="patientVisitsTable-{{ $loop->iteration }}" class="table table-sm table-bordered table-hover data-table">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th>Tanggal Kunjungan</th>
                                                                <th>Jam</th>
                                                                <th>Status Kunjungan</th>
                                                                <th>Catatan Petugas</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($tr->visits as $v)
                                                                <tr>
                                                                    <td data-order="{{ Carbon\Carbon::parse($v->visit_date)->timestamp }}">{{ Carbon\Carbon::parse($v->visit_date)->format('d/m/Y') }}</td>
                                                                    <td>{{ $v->visit_time ? Carbon\Carbon::parse($v->visit_time)->format('H:i') : '-' }}</td>
                                                                    <td>
                                                                        @if($v->visit_status == 'Hadir')
                                                                            <span class="badge badge-success font-weight-bold">Hadir</span>
                                                                        @elseif($v->visit_status == 'Tidak Hadir')
                                                                            <span class="badge badge-danger font-weight-bold">Tidak Hadir</span>
                                                                        @else
                                                                            <span class="badge badge-warning font-weight-bold text-dark">Terjadwal</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-xs">{{ $v->notes ?? '-' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <span class="text-xs text-muted">Belum ada jadwal kunjungan kontrol khusus yang dicatat.</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> Belum ada data riwayat pengobatan untuk pasien ini.
                                </div>
                            @endforelse
                        </div>

                        <!-- TAB 3: BUKTI MINUM OBAT -->
                        <div class="tab-pane fade" id="tab-minum-obat" role="tabpanel">
                            <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-camera mr-1 text-info"></i> Log Verifikasi Foto Minum Obat Harian</h6>
                            @php
                                $medRecords = $patient->treatments->flatMap->medicationRecords;
                            @endphp
                            @if($medRecords->count() > 0)
                                <div class="table-responsive">
                                    <table id="patientMedicationLogsTable" class="table table-sm table-bordered table-hover data-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th style="width: 50px;">No</th>
                                                <th>Waktu Minum Obat</th>
                                                <th>Bukti Foto</th>
                                                <th class="text-center">Kepatuhan Waktu</th>
                                                <th class="text-center">Status Verifikasi</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($medRecords as $idx => $rec)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td data-order="{{ $rec->created_at->timestamp }}">
                                                        <span class="font-weight-bold">{{ $rec->created_at->format('d/m/Y') }}</span>
                                                        <div class="text-xs text-muted">{{ $rec->created_at->format('H:i:s') }} WIB</div>
                                                    </td>
                                                    <td>
                                                        @if($rec->photo)
                                                            <a href="{{ asset('upload_images/' . $rec->photo) }}" target="_blank" class="btn btn-xs btn-outline-info font-weight-bold">
                                                                <i class="fas fa-image mr-1"></i> Lihat Foto Bukti
                                                            </a>
                                                        @else
                                                            <span class="text-xs text-muted"><i class="fas fa-times mr-1"></i> Tanpa Foto</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($rec->late == 0)
                                                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Tepat Waktu</span>
                                                        @else
                                                            <span class="badge badge-warning text-dark"><i class="fas fa-clock mr-1"></i> Terlambat</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($rec->is_verified)
                                                            <span class="badge badge-success font-weight-bold"><i class="fas fa-check-double mr-1"></i> Terverifikasi</span>
                                                        @else
                                                            <span class="badge badge-secondary font-weight-bold">Menunggu Verifikasi</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-xs text-muted">{{ $rec->notes ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> Belum ada rekaman bukti minum obat untuk pasien ini.
                                </div>
                            @endif
                        </div>

                        <!-- TAB 4: PEMERIKSAAN LAB -->
                        <div class="tab-pane fade" id="tab-pemeriksaan" role="tabpanel">
                            <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-microscope mr-1 text-cyan"></i> Riwayat Pemeriksaan Klinis & Laboratorium</h6>
                            @if($patient->examinations->count() > 0)
                                <div class="table-responsive">
                                    <table id="patientExaminationsTable" class="table table-sm table-bordered table-hover data-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Kode Pemeriksaan</th>
                                                <th>Tanggal</th>
                                                <th>Jenis Pemeriksaan</th>
                                                <th>Hasil Laboratorium</th>
                                                <th>Diagnosis Medis</th>
                                                <th>Status</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->examinations as $exam)
                                                <tr>
                                                    <td class="font-weight-bold text-primary">{{ $exam->examination_code }}</td>
                                                    <td data-order="{{ Carbon\Carbon::parse($exam->examination_date)->timestamp }}">{{ Carbon\Carbon::parse($exam->examination_date)->format('d/m/Y') }}</td>
                                                    <td><span class="badge badge-info">{{ $exam->examination_type }}</span></td>
                                                    <td>
                                                        @if(in_array($exam->result, ['Positif', 'Resisten Rifampisin', 'Tersangka / Lesi Aktif']))
                                                            <span class="badge badge-danger font-weight-bold">{{ $exam->result }}</span>
                                                        @else
                                                            <span class="badge badge-success font-weight-bold">{{ $exam->result }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="font-weight-bold text-dark">{{ $exam->diagnosis ?? '-' }}</td>
                                                    <td>
                                                        <span class="badge badge-light border">{{ $exam->status }}</span>
                                                    </td>
                                                    <td class="text-xs text-muted">{{ $exam->laboratory_notes ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> Belum ada data pemeriksaan laboratorium yang tercatat untuk pasien ini.
                                </div>
                            @endif
                        </div>

                        <!-- TAB 5: RIWAYAT SKRINING -->
                        <div class="tab-pane fade" id="tab-skrining" role="tabpanel">
                            <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-stethoscope mr-1 text-warning"></i> Riwayat Skrining Mandiri & Faskes</h6>
                            @if($patient->screenings->count() > 0)
                                <div class="table-responsive">
                                    <table id="patientScreeningsTable" class="table table-sm table-bordered table-hover data-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Kode Skrining</th>
                                                <th>Tanggal</th>
                                                <th>Total Skor</th>
                                                <th>Kategori Risiko</th>
                                                <th>Status</th>
                                                <th>Rekomendasi Medis</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->screenings as $scr)
                                                <tr>
                                                    <td class="font-weight-bold text-primary">{{ $scr->code }}</td>
                                                    <td data-order="{{ $scr->screened_at ? $scr->screened_at->timestamp : $scr->created_at->timestamp }}">{{ $scr->screened_at ? $scr->screened_at->format('d/m/Y H:i') : $scr->created_at->format('d/m/Y') }}</td>
                                                    <td class="font-weight-bold">{{ $scr->total_score }}</td>
                                                    <td>
                                                        @if($scr->risk_level == 'Risiko Tinggi')
                                                            <span class="badge badge-tb-tinggi">Risiko Tinggi</span>
                                                        @elseif($scr->risk_level == 'Risiko Sedang')
                                                            <span class="badge badge-tb-sedang">Risiko Sedang</span>
                                                        @else
                                                            <span class="badge badge-tb-rendah">Risiko Rendah</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light border font-weight-bold">{{ $scr->status }}</span>
                                                    </td>
                                                    <td class="text-xs" style="max-width: 300px;">{{ $scr->recommendation ?? '-' }}</td>
                                                    <td class="text-center">
                                                        <a href="{{ route('admin.screenings.show', $scr->encrypted_id) }}" class="btn btn-xs btn-primary font-weight-bold">
                                                            <i class="fas fa-eye"></i> Detail Skrining
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> Belum ada riwayat pengisian kuesioner skrining TB untuk pasien ini.
                                </div>
                            @endif
                        </div>

                        <!-- TAB 6: KONTAK ERAT -->
                        <div class="tab-pane fade" id="tab-kontak" role="tabpanel">
                            <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-people-arrows mr-1 text-teal"></i> Investigasi Kontak Erat & Keluarga Serumah</h6>
                            @if($patient->closeContacts->count() > 0)
                                <div class="table-responsive">
                                    <table id="patientContactsTable" class="table table-sm table-bordered table-hover data-table">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Kode Kontak</th>
                                                <th>Nama Kontak</th>
                                                <th>Hubungan</th>
                                                <th>JK & Umur</th>
                                                <th>No. HP</th>
                                                <th>Hasil Skrining Kontak</th>
                                                <th>Status TPT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($patient->closeContacts as $cc)
                                                <tr>
                                                    <td class="font-weight-bold text-primary">{{ $cc->contact_code }}</td>
                                                    <td class="font-weight-bold text-dark">{{ $cc->name }}</td>
                                                    <td><span class="badge badge-light border">{{ $cc->relationship }}</span></td>
                                                    <td>{{ $cc->gender == 'L' ? 'L' : 'P' }} &bull; {{ $cc->age }} th</td>
                                                    <td>{{ $cc->phone ?? '-' }}</td>
                                                    <td>
                                                        @if(stripos($cc->screening_result, 'Gejala') !== false || stripos($cc->screening_result, 'Positif') !== false)
                                                            <span class="badge badge-danger font-weight-bold">{{ $cc->screening_result }}</span>
                                                        @else
                                                            <span class="badge badge-success font-weight-bold">{{ $cc->screening_result }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-info">{{ $cc->tpt_status }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle mr-1"></i> Belum ada data kontak erat / keluarga serumah yang dicatat untuk pasien ini.
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
