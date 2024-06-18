@extends('layouts.main')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Detail {{ $title }}</h3>
                    </div>
                    <div class="card-body box-profile">
                        <ul class="list-group list-group-unbordered">
                            <li class="list-group-item" style="border-top: none; padding-top: 0px;">
                                <b>Nama Lengkap</b> <span class="float-right">
                                    <a
                                        href="{{ route('user.edit', ['id' => base64_encode($data->user->id)]) }}">{{ $data->user->name }}</a></span>
                            </li>
                            <li class="list-group-item">
                                <b>Email</b> <span class="float-right">{{ $data->user->email }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Ponsel</b> <span class="float-right">{{ $data->user->ponsel ?? '-' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Puskesmas</b> <span class="float-right">
                                    <a
                                        href="{{ route('pkm.edit', ['id' => base64_encode($data->puskesmas->id)]) }}">{{ $data->puskesmas->kode . ' - ' . $data->puskesmas->nama }}</a>
                                </span>
                            </li>
                            <li class="list-group-item">
                                <b>Lokasi</b> <span class="float-right">{{ $data->puskesmas->lokasi ?? '-' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Kecamatan</b> <span class="float-right">{{ $data->puskesmas->subdistrict->subdistrict_name }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Kota/Kab.</b> <span class="float-right">{{ $data->puskesmas->subdistrict->district->district_name }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Provinsi</b> <span class="float-right">{{ $data->puskesmas->subdistrict->district->province->province_name }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('pjtb') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times-circle"></i> Batal
                        </a>
                        <a href="{{ route('pjtb.edit', ['id' => base64_encode($data->id)]) }}" type="button" class="btn btn-primary btn-sm float-right"><i class="fas fa-edit"></i>
                            Edit</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
