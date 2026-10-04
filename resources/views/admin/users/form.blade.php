@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Pengguna</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? 'Edit Akun' : 'Tambah Akun' }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas {{ $isEdit ? 'fa-user-edit' : 'fa-user-plus' }} mr-1 text-primary"></i>
                        {{ $isEdit ? 'Formulir Perbarui Data Akun Pengguna' : 'Formulir Pendaftaran Akun Pengguna Baru' }}
                    </h3>
                </div>
                <form action="{{ $isEdit ? route('admin.users.update', $user->encrypted_id) : route('admin.users.store') }}" method="POST">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                        <input type="hidden" name="encrypted_id" value="{{ $user->encrypted_id }}">
                    @endif
                    <div class="card-body">
                        <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-id-card mr-1"></i> Data Identitas Pengguna</h6>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $user->name) }}" placeholder="Contoh: Budi Santoso" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="username">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" 
                                       value="{{ old('username', $user->username) }}" placeholder="Contoh: budisantoso12" required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="email">Alamat Email</label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $user->email) }}" placeholder="Contoh: budi@gmail.com">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="phone">No. Handphone / WhatsApp</label>
                                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $user->phone) }}" placeholder="Contoh: 081234567890">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="gender">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" id="gender" class="form-control select2 @error('gender') is-invalid @enderror" required>
                                    <option value="L" {{ old('gender', $user->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ old('gender', $user->gender) == 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="place_of_birth">Tempat Lahir</label>
                                <input type="text" name="place_of_birth" id="place_of_birth" class="form-control @error('place_of_birth') is-invalid @enderror" 
                                       value="{{ old('place_of_birth', $user->place_of_birth) }}" placeholder="Kota kelahiran">
                                @error('place_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label for="date_of_birth">Tanggal Lahir</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       value="{{ old('date_of_birth', $user->date_of_birth ? Carbon\Carbon::parse($user->date_of_birth)->format('Y-m-d') : '') }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-user-tag mr-1"></i> Peran & Hak Akses Akun</h6>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="user_type_id">Peran Sistem <span class="text-danger">*</span></label>
                                <select name="user_type_id" id="user_type_id" class="form-control select2 @error('user_type_id') is-invalid @enderror" required onchange="toggleOfficerSection(this.value)">
                                    @foreach($userTypes as $ut)
                                        <option value="{{ $ut->id }}" {{ old('user_type_id', $user->user_type_id) == $ut->id ? 'selected' : '' }}>
                                            {{ $ut->name }} - {{ $ut->description }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="is_active">Status Akun <span class="text-danger">*</span></label>
                                <select name="is_active" id="is_active" class="form-control select2 @error('is_active') is-invalid @enderror" required>
                                    <option value="1" {{ old('is_active', $user->is_active ?? 1) == 1 ? 'selected' : '' }}>Aktif (Dapat Login)</option>
                                    <option value="0" {{ old('is_active', $user->is_active ?? 1) == 0 ? 'selected' : '' }}>Nonaktif (Diblokir)</option>
                                </select>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Officer Specific Assignment Block -->
                        <div id="officer-section" style="display: {{ old('user_type_id', $user->user_type_id) == 3 ? 'block' : 'none' }};" class="p-3 mb-3 bg-light rounded border">
                            <h6 class="font-weight-bold text-success mb-2"><i class="fas fa-stethoscope mr-1"></i> Konfigurasi Penugasan Petugas Kesehatan</h6>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="officer_type_id">Tipe Petugas</label>
                                    <select name="officer_type_id" id="officer_type_id" class="form-control select2">
                                        <option value="4" {{ old('officer_type_id', optional($user->officer)->officer_type_id) == 4 ? 'selected' : '' }}>Kader Kesehatan Puskesmas</option>
                                        <option value="3" {{ old('officer_type_id', optional($user->officer)->officer_type_id) == 3 ? 'selected' : '' }}>PJTB Puskesmas</option>
                                        <option value="2" {{ old('officer_type_id', optional($user->officer)->officer_type_id) == 2 ? 'selected' : '' }}>Admin Dinkes Kab/Kota</option>
                                        <option value="1" {{ old('officer_type_id', optional($user->officer)->officer_type_id) == 1 ? 'selected' : '' }}>Admin Dinkes Provinsi</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="puskesmas_id">Puskesmas Tugas</label>
                                    <select name="puskesmas_id" id="puskesmas_id" class="form-control select2">
                                        <option value="">-- Pilih Puskesmas --</option>
                                        @foreach($puskesmas as $pkm)
                                            <option value="{{ $pkm->id }}" {{ old('puskesmas_id', optional($user->officer)->puskesmas_id) == $pkm->id ? 'selected' : '' }}>
                                                {{ $pkm->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="district_id">Wilayah Dinkes (Kab/Kota)</label>
                                    <select name="district_id" id="district_id" class="form-control select2">
                                        <option value="">-- Pilih Kab/Kota --</option>
                                        @foreach($districts as $d)
                                            <option value="{{ $d->id }}" {{ old('district_id', optional($user->officer)->district_id) == $d->id ? 'selected' : '' }}>
                                                {{ $d->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-primary mb-3"><i class="fas fa-lock mr-1"></i> Kata Sandi (Password)</h6>
                        @if($isEdit)
                            <div class="alert alert-light border py-2 text-xs text-muted mb-3">
                                <i class="fas fa-info-circle mr-1"></i> Kosongkan kata sandi jika tidak ingin mengubah password akun ini.
                            </div>
                        @endif
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">Kata Sandi {{ $isEdit ? '(Opsional)' : '*' }}</label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" 
                                       placeholder="Minimal 6 karakter" {{ $isEdit ? '' : 'required' }}>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="password_confirmation">Konfirmasi Kata Sandi {{ $isEdit ? '' : '*' }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" 
                                       placeholder="Ketik ulang kata sandi" {{ $isEdit ? '' : 'required' }}>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-between">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-default font-weight-bold">
                            <i class="fas fa-times mr-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary font-weight-bold shadow-sm">
                            <i class="fas fa-save mr-1"></i> Simpan Data Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function toggleOfficerSection(userTypeId) {
        if (userTypeId == '3') {
            $('#officer-section').slideDown();
        } else {
            $('#officer-section').slideUp();
        }
    }
</script>
@endpush
