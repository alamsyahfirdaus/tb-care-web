@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            @if (Auth::id() != $user->id)
                <div class="card-header py-2">
                    <h3 class="card-title pt-1">{{ 'Data ' . $title }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('user.list', ['id' => base64_encode($user_type_id)]) }}"
                            class="btn btn-primary btn-sm" title="Sebelumnya">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </div>

                </div>
            @endif
            <div class="row">
                <div class="col-md-3">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img class="profile-user-img img-fluid img-circle"
                                src="{{ $user->profile ? asset('upload_images/' . $user->profile) : asset('assets/img/profile.png') }}"
                                alt="{{ $user->username }}">
                        </div>
                        <h3 class="profile-username text-center">
                            {{ $user->name }}</h3>
                        <p class="text-muted text-center">
                            {{ $user->userType->name }}</p>
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item" style="border-bottom: none;">
                                @php
                                    $route =
                                        $user->id == Auth::id()
                                            ? route('profile.edit', ['id' => base64_encode($user->id)])
                                            : route('user.edit', ['id' => base64_encode($user->id)]);
                                    $buttonText = $user->id == Auth::id() ? 'Edit Profil' : 'Edit Data ' . $title;
                                @endphp

                                <a href="{{ $route }}" type="button"
                                    class="btn btn-outline-primary btn-block btn-sm">
                                    <i class="fas fa-user-edit"></i> {{ $buttonText }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="card-body">
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="background-color: #fff;"><i
                                            class="fas fa-filter"></i></span>
                                </div>
                                <select name="user_id" id="user_id" class="form-control select2"
                                    {{ $user_type_id == 1 ? 'disabled="disabled"' : '' }}>
                                    @foreach ($users as $item)
                                        <option value="{{ base64_encode($item->id) }}"
                                            {{ $user->id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }} ({{ $item->username }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <ul class="list-group list-group-unbordered">
                            @php
                                $listData = [
                                    'Nama' => $user->name ?? '-',
                                    'Email' => $user->email ?? '-',
                                    'Telepon/HP' => $user->phone,
                                    'Jenis Kelamin' => $user->gender ?? '-',
                                    'Tempat, Tanggal Lahir' =>
                                        $user->place_of_birth && $user->date_of_birth
                                            ? $user->place_of_birth .
                                                ', ' .
                                                \App\Helpers\DateHelper::convertDate($user->date_of_birth)
                                            : '-',
                                ];
                            @endphp
                            @foreach ($listData as $key => $value)
                                <li class="list-group-item">
                                    <span>{{ $key }}</span> <span class="float-right">{{ $value }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @if (($user_type_id != 1 && $user_type_id == 4 && $user->id != Auth::id()) || $user_type_id == 2 || $user_type_id == 3)
            <div class="card card-primary card-outline">
                <div class="card-header py-2">
                    <h3 class="card-title pt-1 card-title-patient">Detail {{ $title }}</h3>
                    <div class="card-tools">
                        @php
                            $routes = [
                                2 => 'ho.edit',
                                3 => 'coord.edit',
                                4 => 'patient.edit',
                            ];
                        @endphp
                        @if (isset($routes[$user_type_id]))
                            <a href="{{ route($routes[$user_type_id], ['id' => base64_encode($user_detail_id)]) }}"
                                class="btn btn-primary btn-sm" title="Edit Detail {{ $title }}">
                                <i class="fas fa-edit"></i>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-unbordered">
                        @foreach ($user_detail as $key => $value)
                            <li class="list-group-item"
                                @if ($loop->first) style="border-top: 0px; padding-top: 0px;" @endif>
                                <span>{{ $key }}</span>
                                <span class="float-right">{{ $value }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (isset($officer) && $officer && $officer->isKader())
            <div class="card card-success card-outline mt-3">
                <div class="card-header py-2">
                    <h3 class="card-title pt-1 font-weight-bold">
                        <i class="fas fa-map-marked-alt mr-1"></i> Wilayah Binaan Kader
                    </h3>
                    @if (!empty($can_manage_kader_area))
                        <div class="card-tools">
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal-add-area">
                                <i class="fas fa-plus mr-1"></i> Tambah Wilayah Binaan
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 50px; text-align: center;">No</th>
                                    <th>Kecamatan</th>
                                    <th>Desa / Kelurahan</th>
                                    <th>RW</th>
                                    <th>RT</th>
                                    @if (!empty($can_manage_kader_area))
                                        <th style="width: 100px; text-align: center;">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($kader_areas as $index => $area)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td>{{ optional($area->subdistrict)->name ?? '-' }}</td>
                                        <td><strong>{{ optional($area->village)->name ?? '-' }}</strong></td>
                                        <td><span class="badge badge-info">RW {{ $area->rw }}</span></td>
                                        <td>
                                            @if ($area->rt)
                                                <span class="badge badge-secondary">RT {{ $area->rt }}</span>
                                            @else
                                                <span class="badge badge-success">Semua RT (RW Binaan Penuh)</span>
                                            @endif
                                        </td>
                                        @if (!empty($can_manage_kader_area))
                                            <td class="text-center">
                                                <form action="{{ route('kader-area.delete', ['id' => $area->id]) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus wilayah binaan ini?');" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-xs" title="Hapus Wilayah">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ !empty($can_manage_kader_area) ? 6 : 5 }}" class="text-center text-muted py-4">
                                            <em>Belum ada wilayah binaan yang ditetapkan untuk Kader ini.</em>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if (!empty($can_manage_kader_area))
                {{-- Modal Tambah Wilayah Binaan --}}
                <div class="modal fade" id="modal-add-area" tabindex="-1" role="dialog" aria-labelledby="modalAddAreaLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form action="{{ route('kader-area.store') }}" method="POST" id="form-add-area">
                                @csrf
                                <input type="hidden" name="officer_id" value="{{ $officer->id }}">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title" id="modalAddAreaLabel"><i class="fas fa-map-marker-alt mr-1"></i> Tambah Wilayah Binaan</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="area_subdistrict_id">Kecamatan <span class="text-danger">*</span></label>
                                        <select class="form-control" name="subdistrict_id" id="area_subdistrict_id" required>
                                            <option value="">-- Pilih Kecamatan --</option>
                                            @foreach ($subdistricts as $sub)
                                                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="area_village_id">Desa / Kelurahan <span class="text-danger">*</span></label>
                                        <select class="form-control" name="village_id" id="area_village_id" required disabled>
                                            <option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="area_rw">RW <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="rw" id="area_rw" placeholder="Contoh: 05" maxlength="5" required>
                                        <small class="form-text text-muted">Nomor RW wajib diisi.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="area_rt">RT <span class="text-muted">(Opsional)</span></label>
                                        <input type="text" class="form-control" name="rt" id="area_rt" placeholder="Contoh: 01" maxlength="5">
                                        <small class="form-text text-muted">Kosongkan jika kader membina seluruh RT dalam RW tersebut.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i> Simpan Wilayah</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    $(document).ready(function() {
                        $('#area_subdistrict_id').on('change', function() {
                            var subdistrictId = $(this).val();
                            var $villageSelect = $('#area_village_id');
                            $villageSelect.empty().prop('disabled', true);
                            $villageSelect.append('<option value="">Memuat desa/kelurahan...</option>');

                            if (subdistrictId) {
                                $.ajax({
                                    url: '{{ url("kader-area/villages") }}/' + subdistrictId,
                                    type: 'GET',
                                    dataType: 'json',
                                    success: function(data) {
                                        $villageSelect.empty();
                                        $villageSelect.append('<option value="">-- Pilih Desa/Kelurahan --</option>');
                                        $.each(data, function(index, village) {
                                            $villageSelect.append('<option value="' + village.id + '">' + village.name + '</option>');
                                        });
                                        $villageSelect.prop('disabled', false);
                                    },
                                    error: function() {
                                        $villageSelect.empty().append('<option value="">Gagal memuat data desa</option>');
                                    }
                                });
                            } else {
                                $villageSelect.empty().append('<option value="">-- Pilih Kecamatan Terlebih Dahulu --</option>');
                            }
                        });
                    });
                </script>
            @endif
        @endif
    </div>

    <script>
        $('#user_id').change(function() {
            var userId = $(this).val();
            if (userId) {
                var url = '{{ route('user.show', ['id' => ':id']) }}';
                window.location.href = url.replace(':id', userId);
            }
        });
    </script>
@endsection
