@extends('admin.layouts.app')

@section('content')
<div class="row">
    <!-- Left Column: User Profile Box -->
    <div class="col-md-4">
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-body box-profile text-center">
                <div class="mb-3">
                    <img class="profile-user-img img-fluid img-circle"
                         src="https://ui-avatars.com/api/?name={{ urlencode($user->name ?? 'Admin') }}&background=007bff&color=fff&size=128"
                         alt="User profile picture">
                </div>
                <h3 class="profile-username font-weight-bold text-dark">{{ $user->name ?? 'Administrator' }}</h3>
                <p class="text-muted"><span class="badge badge-primary px-2 py-1">{{ $user->role->name ?? 'Administrator' }}</span></p>

                <ul class="list-group list-group-unbordered mb-3 text-left">
                    <li class="list-group-item">
                        <b>Username</b> <span class="float-right text-dark font-weight-bold">{{ $user->username }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Email Resmi</b> <span class="float-right text-muted">{{ $user->email }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>No. Telepon</b> <span class="float-right text-muted">{{ $user->phone_number ?: '-' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Status Akun</b> <span class="float-right badge badge-success">Aktif / Terverifikasi</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Settings Tabs -->
    <div class="col-md-8">
        <div class="card card-outline card-primary card-tabs shadow-sm">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="settingsTabs">
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold {{ $tab == 'profile' ? 'active' : '' }}" href="{{ route('admin.settings.index', ['tab' => 'profile']) }}">
                            <i class="fas fa-user-cog mr-1 text-primary"></i> Edit Profil & Kata Sandi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold {{ $tab == 'roles' ? 'active' : '' }}" href="{{ route('admin.settings.index', ['tab' => 'roles']) }}">
                            <i class="fas fa-shield-alt mr-1 text-success"></i> Hak Akses & Role ({{ $userTypes->count() }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold {{ $tab == 'system' ? 'active' : '' }}" href="{{ route('admin.settings.index', ['tab' => 'system']) }}">
                            <i class="fas fa-sliders-h mr-1 text-info"></i> Konfigurasi Sistem
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                @if($tab == 'profile')
                    <form action="{{ route('admin.settings.profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <ul class="mb-0">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">Nama Lengkap <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">Alamat Email <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">Nomor Handphone</label>
                            <div class="col-sm-9">
                                <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $user->phone_number) }}">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-lock mr-1 text-warning"></i> Ganti Kata Sandi (Kosongkan jika tidak diubah)</h6>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Kata Sandi Baru</label>
                            <div class="col-sm-9">
                                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter...">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Konfirmasi Kata Sandi</label>
                            <div class="col-sm-9">
                                <input type="password" name="password_confirmation" class="form-control" placeholder="Ketik ulang kata sandi baru...">
                            </div>
                        </div>

                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>

                @elseif($tab == 'roles')
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle mr-1"></i> Matriks tingkat hak akses pengguna pada ekosistem aplikasi web dan mobile TB Care.
                    </div>
                    <div class="table-responsive">
                        <table id="rolesTable" class="table table-bordered table-hover data-table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">No</th>
                                    <th>ID</th>
                                    <th>Nama Role</th>
                                    <th>Deskripsi Hak Akses</th>
                                    <th class="text-center">Jumlah Pengguna</th>
                                    <th class="text-center">Akses Portal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($userTypes as $ut)
                                <tr>
                                    <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                    <td><code>#{{ $ut->id }}</code></td>
                                    <td>
                                        <strong class="text-dark">{{ $ut->name }}</strong>
                                    </td>
                                    <td>
                                        @if($ut->id == 1)
                                            <span class="text-muted small">Akses menyeluruh seluruh modul sistem, data master, faskes, log, dan pengguna.</span>
                                        @elseif($ut->id == 2)
                                            <span class="text-muted small">Petugas Dinas Kesehatan (Kab/Prov) untuk pemantauan data agregat wilayah.</span>
                                        @elseif($ut->id == 3)
                                            <span class="text-muted small">Penanggung Jawab TB (PJTB) Puskesmas & Kader untuk pemantauan pasien wilayah.</span>
                                        @else
                                            <span class="text-muted small">Masyarakat umum dan pasien TB melalui aplikasi Android / mobile.</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-weight-bold" data-order="{{ $ut->users_count }}">
                                        <span class="badge badge-light border">{{ $ut->users_count }} Akun</span>
                                    </td>
                                    <td class="text-center">
                                        @if($ut->id <= 3)
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-desktop mr-1"></i>Admin & Web</span>
                                        @else
                                            <span class="badge badge-info px-2 py-1"><i class="fas fa-mobile-alt mr-1"></i>Mobile App</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @else
                    <!-- System Config -->
                    <form action="{{ route('admin.settings.system.update') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">Nama Aplikasi</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="TB Care - Sistem Informasi Tuberkulosis" readonly>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">Wilayah Fokus Implementasi</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="Kabupaten Tasikmalaya, Jawa Barat" readonly>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">Ambang Skor Risiko Tinggi</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" value="7" readonly>
                                <small class="text-muted">Skor &ge; 7 atau terdapat batuk &ge; 2 minggu otomatis dikategorikan Risiko Tinggi.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label font-weight-bold">Hotline WhatsApp Konsultasi</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="+62 821-2345-6789">
                            </div>
                        </div>

                        <div class="text-right mt-4">
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                                <i class="fas fa-save mr-1"></i> Simpan Konfigurasi
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
