@extends('admin.layouts.app')

@section('content')
<!-- Regional Summary Stat Boxes -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $totalProvinces }}</h3>
                <p>Provinsi</p>
            </div>
            <div class="icon">
                <i class="fas fa-map"></i>
            </div>
            <a href="{{ route('admin.regions.index', ['tab' => 'province']) }}" class="small-box-footer">
                Lihat Provinsi <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $totalDistricts }}</h3>
                <p>Kabupaten / Kota</p>
            </div>
            <div class="icon">
                <i class="fas fa-city"></i>
            </div>
            <a href="{{ route('admin.regions.index', ['tab' => 'district']) }}" class="small-box-footer">
                Lihat Kab/Kota <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $totalSubdistricts }}</h3>
                <p>Kecamatan</p>
            </div>
            <div class="icon">
                <i class="fas fa-map-signs"></i>
            </div>
            <a href="{{ route('admin.regions.index', ['tab' => 'subdistrict']) }}" class="small-box-footer">
                Lihat Kecamatan <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-purple" style="background-color: #6f42c1; color: white;">
            <div class="inner">
                <h3>{{ $totalVillages }}</h3>
                <p>Desa / Kelurahan</p>
            </div>
            <div class="icon">
                <i class="fas fa-home" style="color: rgba(255,255,255,0.4);"></i>
            </div>
            <a href="{{ route('admin.regions.index', ['tab' => 'village']) }}" class="small-box-footer" style="color: rgba(255,255,255,0.8);">
                Lihat Desa/Kelurahan <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Tabs & Data Card -->
<div class="card card-outline card-primary card-tabs">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="regionTabs">
            <li class="nav-item">
                <a class="nav-link font-weight-bold {{ $tab == 'subdistrict' ? 'active' : '' }}" href="{{ route('admin.regions.index', ['tab' => 'subdistrict']) }}">
                    <i class="fas fa-map-signs mr-1 text-warning"></i> Kecamatan ({{ $totalSubdistricts }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold {{ $tab == 'village' ? 'active' : '' }}" href="{{ route('admin.regions.index', ['tab' => 'village']) }}">
                    <i class="fas fa-home mr-1 text-purple"></i> Desa / Kelurahan ({{ $totalVillages }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold {{ $tab == 'district' ? 'active' : '' }}" href="{{ route('admin.regions.index', ['tab' => 'district']) }}">
                    <i class="fas fa-city mr-1 text-success"></i> Kabupaten / Kota ({{ $totalDistricts }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold {{ $tab == 'province' ? 'active' : '' }}" href="{{ route('admin.regions.index', ['tab' => 'province']) }}">
                    <i class="fas fa-map mr-1 text-info"></i> Provinsi ({{ $totalProvinces }})
                </a>
            </li>
        </ul>
    </div>
    
    <div class="card-body">
        <!-- Search and Action Bar -->
        <div class="row mb-3">
            <div class="col-md-8">
                <form method="GET" action="{{ route('admin.regions.index') }}" class="form-inline">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="input-group" style="width: 100%; max-width: 450px;">
                        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Cari nama wilayah...">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                            @if($q)
                                <a href="{{ route('admin.regions.index', ['tab' => $tab]) }}" class="btn btn-default"><i class="fas fa-times"></i></a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-right">
                @if($tab == 'subdistrict')
                    <button type="button" class="btn btn-warning font-weight-bold" data-toggle="modal" data-target="#modalAddSubdistrict">
                        <i class="fas fa-plus-circle mr-1"></i> Tambah Kecamatan
                    </button>
                @elseif($tab == 'village')
                    <button type="button" class="btn btn-purple font-weight-bold text-white" style="background-color: #6f42c1;" data-toggle="modal" data-target="#modalAddVillage">
                        <i class="fas fa-plus-circle mr-1"></i> Tambah Desa / Kelurahan
                    </button>
                @endif
            </div>
        </div>

        <!-- TAB CONTENT 1: KECAMATAN -->
        @if($tab == 'subdistrict')
        <div class="table-responsive">
            <table id="subdistrictsTable" class="table table-bordered table-hover data-table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Nama Kecamatan</th>
                        <th>Kabupaten / Kota</th>
                        <th>Provinsi</th>
                        <th class="text-center">Desa / Kel</th>
                        <th class="text-center">Faskes PKM</th>
                        <th class="text-center">Pasien TB</th>
                        <th class="text-center">Total Skrining</th>
                        <th style="width: 100px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subdistricts as $idx => $sub)
                    <tr>
                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                        <td>
                            <strong class="text-dark"><i class="fas fa-map-marker-alt text-warning mr-1"></i>{{ $sub->name }}</strong>
                        </td>
                        <td>{{ $sub->district->name ?? '-' }}</td>
                        <td>{{ $sub->district->province->name ?? '-' }}</td>
                        <td class="text-center" data-order="{{ $sub->villages_count }}"><span class="badge badge-secondary px-2 py-1">{{ $sub->villages_count }} Desa</span></td>
                        <td class="text-center" data-order="{{ $sub->puskesmas_count }}"><span class="badge badge-teal px-2 py-1 text-white" style="background-color: #20c997;">{{ $sub->puskesmas_count }} Puskesmas</span></td>
                        <td class="text-center" data-order="{{ $sub->patients_count }}"><span class="badge badge-warning px-2 py-1">{{ $sub->patients_count }} Pasien</span></td>
                        <td class="text-center" data-order="{{ $sub->screenings_count }}"><span class="badge badge-info px-2 py-1">{{ $sub->screenings_count }} Skrining</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('admin.regions.subdistricts.destroy', $sub->id) }}', 'Kecamatan {{ $sub->name }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- TAB CONTENT 2: DESA / KELURAHAN -->
        @elseif($tab == 'village')
        <div class="table-responsive">
            <table id="villagesTable" class="table table-bordered table-hover data-table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Nama Desa / Kelurahan</th>
                        <th>Kecamatan</th>
                        <th>Kabupaten / Kota</th>
                        <th>Provinsi</th>
                        <th class="text-center">Pasien TB</th>
                        <th style="width: 100px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($villages as $idx => $vil)
                    <tr>
                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                        <td>
                            <strong class="text-dark"><i class="fas fa-home text-purple mr-1"></i>{{ $vil->name }}</strong>
                        </td>
                        <td>{{ $vil->subdistrict->name ?? '-' }}</td>
                        <td>{{ $vil->subdistrict->district->name ?? '-' }}</td>
                        <td>{{ $vil->subdistrict->district->province->name ?? '-' }}</td>
                        <td class="text-center" data-order="{{ $vil->patients_count }}"><span class="badge badge-warning px-2 py-1">{{ $vil->patients_count }} Pasien</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('admin.regions.villages.destroy', $vil->id) }}', 'Desa {{ $vil->name }}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- TAB CONTENT 3: KABUPATEN / KOTA -->
        @elseif($tab == 'district')
        <div class="table-responsive">
            <table id="districtsTable" class="table table-bordered table-hover data-table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Nama Kabupaten / Kota</th>
                        <th>Provinsi</th>
                        <th class="text-center">Jumlah Kecamatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($districts as $idx => $dist)
                    <tr>
                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                        <td><strong class="text-dark"><i class="fas fa-city text-success mr-1"></i>{{ $dist->name }}</strong></td>
                        <td>{{ $dist->province->name ?? '-' }}</td>
                        <td class="text-center" data-order="{{ $dist->subdistricts_count }}"><span class="badge badge-primary px-2 py-1">{{ $dist->subdistricts_count }} Kecamatan</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- TAB CONTENT 4: PROVINSI -->
        @elseif($tab == 'province')
        <div class="table-responsive">
            <table id="provincesTable" class="table table-bordered table-hover data-table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Nama Provinsi</th>
                        <th class="text-center">Jumlah Kab / Kota</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($provinces as $idx => $prov)
                    <tr>
                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                        <td><strong class="text-dark"><i class="fas fa-map text-info mr-1"></i>{{ $prov->name }}</strong></td>
                        <td class="text-center" data-order="{{ $prov->districts_count }}"><span class="badge badge-success px-2 py-1">{{ $prov->districts_count }} Kab/Kota</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Kecamatan -->
<div class="modal fade" id="modalAddSubdistrict" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.regions.subdistricts.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-map-signs mr-1"></i> Tambah Kecamatan Baru</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kabupaten / Kota <span class="text-danger">*</span></label>
                        <select name="district_id" class="form-control select2" required style="width: 100%;">
                            @foreach($allDistricts as $dist)
                                <option value="{{ $dist->id }}">{{ $dist->name }} (Prov. {{ $dist->province->name ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Kecamatan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Sukaraja" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i> Simpan Kecamatan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Desa -->
<div class="modal fade" id="modalAddVillage" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('admin.regions.villages.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-purple text-white" style="background-color: #6f42c1;">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-home mr-1"></i> Tambah Desa / Kelurahan Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kecamatan Induk <span class="text-danger">*</span></label>
                        <select name="subdistrict_id" class="form-control select2" required style="width: 100%;">
                            @foreach($allSubdistricts as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->district->name ?? '-' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Desa / Kelurahan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Margaluyu" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-purple font-weight-bold text-white" style="background-color: #6f42c1;"><i class="fas fa-save mr-1"></i> Simpan Desa</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form id="delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
function confirmDelete(url, name) {
    Swal.fire({
        title: 'Hapus Wilayah?',
        html: `Apakah Anda yakin ingin menghapus <b>${name}</b>?<br><small class="text-danger">Peringatan: Wilayah yang memiliki relasi data pasien/faskes tidak boleh dihapus sembarangan.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            let form = document.getElementById('delete-form');
            form.action = url;
            form.submit();
        }
    });
}
</script>
@endpush
