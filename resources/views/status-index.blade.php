@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Daftar {{ $title }}</h3>
                <div class="card-tools">
                    <a href="javascript:void(0)" onclick="addData();" class="btn btn-tool" title="Tambah {{ $title }}">
                        <i class="fas fa-plus"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="datatable" class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 5%; text-align: center;">No</th>
                                <th>Status Pengobatan</th>
                                <th style="width: 5%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($status as $key => $item)
                                <tr>
                                    <td style="text-align: center;">{{ $key + 1 }}</td>
                                    <td>{{ $item->nama }}</td>
                                    <td style="text-align: center;">
                                        <div style="display: none;">
                                            <input type="text" name="{{ 'id_' . md5($item->id) }}"
                                                value="{{ base64_encode($item->id) }}">
                                            <input type="text" name="{{ 'nama_' . md5($item->id) }}"
                                                value="{{ $item->nama }}">
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle"
                                                data-toggle="dropdown">Aksi </button>
                                            <div class="dropdown-menu" role="menu">
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="editData('{{ md5($item->id) }}')">Edit</a>
                                                <div class="dropdown-divider"></div>
                                                {!! Form::open([
                                                    'route' => ['status.delete', base64_encode($item->id)],
                                                    'method' => 'DELETE',
                                                    'id' => 'remove-' . md5($item->id),
                                                ]) !!}
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                    onclick="deleteData('{{ md5($item->id) }}')">Hapus</a>
                                                {!! Form::close() !!}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-form">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('status.save') }}" method="POST" enctype="multipart/form-data" id="form-data">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" value="">
                        <div class="form-group">
                            <label for="nama">Nama<small style="color: #dc3545;">*</small></label>
                            <input type="text" class="form-control" name="nama" id="nama"
                                placeholder="Masukan Nama" autocomplete="off" value="">
                            <span id="error-nama" class="error invalid-feedback"></span>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><i
                                class="fas fa-times-circle"></i> Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i>
                            Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function addData() {
            $('#form-data .form-control').val('').change().removeClass('is-invalid');
            $('.modal-title').text('Tambah {{ $title }}');
            $('#modal-form').modal('show');
        }

        function editData(id) {
            $('#form-data .form-control').val('').change().removeClass('is-invalid');
            $('[name="id"], [name="nama"]').val(function() {
                return $('[name="' + $(this).attr('name') + '_' + id + '"]').val();
            });
            $('.modal-title').text('Edit {{ $title }}');
            $('#form-data').attr('method', 'POST').append('@method('PUT')');
            $('#modal-form').modal('show');
        }
    </script>
@endsection
