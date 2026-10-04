@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.screenings.index') }}">Skrining TB</a></li>
    <li class="breadcrumb-item active">{{ $riskFilter ?? ($statusFilter == 'Perlu Tindak Lanjut' ? 'Perlu Tindak Lanjut' : 'Semua Skrining') }}</li>
@endsection

@section('content')
    <!-- Risk Category Quick Filter Pills -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="btn-group btn-group-sm flex-wrap" role="group">
                <a href="{{ route('admin.screenings.index') }}" class="btn {{ empty($riskFilter) && empty($statusFilter) ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-list mr-1"></i> Semua Skrining
                </a>
                <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Rendah']) }}" class="btn {{ $riskFilter == 'Risiko Rendah' ? 'btn-success' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-shield-alt mr-1"></i> Risiko Rendah
                </a>
                <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Sedang']) }}" class="btn {{ $riskFilter == 'Risiko Sedang' ? 'btn-warning' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-exclamation-circle mr-1"></i> Risiko Sedang
                </a>
                <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Tinggi']) }}" class="btn {{ $riskFilter == 'Risiko Tinggi' ? 'btn-danger' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-biohazard mr-1"></i> Risiko Tinggi
                </a>
                <a href="{{ route('admin.screenings.action_needed') }}" class="btn {{ $statusFilter == 'Perlu Tindak Lanjut' ? 'btn-danger' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-bell mr-1"></i> Perlu Tindak Lanjut
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-outline card-warning filter-card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form action="{{ route('admin.screenings.index') }}" method="GET" class="form-row align-items-center">
                @if($riskFilter)
                    <input type="hidden" name="risk" value="{{ $riskFilter }}">
                @endif
                <div class="col-md-3 my-1">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari kode, nama, NIK...">
                    </div>
                </div>
                <div class="col-md-2 my-1">
                    <select name="puskesmas_id" class="form-control form-control-sm select2">
                        <option value="">-- Puskesmas --</option>
                        @foreach($puskesmasList as $pkm)
                            <option value="{{ $pkm->id }}" {{ $puskesmasFilter == $pkm->id ? 'selected' : '' }}>
                                {{ $pkm->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 my-1">
                    <select name="subdistrict_id" class="form-control form-control-sm select2">
                        <option value="">-- Kecamatan --</option>
                        @foreach($subdistricts as $sub)
                            <option value="{{ $sub->id }}" {{ $subdistrictFilter == $sub->id ? 'selected' : '' }}>
                                {{ $sub->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 my-1">
                    <input type="date" name="date_start" value="{{ $dateStart }}" class="form-control form-control-sm" title="Tanggal Mulai">
                </div>
                <div class="col-md-2 my-1">
                    <input type="date" name="date_end" value="{{ $dateEnd }}" class="form-control form-control-sm" title="Tanggal Akhir">
                </div>
                <div class="col-md-1 my-1 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold mr-1 flex-grow-1" title="Filter Data">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="{{ route('admin.screenings.index') }}" class="btn btn-sm btn-default" title="Reset">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Screenings Table -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold">
                <i class="fas fa-stethoscope mr-1 text-warning"></i> {{ $pageTitle }}
            </h3>
            <span class="badge badge-light border">Total: {{ $screenings->count() }} data</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="screeningsTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">No</th>
                            <th>Kode Skrining</th>
                            <th>Nama Responden / Pasien</th>
                            <th>Tanggal</th>
                            <th>Usia & JK</th>
                            <th>Puskesmas / Wilayah</th>
                            <th class="text-center">Skor</th>
                            <th>Kategori Risiko</th>
                            <th>Status Tindak Lanjut</th>
                            <th style="width: 100px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($screenings as $index => $scr)
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td>
                                    <a href="{{ route('admin.screenings.show', $scr->encrypted_id) }}" class="font-weight-bold text-primary">
                                        {{ $scr->code }}
                                    </a>
                                </td>
                                <td>
                                    <span class="font-weight-bold text-dark">{{ $scr->person_name }}</span>
                                    @if($scr->patient_id)
                                        <span class="badge badge-danger text-xs ml-1"><i class="fas fa-user-injured mr-1"></i> Pasien TB</span>
                                    @endif
                                    <div class="text-xs text-muted">NIK: {{ $scr->nik ?? '-' }}</div>
                                </td>
                                <td data-order="{{ $scr->screened_at ? $scr->screened_at->timestamp : $scr->created_at->timestamp }}">
                                    <div>{{ $scr->screened_at ? $scr->screened_at->format('d/m/Y') : $scr->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-muted">{{ $scr->screened_at ? $scr->screened_at->format('H:i') : '' }} WIB</div>
                                </td>
                                <td>
                                    {{ $scr->age }} Th &bull; 
                                    <span class="font-weight-bold">{{ $scr->gender == 'L' ? 'L' : 'P' }}</span>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-primary">{{ optional($scr->puskesmas)->name ?? '-' }}</div>
                                    <div class="text-xs text-muted">Kec. {{ optional($scr->subdistrict)->name ?? '-' }}</div>
                                </td>
                                <td class="text-center font-weight-bold text-md" data-order="{{ $scr->total_score }}">
                                    {{ $scr->total_score }}
                                </td>
                                <td>
                                    @if($scr->risk_level == 'Risiko Tinggi')
                                        <span class="badge badge-tb-tinggi"><i class="fas fa-biohazard mr-1"></i> Risiko Tinggi</span>
                                    @elseif($scr->risk_level == 'Risiko Sedang')
                                        <span class="badge badge-tb-sedang"><i class="fas fa-exclamation mr-1"></i> Risiko Sedang</span>
                                    @else
                                        <span class="badge badge-tb-rendah"><i class="fas fa-shield-alt mr-1"></i> Risiko Rendah</span>
                                    @endif
                                </td>
                                <td>
                                    @if($scr->status == 'Perlu Tindak Lanjut')
                                        <span class="badge badge-danger font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> Perlu Tindak Lanjut</span>
                                    @elseif($scr->status == 'Dalam Pemantauan')
                                        <span class="badge badge-warning font-weight-bold text-dark"><i class="fas fa-clock mr-1"></i> Dalam Pemantauan</span>
                                    @else
                                        <span class="badge badge-success font-weight-bold"><i class="fas fa-check mr-1"></i> Selesai</span>
                                    @endif
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('admin.screenings.show', $scr->encrypted_id) }}" class="btn btn-info btn-xs font-weight-bold" title="Buka Detail & Seluruh Jawaban">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    <button type="button" class="btn btn-danger btn-xs" title="Hapus Skrining" 
                                            onclick="confirmDelete('{{ route('admin.screenings.destroy', $scr->encrypted_id) }}', 'Skrining {{ $scr->code }} - {{ addslashes($scr->person_name) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
