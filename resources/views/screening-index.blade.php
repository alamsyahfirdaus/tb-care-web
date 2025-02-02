<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ Config::get('constants.APP_NAME') }} | {{ $title }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/bs-stepper/css/bs-stepper.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dist/css/tbcare.css') }}">
</head>

<body class="hold-transition layout-top-nav" style="font-family: Arial;">
    <div class="wrapper">

        <nav class="main-header navbar navbar-expand navbar-primary navbar-dark"
            style="border-bottom: 1px solid #007bff;">
            <div class="container">
                <a href="/" class="navbar-brand">
                    <span class="brand-text" style="font-weight: bold; font-size: 24px;">TB CARE</span>
                </a>
            </div>
        </nav>
        <div class="content-wrapper" style="background-color: white;">
            <div class="content-header">
                <div class="container"></div>
            </div>
            <div class="content">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary card-outline">
                                <div class="card-body">
                                    <div class="bs-stepper">
                                        <div class="bs-stepper-header" role="tablist">
                                            @foreach ($screenings as $item)
                                                <div class="step" data-target="#form{{ $item->id }}-part">
                                                    <button type="button" class="step-trigger" role="tab"
                                                        aria-controls="form{{ $item->id }}-part"
                                                        id="form{{ $item->id }}-part-trigger">
                                                        <span class="bs-stepper-circle">{{ $loop->iteration }}</span>
                                                        <span
                                                            class="bs-stepper-label">{{ $item->screening_category }}</span>
                                                    </button>
                                                </div>
                                                @if ($loop->iteration < count($screenings))
                                                    <div class="line"></div>
                                                @endif
                                            @endforeach
                                        </div>
                                        <div class="bs-stepper-content">
                                            <form action="{{ route('process.screening') }}" method="POST"
                                                enctype="multipart/form-data" id="form-data">
                                                @csrf
                                                @foreach ($screenings as $item)
                                                    @php
                                                        $categories = \App\Models\Category::where(
                                                            'screening_id',
                                                            $item->id,
                                                        )->get();
                                                    @endphp
                                                    <div id="form{{ $item->id }}-part" class="content"
                                                        role="tabpanel"
                                                        aria-labelledby="form{{ $item->id }}-part-trigger">
                                                        <div class="table-responsive">
                                                            <table class="table table-striped" style="width: 100%;">
                                                                @foreach ($categories as $category)
                                                                    <tr>
                                                                        <td style="width: 5%; padding: 6px;">
                                                                            {{ $loop->iteration }}.
                                                                            {{ $category->category_name }}
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <input type="hidden" name="category_ids[]"
                                                                            value="{{ $category->id }}">
                                                                        <td
                                                                            style="{{ $loop->iteration < count($categories) ? 'padding-bottom: 12px;' : 'padding-bottom: 0px;' }}">
                                                                            <div class="form-group clearfix mb-2">
                                                                                @foreach ([1 => 'Ya', 2 => 'Tidak'] as $key => $label)
                                                                                    <div
                                                                                        class="icheck-primary d-inline">
                                                                                        <input type="radio"
                                                                                            id="question_{{ $category->id }}_{{ $key }}"
                                                                                            name="question_{{ $category->id }}"
                                                                                            value="{{ $key }}"
                                                                                            onclick="showDuration({{ $category->id }});">
                                                                                        <label
                                                                                            for="question_{{ $category->id }}_{{ $key }}"
                                                                                            style="font-weight: normal;">{{ $label }}</label>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                            <div id="question_{{ $category->id }}"
                                                                                class="row" style="display: none;">
                                                                                <div class="col-2">
                                                                                    <input type="text"
                                                                                        name="category_id_{{ $category->id }}"
                                                                                        class="form-control form-control-sm"
                                                                                        placeholder="Berapa hari?"
                                                                                        autocomplete="off"
                                                                                        onkeyup="validateDuration(this)">
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </table>
                                                        </div>

                                                        <hr>
                                                        @if ($loop->iteration !== 1)
                                                            <button type="button" class="btn btn-primary btn-sm"
                                                                onclick="stepper.previous()" style="font-weight: bold;">
                                                                <i class="fas fa-angle-double-left"></i> Sebelumnya
                                                            </button>
                                                        @endif

                                                        @if ($loop->last)
                                                            <button type="button" id="btn-submit"
                                                                class="btn btn-primary btn-sm"
                                                                style="font-weight: bold;"><i
                                                                    class="fas fa-paper-plane"></i> Kirim</button>
                                                        @else
                                                            <button type="button"
                                                                class="btn btn-primary btn-sm next-button"
                                                                style="font-weight: bold;"><i
                                                                    class="fas fa-angle-double-right"></i>
                                                                Selanjutnya</button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="control-sidebar control-sidebar-dark"></aside>

        <footer class="main-footer">
            <strong>Copyright &copy; 2020-{{ date('Y') }} Alamsyah Firdaus.</strong>
        </footer>
    </div>

    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/dist/js/tbcare.js') }}"></script>
    <script>
        $('.next-button').click(function() {
            let isValid = true;

            let activeStep = $('.bs-stepper-content .content.active');

            activeStep.find('input[type="radio"]').each(function() {
                let name = $(this).attr('name');
                if (activeStep.find(`input[name="${name}"]:checked`).length === 0) {
                    isValid = false;
                    return false;
                }
            });

            if (isValid) {
                stepper.next();
            } else {
                Swal.fire({
                    title: "Peringatan!",
                    text: "Harap pilih semua opsi sebelum melanjutkan.",
                    icon: "warning",
                    showConfirmButton: false,
                    timer: 1750
                });
                return;
            }
        });

        $('#btn-submit').click(function() {
            let isValid = true;
            let activeStep = $('.bs-stepper-content .content.active');

            activeStep.find('input[type="radio"]').each(function() {
                let name = $(this).attr('name');
                let categoryId = $(this).attr('id').split('_')[1];

                if (activeStep.find(`input[name="${name}"]:checked`).length === 0) {
                    isValid = false;
                    activeStep.find(`input[name="${name}"]`).addClass('is-invalid');
                } else {
                    activeStep.find(`input[name="${name}"]`).removeClass('is-invalid');
                }

                if (categoryId == 10 && activeStep.find(`input[name="${name}"]:checked`).val() == 1) {
                    let durationInput = activeStep.find(`input[name="category_id_${categoryId}"]`);
                    if (durationInput.val().trim() === '') {
                        isValid = false;
                        durationInput.addClass('is-invalid');
                    } else {
                        durationInput.removeClass('is-invalid');
                    }

                    durationInput.keyup(function() {
                        durationInput.removeClass('is-invalid');
                    });
                }
            });

            if (isValid) {
                $('#form-data').submit();
            } else {
                Swal.fire({
                    title: "Peringatan!",
                    text: "Harap pilih semua opsi sebelum melanjutkan.",
                    icon: "warning",
                    showConfirmButton: false,
                    timer: 1750
                });
            }
        });

        function showDuration(categoryId) {
            if (categoryId === 12) {
                var checkedValue = $(`input[name="question_${categoryId}"]:checked`).val();
                var durationContainer = $(`#question_${categoryId}`);

                if (checkedValue) {
                    if (checkedValue === '1') {
                        durationContainer.show();
                    } else {
                        durationContainer.hide();
                    }
                } else {
                    durationContainer.hide();
                }
            }
        }

        function validateDuration(input) {
            input.value = input.value.replace(/[^0-9]/g, '');

            if (input.value === '0') {
                input.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.stepper = new Stepper(document.querySelector('.bs-stepper'))
        })
    </script>
    @if (@session()->has('success'))
        <script>
            Swal.fire({
                title: "Selamat!",
                text: "{{ session('success') }}",
                icon: "success",
                showConfirmButton: false,
                timer: 2500
            });
        </script>
    @endif
    @if (@session()->has('warning'))
        <script>
            Swal.fire({
                title: "Peringatan!",
                text: "{{ session('warning') }}",
                icon: "warning",
                showConfirmButton: false,
                timer: 2500
            });
        </script>
    @endif
</body>

</html>
