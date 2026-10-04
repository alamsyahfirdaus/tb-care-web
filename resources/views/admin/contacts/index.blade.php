@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contacts.index') }}">Kontak Erat</a></li>
    <li class="breadcrumb-item active">{{ $pageTitle }}</li>
@endsection

@section('content')
    <!-- Top Tabs & Action -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('admin.contacts.index') }}" class="btn {{ empty($filterType) ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-people-arrows mr-1"></i> Semua Kontak
                </a>
                <a href="{{ route('admin.contacts.index', ['filter' => 'keluarga']) }}" class="btn {{ $filterType == 'keluarga' ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-home mr-1"></i> Kontak Keluarga Serumah
                </a>
                <a href="{{ route('admin.contacts.index', ['filter' => 'screening']) }}" class="btn {{ $filterType == 'screening' ? 'btn-primary' : 'btn-default' }} font-weight-bold">
                    <i class="fas fa-notes-medical mr-1"></i> Hasil Skrining Kontak
                </a>
            </div>
            <a href="{{ route('admin.contacts.create') }}" class="btn btn-sm btn-teal font-weight-bold shadow-sm text-white" style="background-color: #20c997;">
                <i class="fas fa-user-plus mr-1"></i> Catat Kontak Erat Baru
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card card-outline card-teal filter-card mb-3 shadow-sm">
        <div class="card-body py-2">
            <form action="{{ route('admin.contacts.index') }}" method="GET" class="form-row align-items-center">
                @if($filterType)
                    <input type="hidden" name="filter" value="{{ $filterType }}">
                @endif
                <div class="col-md-4 my-1">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari nama kontak, NIK, pasien sumber...">
                    </div>
                </div>
                <div class="col-md-3 my-1">
                    <select name="relationship" class="form-control form-control-sm select2">
                        <option value="">-- Hubungan: Semua --</option>
                        <option value="Keluarga Serumah" {{ $relationshipFilter == 'Keluarga Serumah' ? 'selected' : '' }}>Keluarga Serumah</option>
                        <option value="Teman Kerja" {{ $relationshipFilter == 'Teman Kerja' ? 'selected' : '' }}>Teman Kerja</option>
                        <option value="Tetangga Dekat" {{ $relationshipFilter == 'Tetangga Dekat' ? 'selected' : '' }}>Tetangga Dekat</option>
                        <option value="Pengasuh" {{ $relationshipFilter == 'Pengasuh' ? 'selected' : '' }}>Pengasuh</option>
                    </select>
                </div>
                <div class="col-md-3 my-1">
                    <select name="result" class="form-control form-control-sm select2">
                        <option value="">-- Hasil Skrining: Semua --</option>
                        <option value="Sehat / Tidak Bergejala" {{ $resultFilter == 'Sehat / Tidak Bergejala' ? 'selected' : '' }}>Sehat / Tidak Bergejala</option>
                        <option value="Gejala TB / Terduga" {{ $resultFilter == 'Gejala TB / Terduga' ? 'selected' : '' }}>Gejala TB / Terduga</option>
                        <option value="Mulai TPT (Pencegahan)" {{ $resultFilter == 'Mulai TPT (Pencegahan)' ? 'selected' : '' }}>Mulai TPT (Pencegahan)</option>
                        <option value="Positif TB" {{ $resultFilter == 'Positif TB' ? 'selected' : '' }}>Positif TB</option>
                    </select>
                </div>
                <div class="col-md-2 my-1 d-flex">
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold mr-1 flex-grow-1"><i class="fas fa-filter"></i> Filter</button>
                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-sm btn-default"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Contacts Table -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold">
                <i class="fas fa-people-arrows mr-1 text-teal"></i> {{ $pageTitle }}
            </h3>
            <span class="badge badge-light border">Total: {{ $contacts->count() }} data</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="contactsTable" class="table table-bordered table-hover data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Kode Kontak</th>
                            <th>Nama Kontak Erat</th>
                            <th>Hubungan</th>
                            <th>Usia & JK</th>
                            <th>Pasien Indeks / Sumber</th>
                            <th>Hasil Skrining</th>
                            <th>Status TPT</th>
                            <th style="width: 120px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $index => $c)
                            <tr>
                                <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                <td class="font-weight-bold text-primary">{{ $c->contact_code }}</td>
                                <td>
                                    <span class="font-weight-bold text-dark">{{ $c->name }}</span>
                                    <div class="text-xs text-muted">NIK: {{ $c->nik ?? '-' }} &bull; {{ $c->phone ?? '-' }}</div>
                                </td>
                                <td><span class="badge badge-light border">{{ $c->relationship }}</span></td>
                                <td data-order="{{ $c->age }}">{{ $c->age }} Th &bull; {{ $c->gender == 'L' ? 'L' : 'P' }}</td>
                                <td>
                                    @if($c->patient)
                                        <a href="{{ route('admin.patients.show', $c->patient->id) }}" class="font-weight-bold text-dark d-block">
                                            {{ optional($c->patient->user)->name ?? 'Pasien #' . $c->patient->id }}
                                        </a>
                                        <span class="text-xs text-primary">{{ optional($c->patient->puskesmas)->name ?? '-' }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if(stripos($c->screening_result, 'Gejala') !== false || stripos($c->screening_result, 'Positif') !== false)
                                        <span class="badge badge-danger font-weight-bold"><i class="fas fa-exclamation-triangle mr-1"></i> {{ $c->screening_result }}</span>
                                    @elseif(stripos($c->screening_result, 'TPT') !== false)
                                        <span class="badge badge-info font-weight-bold"><i class="fas fa-shield-virus mr-1"></i> {{ $c->screening_result }}</span>
                                    @else
                                        <span class="badge badge-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> {{ $c->screening_result }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light border font-weight-bold">{{ $c->tpt_status }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-action-group">
                                        <a href="{{ route('admin.contacts.edit', $c->id) }}" class="btn btn-warning btn-xs" title="Edit Kontak">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.contacts.destroy', $c->id) }}" method="POST" id="del-contact-{{ $c->id }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-xs" title="Hapus Kontak" 
                                                    onclick="confirmDelete('del-contact-{{ $c->id }}', '{{ $c->name }}')">
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
