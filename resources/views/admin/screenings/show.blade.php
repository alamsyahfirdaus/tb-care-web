@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.screenings.index') }}">Skrining TB</a></li>
    <li class="breadcrumb-item active">{{ $screening->code }}</li>
@endsection

@section('content')
    <!-- Top Result Banner -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-left-{{ $screening->risk_level == 'Risiko Tinggi' ? 'danger' : ($screening->risk_level == 'Risiko Sedang' ? 'warning' : 'success') }}" 
                 style="border-left: 5px solid {{ $screening->risk_level == 'Risiko Tinggi' ? '#dc3545' : ($screening->risk_level == 'Risiko Sedang' ? '#ffc107' : '#28a745') }};">
                <div class="card-body p-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div>
                            <span class="badge badge-light border text-sm font-weight-bold mr-2">{{ $screening->code }}</span>
                            <span class="text-xs text-muted">Tanggal: {{ $screening->screened_at ? $screening->screened_at->format('d F Y, H:i') : $screening->created_at->format('d F Y, H:i') }} WIB</span>
                            <h4 class="font-weight-bold text-dark mt-1 mb-0">{{ $screening->person_name }} ({{ $screening->age }} Tahun &bull; {{ $screening->gender == 'L' ? 'Laki-laki' : 'Perempuan' }})</h4>
                        </div>
                        <div class="text-right my-1">
                            <span class="mr-3 font-weight-bold text-muted">Total Skor: <strong class="text-xl text-dark">{{ $screening->total_score }}</strong></span>
                            @if($screening->risk_level == 'Risiko Tinggi')
                                <span class="badge badge-tb-tinggi px-3 py-2 text-md font-weight-bold"><i class="fas fa-biohazard mr-1"></i> Risiko Tinggi TB</span>
                            @elseif($screening->risk_level == 'Risiko Sedang')
                                <span class="badge badge-tb-sedang px-3 py-2 text-md font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Risiko Sedang TB</span>
                            @else
                                <span class="badge badge-tb-rendah px-3 py-2 text-md font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Risiko Rendah TB</span>
                            @endif

                            <button type="button" class="btn btn-sm btn-primary font-weight-bold ml-2" data-toggle="modal" data-target="#modal-update-status">
                                <i class="fas fa-edit mr-1"></i> Ubah Status Tindak Lanjut
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Respondent Info & Recommendation Card -->
        <div class="col-md-4">
            <!-- Respondent Details -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h6 class="card-title font-weight-bold mb-0"><i class="fas fa-user-circle mr-1 text-primary"></i> Data Responden / Pasien</h6>
                </div>
                <div class="card-body p-3">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 40%;">Nama:</td>
                            <td class="font-weight-bold text-dark">{{ $screening->person_name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">NIK:</td>
                            <td class="font-weight-bold">{{ $screening->nik ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">No. Telepon:</td>
                            <td>{{ $screening->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Usia / JK:</td>
                            <td>{{ $screening->age }} Tahun &bull; {{ $screening->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Puskesmas:</td>
                            <td class="font-weight-bold text-primary">{{ optional($screening->puskesmas)->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Wilayah:</td>
                            <td>Kec. {{ optional($screening->subdistrict)->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kelurahan/Desa:</td>
                            <td>{{ optional($screening->village)->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Alamat:</td>
                            <td>{{ $screening->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Formulir Kategori:</td>
                            <td><span class="badge badge-light border">{{ optional($screening->category)->name ?? ($screening->age >= 15 ? 'Usia >= 15 Tahun' : 'Usia < 15 Tahun') }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Recommendation & Status -->
            <div class="card card-outline card-info shadow-sm mb-3">
                <div class="card-header bg-light">
                    <h6 class="card-title font-weight-bold mb-0 text-info"><i class="fas fa-stethoscope mr-1"></i> Rekomendasi & Tindak Lanjut</h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <small class="text-muted d-block font-weight-bold">Status Tindak Lanjut Saat Ini:</small>
                        @if($screening->status == 'Perlu Tindak Lanjut')
                            <span class="badge badge-danger px-3 py-2 text-sm font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Perlu Tindak Lanjut</span>
                        @elseif($screening->status == 'Dalam Pemantauan')
                            <span class="badge badge-warning px-3 py-2 text-sm font-weight-bold text-dark"><i class="fas fa-clock mr-1"></i> Dalam Pemantauan</span>
                        @else
                            <span class="badge badge-success px-3 py-2 text-sm font-weight-bold"><i class="fas fa-check mr-1"></i> Selesai</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block font-weight-bold">Rekomendasi Klinis:</small>
                        <div class="p-2 bg-light rounded border text-sm mt-1">
                            {{ $screening->recommendation ?? 'Tidak ada rekomendasi khusus.' }}
                        </div>
                    </div>

                    @if($screening->notes)
                        <div>
                            <small class="text-muted d-block font-weight-bold">Catatan Petugas:</small>
                            <div class="p-2 bg-light rounded border text-xs mt-1 text-muted">
                                {{ $screening->notes }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if($screening->patient)
                <div class="alert alert-success shadow-sm">
                    <h6 class="font-weight-bold mb-1"><i class="fas fa-link mr-1"></i> Terhubung ke Rekam Pasien</h6>
                    <span class="text-xs d-block mb-2">Responden ini sudah terdaftar sebagai Pasien Pengobatan TB Care.</span>
                    <a href="{{ route('admin.patients.show', $screening->patient->encrypted_id) }}" class="btn btn-xs btn-success font-weight-bold">
                        Buka Rekam Pasien <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            @endif
        </div>

        <!-- Comprehensive Answers Breakdown (Requirement #11) -->
        <div class="col-md-8">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-primary">
                        <i class="fas fa-clipboard-check mr-1"></i> SELURUH JAWABAN KUESIONER SKRINING
                    </h3>
                    <span class="badge badge-light border">{{ $screening->answers->count() }} Pertanyaan Terjawab</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="screeningAnswersTable" class="table table-bordered table-hover data-table mb-3">
                            <thead>
                                <tr>
                                    <th style="width: 40px;" class="text-center">No</th>
                                    <th>Kategori / Kelompok</th>
                                    <th>Pertanyaan Penapisan</th>
                                    <th style="width: 110px;" class="text-center">Jawaban</th>
                                    <th style="width: 110px;" class="text-center">Tipe Gejala</th>
                                    <th style="width: 70px;" class="text-center">Skor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($screening->answers as $aIndex => $ans)
                                    @php
                                        $isYes = in_array($ans->answer, ['1', 1, 'Ya', 'ya', 'YA']);
                                    @endphp
                                    <tr class="{{ ($ans->is_critical && $isYes) ? 'table-warning' : '' }}">
                                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="badge badge-light border">
                                                <i class="fas {{ stripos($ans->group_name, 'Gejala') !== false ? 'fa-viruses text-danger' : 'fa-clipboard-list text-primary' }} mr-1"></i>
                                                {{ $ans->group_name ?? 'Lainnya' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold text-dark">{{ $ans->question_text ?? optional($ans->question)->question }}</span>
                                            @if($ans->duration_days)
                                                <div class="text-xs text-danger font-weight-bold mt-1">
                                                    <i class="fas fa-hourglass-half mr-1"></i> Durasi berlangsung: {{ $ans->duration_days }} hari
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($isYes)
                                                <span class="badge badge-danger px-3 py-1 font-weight-bold"><i class="fas fa-check mr-1"></i> YA</span>
                                            @else
                                                <span class="badge badge-light border px-3 py-1 text-muted"><i class="fas fa-times mr-1"></i> TIDAK</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($ans->is_critical)
                                                <span class="badge badge-warning text-dark text-xs font-weight-bold">Gejala Kritis</span>
                                            @else
                                                <span class="badge badge-light border text-xs text-muted">Faktor Risiko</span>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-bold {{ $ans->score > 0 ? 'text-danger' : 'text-muted' }}" data-order="{{ $ans->score }}">
                                            {{ $ans->score }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Score Row -->
                    <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Total Gejala Ditemukan:</span> <strong>{{ $screening->symptoms_count }}</strong> &bull;
                            <span class="text-muted">Gejala Kritis Batuk/Darah/BB:</span> 
                            <strong class="{{ $screening->has_critical_symptom ? 'text-danger' : 'text-success' }}">
                                {{ $screening->has_critical_symptom ? 'YA (Terdeteksi)' : 'TIDAK' }}
                            </strong>
                        </div>
                        <div>
                            <span class="text-muted font-weight-bold mr-2">TOTAL SKOR SKRINING:</span>
                            <span class="badge badge-primary px-3 py-2 text-lg font-weight-bold">{{ $screening->total_score }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Update Status Tindak Lanjut -->
    <div class="modal fade" id="modal-update-status" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.screenings.update_status', $screening->encrypted_id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-1"></i> Perbarui Status Tindak Lanjut Skrining</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="status">Status Tindak Lanjut <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="Perlu Tindak Lanjut" {{ $screening->status == 'Perlu Tindak Lanjut' ? 'selected' : '' }}>Perlu Tindak Lanjut (Rujuk ke Puskesmas)</option>
                                <option value="Dalam Pemantauan" {{ $screening->status == 'Dalam Pemantauan' ? 'selected' : '' }}>Dalam Pemantauan (Edukasi 14 Hari)</option>
                                <option value="Selesai" {{ $screening->status == 'Selesai' ? 'selected' : '' }}>Selesai (Sudah Diperiksa / Tidak Bergejala)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notes">Catatan Tindak Lanjut / Rujukan Faskes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Contoh: Pasien telah dihubungi oleh Kader dan dijadwalkan tes dahak TCM di Puskesmas Tamansari...">{{ $screening->notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-default font-weight-bold" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary font-weight-bold shadow-sm">
                            <i class="fas fa-save mr-1"></i> Simpan Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
