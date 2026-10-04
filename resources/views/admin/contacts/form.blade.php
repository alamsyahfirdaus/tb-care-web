@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contacts.index') }}">Kontak Erat</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? 'Edit Kontak' : 'Tambah Kontak' }}</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card card-outline card-teal shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas {{ $isEdit ? 'fa-edit' : 'fa-plus-circle' }} mr-1 text-teal"></i>
                        {{ $isEdit ? 'Formulir Perbarui Data Kontak Erat' : 'Formulir Pencatatan Kontak Erat Baru' }}
                    </h3>
                </div>
                <form action="{{ $isEdit ? route('admin.contacts.update', $contact->id) : route('admin.contacts.store') }}" method="POST">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif
                    <div class="card-body">
                        <div class="form-group">
                            <label for="patient_id">Pasien Indeks / Sumber Kontak <span class="text-danger">*</span></label>
                            <select name="patient_id" id="patient_id" class="form-control select2 @error('patient_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Pasien Sumber --</option>
                                @foreach($patients as $p)
                                    <option value="{{ $p->id }}" {{ old('patient_id', $contact->patient_id) == $p->id ? 'selected' : '' }}>
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
                                <label for="name">Nama Kontak Erat <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $contact->name) }}" placeholder="Contoh: Siti Rahmawati" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="nik">NIK Kontak (KTP/KIA)</label>
                                <input type="text" name="nik" id="nik" class="form-control" value="{{ old('nik', $contact->nik) }}" placeholder="16 digit NIK">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="relationship">Hubungan dengan Pasien <span class="text-danger">*</span></label>
                                <select name="relationship" id="relationship" class="form-control select2" required>
                                    @foreach(['Keluarga Serumah', 'Teman Kerja', 'Tetangga Dekat', 'Pengasuh', 'Lainnya'] as $rel)
                                        <option value="{{ $rel }}" {{ old('relationship', $contact->relationship) == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="gender">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" id="gender" class="form-control select2" required>
                                    <option value="L" {{ old('gender', $contact->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender', $contact->gender) == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="age">Usia Kontak (Tahun) <span class="text-danger">*</span></label>
                                <input type="number" name="age" id="age" min="0" max="120" class="form-control @error('age') is-invalid @enderror" 
                                       value="{{ old('age', $contact->age ?? 25) }}" required>
                                @error('age')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="phone">No. Handphone / WhatsApp</label>
                                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $contact->phone) }}" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="address">Alamat Kontak</label>
                                <input type="text" name="address" id="address" class="form-control" value="{{ old('address', $contact->address) }}" placeholder="Alamat tinggal saat ini">
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-teal mb-3"><i class="fas fa-stethoscope mr-1"></i> Hasil Investigasi Kontak & Pencegahan</h6>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="screening_date">Tanggal Skrining Kontak</label>
                                <input type="date" name="screening_date" id="screening_date" class="form-control" 
                                       value="{{ old('screening_date', $contact->screening_date ? Carbon\Carbon::parse($contact->screening_date)->format('Y-m-d') : Carbon\Carbon::now()->format('Y-m-d')) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="screening_result">Hasil Skrining Kontak <span class="text-danger">*</span></label>
                                <select name="screening_result" id="screening_result" class="form-control select2" required>
                                    @foreach(['Sehat / Tidak Bergejala', 'Gejala TB / Terduga', 'Dirujuk ke Puskesmas', 'Mulai TPT (Pencegahan)', 'Positif TB'] as $res)
                                        <option value="{{ $res }}" {{ old('screening_result', $contact->screening_result) == $res ? 'selected' : '' }}>{{ $res }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="tpt_status">Status Terapi Pencegahan (TPT) <span class="text-danger">*</span></label>
                                <select name="tpt_status" id="tpt_status" class="form-control select2" required>
                                    @foreach(['Tidak Perlu', 'Dianjurkan', 'Sedang TPT', 'Selesai TPT', 'Menolak'] as $tpt)
                                        <option value="{{ $tpt }}" {{ old('tpt_status', $contact->tpt_status) == $tpt ? 'selected' : '' }}>{{ $tpt }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Catatan Tambahan Investigasi</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Catatan kunjungan rumah kader / rujukan...">{{ old('notes', $contact->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-between">
                        <a href="{{ route('admin.contacts.index') }}" class="btn btn-default font-weight-bold">
                            <i class="fas fa-times mr-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-teal font-weight-bold shadow-sm text-white" style="background-color: #20c997;">
                            <i class="fas fa-save mr-1"></i> Simpan Kontak Erat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
