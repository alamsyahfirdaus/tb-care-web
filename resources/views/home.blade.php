@extends('layouts/main')
@section('content')
    <div class="container-fluid">
        @if (session('role') == 4)
            @if (empty($medication_record->id))
                <div class="alert alert-warning alert-dismissible" style="color: black;">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Peringatan!</h5>
                    <span>Anda belum minum obat hari ini. <a href="javascript:void(0)" id="takeMedicine"
                            style="color: black;">Minum obat sekarang</a>.</span>
                </div>
            @endif
            <div class="row">
                @foreach ($patient_treatments as $label => $count)
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-info"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ $label }}</span>
                                <span class="info-box-number">{{ $count }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- <div class="card card-primary card-outline">
            <div class="card-body">
                <h3>Hello</h3>
            </div>
        </div> --}}
        @endif
    </div>
    <div class="modal fade" id="modal-take-medicine">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Minum Obat</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('take.medicine') }}" method="POST" enctype="multipart/form-data"
                        id="form-upload">
                        @csrf
                        <input type="file" name="photo" accept="image/jpeg, image/png, image/gif"
                            style="display: none;">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="text" class="form-control" id="photo" placeholder="Unggah Foto" readonly
                                    style="background-color: #ffffff;">
                                <span class="input-group-append">
                                    <button type="button" id="uploadPhoto" class="btn btn-primary"><i
                                            class="fas fa-upload"></i></button>
                                </span>
                            </div>
                            <span id="error-photo" class="error invalid-feedback"></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('#takeMedicine').click(function() {
            $('#modal-take-medicine').modal('show');
            $('[name="photo"]').val('');
            $('#photo').val('')
                .removeClass('is-invalid');
            $('#error-photo').text('')
                .hide();
        });

        $('#photo').click(function() {
            $('[name="photo"]').click();
        });

        $('[name="photo"]').change(function() {
            $('#photo').val($(this).val().split('\\').pop());
        });

        $('#uploadPhoto').click(function() {
            let photo = $('[name="photo"]').prop('files')[0];
            let validTypes = ['image/jpeg', 'image/png', 'image/gif'];

            let photoInput = $('#photo');
            let errorFeedback = $('#error-photo');

            photoInput.removeClass('is-invalid');
            errorFeedback.text('').hide();

            if (photo) {
                if (validTypes.includes(photo.type)) {
                    $('#form-upload').submit();
                } else {
                    photoInput.addClass('is-invalid');
                    errorFeedback.text('Format foto tidak valid.').show();
                }

            } else {
                photoInput.addClass('is-invalid');
                errorFeedback.text('Foto harus diunggah.').show();
            }
        });
    </script>
@endsection
