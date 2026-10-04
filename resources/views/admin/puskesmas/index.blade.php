@extends('admin.layouts.app')

@section('content')
<div class="row">
    <!-- Stat Box 1 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $puskesmas->count() }}</h3>
                <p>Total Faskes Terdata</p>
            </div>
            <div class="icon">
                <i class="fas fa-hospital-alt"></i>
            </div>
        </div>
    </div>
    <!-- Stat Box 2 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ \App\Models\Officer::count() }}</h3>
                <p>Petugas & PJTB</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-nurse"></i>
            </div>
        </div>
    </div>
    <!-- Stat Box 3 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ \App\Models\Patient::whereNotNull('puskesmas_id')->count() }}</h3>
                <p>Pasien Terhubung Faskes</p>
            </div>
            <div class="icon">
                <i class="fas fa-procedures"></i>
            </div>
        </div>
    </div>
    <!-- Stat Box 4 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ \App\Models\Screening::whereNotNull('puskesmas_id')->count() }}</h3>
                <p>Skrining Faskes</p>
            </div>
            <div class="icon">
                <i class="fas fa-clipboard-check"></i>
            </div>
        </div>
    </div>
</div>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter & Pencarian Faskes</h3>
        <div class="card-tools">
            <a href="{{ route('admin.puskesmas.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Faskes Baru
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.puskesmas.index') }}" class="row">
            <div class="col-md-5 mb-2">
                <label class="small text-muted font-weight-bold">Kecamatan</label>
                <select name="subdistrict_id" class="form-control select2">
                    <option value="">-- Semua Kecamatan --</option>
                    @foreach($subdistricts as $sub)
                        <option value="{{ $sub->id }}" {{ $subdistrictFilter == $sub->id ? 'selected' : '' }}>
                            {{ $sub->name }} ({{ $sub->district->name ?? 'Tasikmalaya' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 mb-2">
                <label class="small text-muted font-weight-bold">Pencarian Nama / Kode / Alamat</label>
                <div class="input-group">
                    <input type="text" name="q" value="{{ $keyword }}" class="form-control" placeholder="Cari nama Puskesmas, kode, atau alamat...">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
                <a href="{{ route('admin.puskesmas.index') }}" class="btn btn-default btn-block">
                    <i class="fas fa-undo mr-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card card-outline card-teal">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold mb-0">
            <i class="fas fa-clinic-medical mr-1 text-teal"></i> {{ $pageTitle }} <span class="badge badge-light border ml-2">Total: {{ $puskesmas->count() }} Faskes</span>
        </h3>
        <a href="{{ route('admin.puskesmas.create') }}" class="btn btn-sm btn-teal font-weight-bold shadow-sm text-white" style="background-color: #20c997;">
            <i class="fas fa-plus mr-1"></i> Tambah Faskes
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="puskesmasTable" class="table table-bordered table-hover data-table align-middle w-100 mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Kode Faskes</th>
                        <th>Nama Fasilitas Kesehatan</th>
                        <th>Wilayah Binaan</th>
                        <th class="text-center">Petugas / PJTB</th>
                        <th class="text-center">Pasien TB</th>
                        <th class="text-center">Total Skrining</th>
                        <th style="width: 140px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($puskesmas as $idx => $pkm)
                    <tr>
                        <td class="text-center text-muted font-weight-bold">
                            {{ $loop->iteration }}
                        </td>
                        <td>
                            <span class="badge badge-light border px-2 py-1 font-weight-bold text-teal">
                                <i class="fas fa-barcode mr-1"></i>{{ $pkm->code ?? ('PKM-' . str_pad($pkm->id, 3, '0', STR_PAD_LEFT)) }}
                            </span>
                        </td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $pkm->name }}</div>
                            <small class="text-muted"><i class="fas fa-map-marker-alt text-danger mr-1"></i>{{ $pkm->address ?: 'Alamat belum diatur' }}</small>
                        </td>
                        <td>
                            <div><i class="fas fa-map-signs text-muted mr-1"></i>{{ $pkm->subdistrict->name ?? 'Kecamatan -' }}</div>
                            <small class="text-muted">
                                {{ $pkm->subdistrict->district->name ?? 'Kabupaten/Kota -' }}, 
                                {{ $pkm->subdistrict->district->province->name ?? 'Provinsi -' }}
                            </small>
                        </td>
                        <td class="text-center" data-order="{{ $pkm->officers_count }}">
                            <span class="badge badge-success px-2 py-1">
                                <i class="fas fa-user-nurse mr-1"></i>{{ $pkm->officers_count }} Petugas
                            </span>
                        </td>
                        <td class="text-center" data-order="{{ $pkm->patients_count }}">
                            <span class="badge badge-warning px-2 py-1">
                                <i class="fas fa-procedures mr-1"></i>{{ $pkm->patients_count }} Pasien
                            </span>
                        </td>
                        <td class="text-center" data-order="{{ $pkm->screenings_count }}">
                            <span class="badge badge-info px-2 py-1">
                                <i class="fas fa-clipboard-check mr-1"></i>{{ $pkm->screenings_count }} Data
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.puskesmas.show', $pkm->encrypted_id) }}" class="btn btn-info" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.puskesmas.edit', $pkm->encrypted_id) }}" class="btn btn-warning" title="Edit Faskes">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <button type="button" class="btn btn-danger" title="Hapus Faskes" onclick="confirmDelete('{{ route('admin.puskesmas.destroy', $pkm->encrypted_id) }}', '{{ $pkm->name }}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
        title: 'Hapus Fasilitas Kesehatan?',
        html: `Apakah Anda yakin ingin menghapus <b>${name}</b>?<br><small class="text-danger">Peringatan: Relasi petugas dan data wilayah akan terpengaruh.</small>`,
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
