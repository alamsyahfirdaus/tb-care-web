@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.examinations.index') }}">Pemeriksaan</a></li>
    <li class="breadcrumb-item active">{{ $pageTitle }}</li>
@endsection

@section('content')
    <!-- Top Tabs & Action -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('admin.examinations.index') }}" class="btn {{ empty($viewType) ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-microscope mr-1"></i> Semua Pemeriksaan
                </a>
                <a href="{{ route('admin.examinations.index', ['view' => 'results']) }}" class="btn {{ $viewType == 'results' ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-file-medical-alt mr-1"></i> Hasil Laboratorium
                </a>
                <a href="{{ route('admin.examinations.index', ['view' => 'diagnosis']) }}" class="btn {{ $viewType == 'diagnosis' ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-stethoscope mr-1"></i> Diagnosis TB
                </a>
            </div>
            <a href="{{ route('admin.examinations.create') }}" class="btn btn-sm btn-info font-weight-bold shadow-sm">
                <i class="fas fa-plus-circle mr-1"></i> Catat Pemeriksaan Baru
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-outline card-info filter-card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form action="{{ route('admin.examinations.index') }}" method="GET" class="form-row align-items-center">
                @if($viewType)
                    <input type="hidden" name="view" value="{{ $viewType }}">
                @endif
                <div class="col-md-3 my-1">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari kode, pasien, diagnosis...">
                    </div>
                </div>
                <div class="col-md-3 my-1">
                    <select name="type" class="form-control form-control-sm select2">
                        <option value="">-- Jenis Tes: Semua --</option>
                        <option value="Tes Cepat Molekuler (TCM)" {{ $typeFilter == 'Tes Cepat Molekuler (TCM)' ? 'selected' : '' }}>Tes Cepat Molekuler (TCM)</option>
                        <option value="BTA (Mikroskopis)" {{ $typeFilter == 'BTA (Mikroskopis)' ? 'selected' : '' }}>BTA (Mikroskopis)</option>
                        <option value="Rontgen Dada (Thorax X-Ray)" {{ $typeFilter == 'Rontgen Dada (Thorax X-Ray)' ? 'selected' : '' }}>Rontgen Dada (Thorax X-Ray)</option>
                        <option value="Kultur / Biakan Dahak" {{ $typeFilter == 'Kultur / Biakan Dahak' ? 'selected' : '' }}>Kultur / Biakan Dahak</option>
                    </select>
                </div>
                <div class="col-md-2 my-1">
                    <select name="result" class="form-control form-control-sm select2">
                        <option value="">-- Hasil: Semua --</option>
                        <option value="Positif" {{ $resultFilter == 'Positif' ? 'selected' : '' }}>Positif</option>
                        <option value="Negatif" {{ $resultFilter == 'Negatif' ? 'selected' : '' }}>Negatif</option>
                        <option value="Sensitif Rifampisin" {{ $resultFilter == 'Sensitif Rifampisin' ? 'selected' : '' }}>Sensitif Rifampisin</option>
                        <option value="Resisten Rifampisin" {{ $resultFilter == 'Resisten Rifampisin' ? 'selected' : '' }}>Resisten Rifampisin</option>
                        <option value="Tersangka / Lesi Aktif" {{ $resultFilter == 'Tersangka / Lesi Aktif' ? 'selected' : '' }}>Tersangka / Lesi Aktif</option>
                    </select>
                </div>
                <div class="col-md-3 my-1">
                    <select name="puskesmas_id" class="form-control form-control-sm select2">
                        <option value="">-- Puskesmas: Semua --</option>
                        @foreach($puskesmasList as $pkm)
                            <option value="{{ $pkm->id }}" {{ $puskesmasFilter == $pkm->id ? 'selected' : '' }}>{{ $pkm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 my-1 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold mr-1 flex-grow-1"><i class="fas fa-filter"></i></button>
                    <a href="{{ route('admin.examinations.index') }}" class="btn btn-sm btn-default"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Examinations Table -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold mb-0">
                <i class="fas fa-list mr-1 text-info"></i> {{ $pageTitle }} <span class="badge badge-light border ml-2">Total: {{ $examinations->count() }} data</span>
            </h3>
            <a href="{{ route('admin.examinations.create') }}" class="btn btn-sm btn-info font-weight-bold shadow-sm">
                <i class="fas fa-plus mr-1"></i> Catat Pemeriksaan
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="examinationsTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Kode Pemeriksaan</th>
                            <th>Pasien</th>
                            <th>Tanggal Periksa</th>
                            <th>Jenis Pemeriksaan</th>
                            <th>Hasil Uji Lab</th>
                            <th>Diagnosis Medis</th>
                            <th>Puskesmas</th>
                            <th style="width: 120px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($examinations as $index => $exam)
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td class="font-weight-bold text-primary">{{ $exam->examination_code }}</td>
                                <td>
                                    @if($exam->patient)
                                        <a href="{{ route('admin.patients.show', $exam->patient->encrypted_id) }}" class="font-weight-bold text-dark d-block">
                                            {{ optional($exam->patient->user)->name ?? 'Pasien #' . $exam->patient->id }}
                                        </a>
                                        <span class="text-xs text-muted">NIK: {{ $exam->patient->nik ?? '-' }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td data-order="{{ Carbon\Carbon::parse($exam->examination_date)->timestamp }}">{{ Carbon\Carbon::parse($exam->examination_date)->format('d/m/Y') }}</td>
                                <td><span class="badge badge-light border font-weight-bold">{{ $exam->examination_type }}</span></td>
                                <td>
                                    @if(in_array($exam->result, ['Positif', 'Resisten Rifampisin', 'Tersangka / Lesi Aktif']))
                                        <span class="badge badge-danger font-weight-bold"><i class="fas fa-biohazard mr-1"></i> {{ $exam->result }}</span>
                                    @elseif($exam->result == 'Negatif' || $exam->result == 'Normal')
                                        <span class="badge badge-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> {{ $exam->result }}</span>
                                    @else
                                        <span class="badge badge-info font-weight-bold">{{ $exam->result }}</span>
                                    @endif
                                </td>
                                <td class="font-weight-bold text-dark">{{ $exam->diagnosis ?? '-' }}</td>
                                <td>{{ optional($exam->puskesmas)->name ?? '-' }}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-action-group">
                                        <a href="{{ route('admin.examinations.edit', $exam->encrypted_id) }}" class="btn btn-warning btn-xs" title="Edit Data">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.examinations.destroy', $exam->encrypted_id) }}" method="POST" id="del-exam-{{ $exam->encrypted_id }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-xs" title="Hapus Data" 
                                                    onclick="confirmDelete('del-exam-{{ $exam->encrypted_id }}', '{{ $exam->examination_code }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
