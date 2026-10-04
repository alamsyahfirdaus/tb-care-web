@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.patients.index') }}">Pasien TB</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? 'Edit Pasien' : 'Tambah Pasien' }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-danger shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas {{ $isEdit ? 'fa-user-edit' : 'fa-user-plus' }} mr-1 text-danger"></i>
                        {{ $isEdit ? 'Formulir Perbarui Rekam Medis Pasien' : 'Formulir Registrasi Pasien TB Baru' }}
                    </h3>
                </div>
                <form action="{{ $isEdit ? route('admin.patients.update', $patient->encrypted_id) : route('admin.patients.store') }}" method="POST">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                        <input type="hidden" name="encrypted_id" value="{{ $patient->encrypted_id }}">
                    @endif
                    <div class="card-body">
                        <!-- 1. Identitas Pribadi -->
                        <h6 class="font-weight-bold text-danger mb-3"><i class="fas fa-id-card mr-1"></i> [1] Identitas Pribadi & Kependudukan</h6>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Nama Lengkap Pasien <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', optional($patient->user)->name) }}" placeholder="Contoh: Rian Hidayat" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="nik">Nomor Induk Kependudukan (NIK) <span class="text-danger">*</span></label>
                                <input type="text" name="nik" id="nik" maxlength="16" class="form-control @error('nik') is-invalid @enderror" 
                                       value="{{ old('nik', $patient->nik) }}" placeholder="16 digit NIK KTP" required>
                                @error('nik')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            @if(!$isEdit)
                                <div class="form-group col-md-4">
                                    <label for="username">Username Pasien <span class="text-danger">*</span></label>
                                    <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" 
                                           value="{{ old('username') }}" placeholder="Username akun portal/mobile" required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                            <div class="form-group col-md-{{ $isEdit ? '4' : '4' }}">
                                <label for="phone">No. Handphone / WhatsApp</label>
                                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', optional($patient->user)->phone) }}" placeholder="08xxxxxxxxxx">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-{{ $isEdit ? '4' : '4' }}">
                                <label for="gender">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" id="gender" class="form-control select2 @error('gender') is-invalid @enderror" required>
                                    <option value="L" {{ old('gender', optional($patient->user)->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender', optional($patient->user)->gender) == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-{{ $isEdit ? '4' : '4' }}">
                                <label for="date_of_birth">Tanggal Lahir</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       value="{{ old('date_of_birth', optional($patient->user)->date_of_birth ? Carbon\Carbon::parse(optional($patient->user)->date_of_birth)->format('Y-m-d') : '') }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- 2. Alamat & Wilayah -->
                        <hr>
                        <h6 class="font-weight-bold text-danger mb-3"><i class="fas fa-map-marked-alt mr-1"></i> [2] Alamat Domisili & Puskesmas Pembina</h6>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="puskesmas_id">Puskesmas Pembina <span class="text-danger">*</span></label>
                                <select name="puskesmas_id" id="puskesmas_id" class="form-control select2 @error('puskesmas_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Puskesmas --</option>
                                    @foreach($puskesmas as $pkm)
                                        <option value="{{ $pkm->id }}" {{ old('puskesmas_id', $patient->puskesmas_id) == $pkm->id ? 'selected' : '' }}>
                                            {{ $pkm->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('puskesmas_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="subdistrict_id">Kecamatan <span class="text-danger">*</span></label>
                                <select name="subdistrict_id" id="subdistrict_id" class="form-control select2 @error('subdistrict_id') is-invalid @enderror" required onchange="loadVillages(this.value)">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    @foreach($subdistricts as $sub)
                                        <option value="{{ $sub->id }}" {{ old('subdistrict_id', $patient->subdistrict_id) == $sub->id ? 'selected' : '' }}>
                                            {{ $sub->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('subdistrict_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="village_id">Kelurahan / Desa</label>
                                <select name="village_id" id="village_id" class="form-control select2 @error('village_id') is-invalid @enderror">
                                    <option value="">-- Pilih Kelurahan/Desa --</option>
                                    @foreach($villages as $vil)
                                        <option value="{{ $vil->id }}" {{ old('village_id', $patient->village_id) == $vil->id ? 'selected' : '' }}>
                                            {{ $vil->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('village_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-8">
                                <label for="address">Alamat Lengkap / Jalan <span class="text-danger">*</span></label>
                                <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror" 
                                       value="{{ old('address', $patient->address) }}" placeholder="Nama jalan, nomor rumah, patokan" required>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-2">
                                <label for="rt">RT</label>
                                <input type="text" name="rt" id="rt" maxlength="5" class="form-control" value="{{ old('rt', $patient->rt) }}" placeholder="01">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="rw">RW</label>
                                <input type="text" name="rw" id="rw" maxlength="5" class="form-control" value="{{ old('rw', $patient->rw) }}" placeholder="05">
                            </div>
                        </div>

                        <!-- 3. Fisik & Klinis -->
                        <hr>
                        <h6 class="font-weight-bold text-danger mb-3"><i class="fas fa-stethoscope mr-1"></i> [3] Kondisi Fisik & Program Pengobatan</h6>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="height">Tinggi Badan (cm)</label>
                                <input type="number" name="height" id="height" class="form-control" value="{{ old('height', $patient->height) }}" placeholder="Contoh: 165">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="weight">Berat Badan (kg)</label>
                                <input type="number" name="weight" id="weight" class="form-control" value="{{ old('weight', $patient->weight) }}" placeholder="Contoh: 55">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="blood_type">Golongan Darah</label>
                                <select name="blood_type" id="blood_type" class="form-control select2">
                                    <option value="">-- Tidak Tahu --</option>
                                    @foreach(['A', 'B', 'AB', 'O'] as $bt)
                                        <option value="{{ $bt }}" {{ old('blood_type', $patient->blood_type) == $bt ? 'selected' : '' }}>{{ $bt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="occupation">Pekerjaan</label>
                                <input type="text" name="occupation" id="occupation" class="form-control" value="{{ old('occupation', $patient->occupation) }}" placeholder="Petani, Buruh, PNS...">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="diagnosis_date">Tanggal Diagnosis TB</label>
                                <input type="date" name="diagnosis_date" id="diagnosis_date" class="form-control" 
                                       value="{{ old('diagnosis_date', $patient->diagnosis_date ? Carbon\Carbon::parse($patient->diagnosis_date)->format('Y-m-d') : '') }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="treatment_start_date">Tanggal Mulai Pengobatan</label>
                                <input type="date" name="treatment_start_date" id="treatment_start_date" class="form-control" 
                                       value="{{ old('treatment_start_date', $patient->treatment_start_date ? Carbon\Carbon::parse($patient->treatment_start_date)->format('Y-m-d') : Carbon\Carbon::now()->format('Y-m-d')) }}">
                            </div>
                            @if(!$isEdit && isset($treatmentTypes))
                                <div class="form-group col-md-4">
                                    <label for="treatment_type_id">Regimen Pengobatan Awal</label>
                                    <select name="treatment_type_id" id="treatment_type_id" class="form-control select2">
                                        <option value="">-- Pilih Regimen --</option>
                                        @foreach($treatmentTypes as $tt)
                                            <option value="{{ $tt->id }}">{{ $tt->treatment_type }} ({{ $tt->treatment_duration }} {{ ucfirst($tt->duration_unit) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top d-flex justify-content-between">
                        <a href="{{ route('admin.patients.index') }}" class="btn btn-default font-weight-bold">
                            <i class="fas fa-times mr-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-danger font-weight-bold shadow-sm">
                            <i class="fas fa-save mr-1"></i> Simpan Data Pasien
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function loadVillages(subdistrictId) {
        if (!subdistrictId) {
            $('#village_id').html('<option value="">-- Pilih Kelurahan/Desa --</option>');
            return;
        }

        $.getJSON('{{ url("/api/kader-area/villages") }}/' + subdistrictId, function (data) {
            let options = '<option value="">-- Pilih Kelurahan/Desa --</option>';
            $.each(data, function (key, item) {
                options += '<option value="' + item.id + '">' + item.name + '</option>';
            });
            $('#village_id').html(options).trigger('change');
        });
    }
</script>
@endpush
