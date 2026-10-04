@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.examinations.index') }}">Pemeriksaan</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? 'Edit Pemeriksaan' : 'Tambah Pemeriksaan' }}</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-outline card-info shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas {{ $isEdit ? 'fa-edit' : 'fa-plus-circle' }} mr-1 text-info"></i>
                        {{ $isEdit ? 'Formulir Perbarui Data Pemeriksaan TB' : 'Formulir Catat Pemeriksaan Laboratorium TB Baru' }}
                    </h3>
                </div>
                <form action="{{ $isEdit ? route('admin.examinations.update', $examination->id) : route('admin.examinations.store') }}" method="POST">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif
                    <div class="card-body">
                        <div class="form-group">
                            <label for="patient_id">Pilih Pasien TB <span class="text-danger">*</span></label>
                            <select name="patient_id" id="patient_id" class="form-control select2 @error('patient_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Pasien --</option>
                                @foreach($patients as $p)
                                    <option value="{{ $p->id }}" {{ old('patient_id', $examination->patient_id) == $p->id ? 'selected' : '' }}>
                                        {{ optional($p->user)->name ?? 'Pasien #' . $p->id }} (NIK: {{ $p->nik ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('patient_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="examination_date">Tanggal Pemeriksaan <span class="text-danger">*</span></label>
                                <input type="date" name="examination_date" id="examination_date" class="form-control @error('examination_date') is-invalid @enderror" 
                                       value="{{ old('examination_date', $examination->examination_date ? Carbon\Carbon::parse($examination->examination_date)->format('Y-m-d') : Carbon\Carbon::now()->format('Y-m-d')) }}" required>
                                @error('examination_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="puskesmas_id">Puskesmas / Lab Pemeriksa</label>
                                <select name="puskesmas_id" id="puskesmas_id" class="form-control select2">
                                    <option value="">-- Pilih Puskesmas --</option>
                                    @foreach($puskesmas as $pkm)
                                        <option value="{{ $pkm->id }}" {{ old('puskesmas_id', $examination->puskesmas_id) == $pkm->id ? 'selected' : '' }}>
                                            {{ $pkm->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="examination_type">Jenis Pemeriksaan <span class="text-danger">*</span></label>
                                <select name="examination_type" id="examination_type" class="form-control select2 @error('examination_type') is-invalid @enderror" required>
                                    @foreach(['Tes Cepat Molekuler (TCM)', 'BTA (Mikroskopis)', 'Rontgen Dada (Thorax X-Ray)', 'Kultur / Biakan Dahak', 'Uji Kepekaan Obat (DST)'] as $t)
                                        <option value="{{ $t }}" {{ old('examination_type', $examination->examination_type) == $t ? 'selected' : '' }}>{{ $t }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="result">Hasil Laboratorium <span class="text-danger">*</span></label>
                                <select name="result" id="result" class="form-control select2 @error('result') is-invalid @enderror" required>
                                    @foreach(['Positif', 'Negatif', 'Sensitif Rifampisin', 'Resisten Rifampisin', 'Tersangka / Lesi Aktif', 'Normal', 'Inkonklusif'] as $res)
                                        <option value="{{ $res }}" {{ old('result', $examination->result) == $res ? 'selected' : '' }}>{{ $res }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label for="diagnosis">Diagnosis Klinis Medis <span class="text-danger">*</span></label>
                                <input type="text" name="diagnosis" id="diagnosis" class="form-control @error('diagnosis') is-invalid @enderror" 
                                       value="{{ old('diagnosis', $examination->diagnosis) }}" placeholder="Contoh: TB Paru Terkonfirmasi Bakteriologis" required>
                                @error('diagnosis')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="status">Status Pemeriksaan <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control select2" required>
                                    <option value="Selesai" {{ old('status', $examination->status ?? 'Selesai') == 'Selesai' ? 'selected' : '' }}>Selesai (Hasil Keluar)</option>
                                    <option value="Menunggu Hasil" {{ old('status', $examination->status) == 'Menunggu Hasil' ? 'selected' : '' }}>Menunggu Hasil Lab</option>
                                    <option value="Perlu Pemeriksaan Ulang" {{ old('status', $examination->status) == 'Perlu Pemeriksaan Ulang' ? 'selected' : '' }}>Perlu Uji Ulang</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="laboratory_notes">Catatan Tambahan Laboratorium</label>
                            <textarea name="laboratory_notes" id="laboratory_notes" class="form-control" rows="3" placeholder="Nilai kuantitatif atau temuan klinis spesifik...">{{ old('laboratory_notes', $examination->laboratory_notes) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-between">
                        <a href="{{ route('admin.examinations.index') }}" class="btn btn-default font-weight-bold">
                            <i class="fas fa-times mr-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-info font-weight-bold shadow-sm">
                            <i class="fas fa-save mr-1"></i> Simpan Data Pemeriksaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
