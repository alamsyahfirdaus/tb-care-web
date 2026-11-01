@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('kaders.index') }}">Manajemen Kader</a></li>
    <li class="breadcrumb-item active">{{ $isEdit ? 'Edit Kader' : 'Tambah Kader' }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas {{ $isEdit ? 'fa-user-edit' : 'fa-user-plus' }} mr-1 text-primary"></i>
                        {{ $isEdit ? 'Formulir Perbarui Data Akun Kader' : 'Formulir Registrasi Kader Kesehatan TB' }}
                    </h3>
                </div>

                <form action="{{ $isEdit ? route('kaders.update', $kader->encrypted_id) : route('kaders.store') }}" method="POST">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <div class="card-body">
                        <!-- Section 1: Identitas Diri -->
                        <h6 class="font-weight-bold text-primary mb-3">
                            <i class="fas fa-id-card mr-1"></i> Data Identitas Kader
                        </h6>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $kader->name) }}" placeholder="Contoh: Siti Rahmawati" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label for="username">Username Akun <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">@</span>
                                    </div>
                                    <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror"
                                           value="{{ old('username', $kader->username) }}" placeholder="Contoh: sitirahma" required>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="form-text text-muted">Digunakan untuk login pada aplikasi TB Care Mobile.</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="phone">No. Handphone / WhatsApp</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', $kader->phone) }}" placeholder="Contoh: 081234567890">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="email">Alamat Email</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                           value="{{ old('email', $kader->email) }}" placeholder="Contoh: kader.siti@gmail.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="gender">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="gender" id="gender" class="form-control select2 @error('gender') is-invalid @enderror" required>
                                    <option value="P" {{ old('gender', $kader->gender ?? 'P') == 'P' ? 'selected' : '' }}>Perempuan</option>
                                    <option value="L" {{ old('gender', $kader->gender) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="place_of_birth">Tempat Lahir</label>
                                <input type="text" name="place_of_birth" id="place_of_birth" class="form-control @error('place_of_birth') is-invalid @enderror"
                                       value="{{ old('place_of_birth', $kader->place_of_birth) }}" placeholder="Kota tempat lahir">
                                @error('place_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label for="date_of_birth">Tanggal Lahir</label>
                                <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                                       value="{{ old('date_of_birth', $kader->date_of_birth ? Carbon\Carbon::parse($kader->date_of_birth)->format('Y-m-d') : '') }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <!-- Section 2: Penugasan Puskesmas & Wilayah Binaan -->
                        <h6 class="font-weight-bold text-primary mb-3">
                            <i class="fas fa-map-marked-alt mr-1"></i> Penugasan Puskesmas & Desa Binaan
                        </h6>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="puskesmas_id">Puskesmas Penugasan <span class="text-danger">*</span></label>
                                @if($currentUser->isPJTB())
                                    @php
                                        $pjtbPuskesmas = $currentUser->officer->puskesmas;
                                    @endphp
                                    <input type="text" class="form-control bg-light font-weight-bold"
                                           value="{{ $pjtbPuskesmas->name ?? 'Puskesmas Penugasan' }}" readonly>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-lock mr-1 text-secondary"></i> Penugasan otomatis dikunci sesuai wilayah kerja Puskesmas Anda.
                                    </small>
                                @else
                                    <select name="puskesmas_id" id="puskesmas_id" class="form-control select2 @error('puskesmas_id') is-invalid @enderror" required>
                                        <option value="">-- Pilih Puskesmas --</option>
                                        @foreach($puskesmasList as $pkm)
                                            <option value="{{ $pkm->id }}" {{ old('puskesmas_id', optional($kader->officer)->puskesmas_id) == $pkm->id ? 'selected' : '' }}>
                                                {{ $pkm->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('puskesmas_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                @endif
                            </div>

                            <div class="form-group col-md-6">
                                <label for="is_active">Status Akun <span class="text-danger">*</span></label>
                                <select name="is_active" id="is_active" class="form-control select2 @error('is_active') is-invalid @enderror" required>
                                    <option value="1" {{ old('is_active', $kader->is_active ?? 1) == 1 ? 'selected' : '' }}>
                                        Aktif (Dapat Login ke TB Care Mobile)
                                    </option>
                                    <option value="0" {{ old('is_active', $kader->is_active ?? 1) == 0 ? 'selected' : '' }}>
                                        Nonaktif (Akses Mobile Ditangguhkan)
                                    </option>
                                </select>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Pilih Desa/Kelurahan Binaan -->
                        <div class="form-group mb-3">
                            <label class="d-block font-weight-bold">
                                Desa / Kelurahan Binaan
                                <span class="badge badge-info ml-1">Penting</span>
                            </label>
                            <p class="text-xs text-muted mb-2">
                                Centang desa/kelurahan yang menjadi wilayah kerja kader ini. Kader <strong>hanya dapat melihat dan mendampingi pasien</strong> yang berdomisili di desa yang dipilih. Jika tidak ada desa yang dipilih, akses pasien akan otomatis ditutup (<em>fail-closed</em>).
                            </p>

                            <div class="border rounded p-3 bg-light" style="max-height: 250px; overflow-y: auto;">
                                <div class="row">
                                    @forelse($villages as $village)
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="village_ids[]" value="{{ $village->id }}"
                                                       id="village_{{ $village->id }}" class="custom-control-input"
                                                       {{ in_array($village->id, old('village_ids', $assignedVillageIds)) ? 'checked' : '' }}>
                                                <label class="custom-control-label font-weight-normal cursor-pointer" for="village_{{ $village->id }}">
                                                    {{ $village->name }}
                                                    @if($village->subdistrict)
                                                        <small class="text-muted d-block">Kec. {{ $village->subdistrict->name }}</small>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted">
                                            <em>Tidak ada desa/kelurahan yang tersedia untuk Puskesmas ini.</em>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                            @error('village_ids')
                                <div class="text-danger text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <!-- Section 3: Keamanan & Kata Sandi -->
                        <h6 class="font-weight-bold text-primary mb-3">
                            <i class="fas fa-lock mr-1"></i> Keamanan & Kata Sandi (Password)
                        </h6>

                        @if($isEdit)
                            <div class="alert alert-light border py-2 text-xs text-muted mb-3">
                                <i class="fas fa-info-circle mr-1 text-info"></i>
                                Kosongkan kolom kata sandi jika tidak ingin mereset password kader ini. Mereset password akan otomatis mencabut seluruh sesi login mobile sebelumnya.
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
                                <label for="password_confirmation">Konfirmasi Kata Sandi {{ $isEdit ? '(Opsional)' : '*' }}</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                                       placeholder="Ketik ulang kata sandi" {{ $isEdit ? '' : 'required' }}>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-between">
                        <a href="{{ $isEdit ? route('kaders.show', $kader->encrypted_id) : route('kaders.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary font-weight-bold shadow-sm">
                            <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Daftarkan Kader' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
