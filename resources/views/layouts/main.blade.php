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
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet"
        href="{{ asset('assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/jqvmap/jqvmap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/bs-stepper/css/bs-stepper.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-bs4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dist/css/tbcare.css') }}">
    <style>
        @media (max-width: 991px) {
            body:not(.sidebar-open) .main-sidebar {
                transform: translateX(-250px);
            }
        }

        body {
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
        }

        .navbar-primary {
            border-bottom: 1px solid #007bff;
        }

        #brand-link {
            background-color: #007bff;
            border-bottom: 1px solid #007bff;
            height: 57px;
        }

        .brand-image-text {
            color: white;
            font-weight: bold;
            font-size: 28px;
            padding-left: 16px;
        }

        .brand-text {
            color: white;
            font-weight: bold;
            font-size: 28px;
        }

        .user-panel .image img {
            width: 40px;
            aspect-ratio: 1 / 1;
        }

        .content-wrapper {
            background-color: white;
        }

        #user-profile {
            color: white;
        }

        .main-footer a {
            color: #869099;
        }

        form .btn-sm,
        .btn-block {
            font-weight: bold;
        }

        .profile-user-img {
            border: none;
            width: 150px;
            height: 150px;
        }

        .form-control:disabled {
            background-color: #ffffff;
            opacity: 1;
        }

        th span {
            color: #ffffff;
        }

        .bs-stepper-content {
            padding-bottom: 0px;
        }

        .card-footer {
            padding: 0.75rem 1.25rem;
            background-color: #ffffff;
            border-top: 0 solid rgba(0, 0, 0, 0.125);
            padding-top: 0px;
        }

        .select2-container--default.select2-container--disabled .select2-selection--single {
            background-color: #fff;
            cursor: default;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border: 1px solid #ced4da;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">

    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-primary navbar-dark" style="height: 57px;">
            <div class="container-fluid">
                <a href="javascript:void(0)" class="px-2 d-lg-none">
                    <span style="color: white; font-weight: bold; font-size: 28px; display: block;">TB CARE</span>
                </a>

                <ul class="navbar-nav ml-auto">
                    <li class="nav-item d-lg-none">
                        <a class="nav-link px-1" data-widget="pushmenu" href="javascript:void(0)"><i
                                class="fas fa-bars"></i></a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="javascript:void(0)"
                            id="user-profile">
                            <i class="fas fa-user-alt fa-fw"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a href="{{ route('profile') }}" class="dropdown-item">
                                <i class="fas fa-user mr-2"></i> Profil Saya
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('logout') }}" class="dropdown-item">
                                <i class="fas fa-sign-out-alt mr-2"></i> Keluar
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="main-sidebar sidebar-light-primary">
            <a href="javascript:void(0)" id="brand-link" class="brand-link py-2">
                <span class="brand-image-text">TB</span>
                <span class="brand-text">CARE</span>
            </a>

            <div class="sidebar" style="border-right: 1px solid #DEE2E6;">
                <div class="user-panel my-3 pb-3 d-flex">
                    <div class="image">
                        <img src="{{ Auth::user()->profile ? asset('upload_images/' . Auth::user()->profile) : asset('assets/img/profile.png') }}"
                            class="img-circle" alt="">
                    </div>
                    <div class="info">
                        <a href="{{ route('user.show', ['id' => base64_encode(Auth::id())]) }}" class="d-block py-1">
                            {{ Str::limit(Auth::user()->name, 20) }}
                        </a>
                    </div>
                </div>
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item">
                            <a href="{{ route('home') }}"
                                class="nav-link {{ Request::segment(1) == 'home' || $title == 'Profile' ? 'active' : '' }}">
                                <i class="nav-icon fas fa-home"></i>
                                <p>Beranda</p>
                            </a>
                        </li>
                        @if (session('role') == 1)

                            <li
                                class="nav-item {{ in_array(Request::segment(1), ['trtypes', 'pkm']) ? 'menu-open' : '' }}">
                                <a href="javascript:void(0)"
                                    class="nav-link {{ in_array(Request::segment(1), ['trtypes', 'pkm']) ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-folder-open"></i>
                                    <p>Data Induk<i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('pkm') }}"
                                            class="nav-link {{ Request::segment(1) == 'pkm' ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Daftar Puskesmas</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('trtypes') }}"
                                            class="nav-link {{ Request::segment(1) == 'trtypes' ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Jenis Pengobatan</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            @php
                                $segments = ['user', 'ho', 'coord', 'patient'];
                                $isActive = in_array(Request::segment(1), $segments);
                            @endphp

                            <li class="nav-item {{ $isActive ? 'menu-open' : '' }}">
                                <a href="javascript:void(0)" class="nav-link {{ $isActive ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>
                                        Pengguna
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    @foreach (App\Models\UserType::all() as $item)
                                        <li class="nav-item">
                                            <a href="{{ route('user.list', ['id' => base64_encode($item->id)]) }}"
                                                class="nav-link {{ $title == $item->name ? 'active' : '' }}">
                                                <i class="far fa-circle nav-icon"></i>
                                                <p>{{ $item->name }}</p>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('treatments') }}"
                                    class="nav-link {{ $title == 'Pengobatan' ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-md"></i>
                                    <p>Pengobatan</p>
                                </a>
                            </li>
                        @endif
                        @if (in_array(session('role'), [2, 3]))
                            <li class="nav-item">
                                <a href="{{ route('patients') }}"
                                    class="nav-link {{ $title == 'Pasien' ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i>
                                    <p>Pasien</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('treatments') }}"
                                    class="nav-link {{ $title == 'Pengobatan' ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-user-md"></i>
                                    <p>Pengobatan</p>
                                </a>
                            </li>
                        @endif
                        @if (session('role') != 4)
                            <li class="nav-item">
                                <a href="{{ route('materials') }}"
                                    class="nav-link {{ $title == 'Materi Edukasi' ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-newspaper"></i>
                                    <p>Materi Edukasi</p>
                                </a>
                            </li>
                        @endif
                        @if (session('role') == 4)
                            <li class="nav-item">
                                <a href="{{ route('treatments') }}"
                                    class="nav-link {{ $title == 'Pengobatan' ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-box"></i>
                                    <p>Pengobatan</p>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">{{ $title }}</h1>
                        </div>
                        {{-- <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="javascript:void(0)">#</a></li>
                                <li class="breadcrumb-item active">#</li>
                            </ol>
                        </div> --}}
                    </div>
                </div>
            </div>

            <section class="content">
                <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/select2/js/select2.full.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/sparklines/sparkline.js') }}"></script>
                <script src="{{ asset('assets/plugins/jqvmap/jquery.vmap.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
                <script src="{{ asset('assets/plugins/jquery-knob/jquery.knob.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/moment/moment.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/inputmask/jquery.inputmask.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
                <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
                <script src="{{ asset('assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
                <script src="{{ asset('assets/dist/js/tbcare.js') }}"></script>
                @yield('content')
                @if (@session()->has('success'))
                    <script>
                        Swal.fire({
                            title: "Berhasil!",
                            text: "{{ session('success') }}",
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1750
                        });
                    </script>
                @endif
                @if (@session()->has('error'))
                    <script>
                        Swal.fire({
                            title: "Gagal!",
                            text: "{{ session('error') }}",
                            icon: "error",
                            showConfirmButton: false,
                            timer: 1750
                        });
                    </script>
                @endif
            </section>
            <script>
                $(document).keydown(function(e) {
                    if (e.keyCode == 123) {
                        e.preventDefault();
                    }
                    if ((e.ctrlKey && e.shiftKey && e.keyCode == 73) ||
                        (e.ctrlKey && e.shiftKey && e.keyCode == 74)) {
                        e.preventDefault();
                    }
                    if (e.ctrlKey && e.keyCode == 85) {
                        e.preventDefault();
                    }
                });
            </script>
        </div>
        <footer class="main-footer">
            Copyright &copy; 2020-{{ date('Y') }} <a href="javascript:void(0)">Alamsyah Firdaus</a>.
        </footer>
    </div>

    <script>
        $(function() {
            var table = $('#datatable').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": false,
                "order": [],
                "columnDefs": [{
                    "targets": [-1],
                    "orderable": false,
                }],
                "language": {
                    "emptyTable": "Tidak ada data yang tersedia dalam tabel",
                    // "infoEmpty": "Menampilkan 0 hingga 0 dari 0 entri",
                    // "infoFiltered": "(disaring dari total _MAX_ entri)",
                    "infoFiltered": "",
                    // "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
                    // "lengthMenu": "Tampilkan _MENU_ entri per halaman",
                    "search": "Cari:",
                    "zeroRecords": "Tidak ada data yang cocok dengan pencarian Anda",
                    // "paginate": {
                    //     "first": "Pertama",
                    //     "last": "Terakhir",
                    //     "next": "Selanjutnya",
                    //     "previous": "Sebelumnya"
                    // }
                }
            });

            if (table.data().count() > 0) {
                table.on('order.dt search.dt', function() {
                    table.rows().every(function(rowIdx) {
                        table.cell(rowIdx, 0).data(rowIdx +
                            1);
                    });
                });
            }

            $('.select2').select2();

            $('.datetimepicker-input').datetimepicker({
                format: 'DD/MM/YYYY'
            });

            $('.timepicker-input').datetimepicker({
                format: 'LT'
            });

            $('#btn-password').click(function() {
                var passwordField = $('#password');
                var passwordFieldType = passwordField.attr('type');
                if (passwordFieldType === 'password') {
                    passwordField.attr('type', 'text');
                    $(this).find('i').removeClass('fas fa-eye-slash').addClass('fas fa-eye');
                } else {
                    passwordField.attr('type', 'password');
                    $(this).find('i').removeClass('fas fa-eye').addClass('fas fa-eye-slash');
                }
            });

            $('input[type="file"].custom-file-input').change(function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });

            $('#form-data').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: $(this).attr('action'),
                    data: new FormData($(this)[0]),
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        Swal.fire({
                            title: "Berhasil!",
                            text: response.message,
                            icon: "success",
                            showConfirmButton: false,
                            timer: 1750
                        });
                        if (response.url) {
                            setTimeout(function() {
                                window.location.href = response.url;
                            }, 2250);
                        } else {
                            if (response.previous) {
                                setTimeout(function() {
                                    window.location.href = "{{ url()->previous() }}";
                                }, 2250);
                            } else {
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2250);
                            }
                        }
                    },
                    error: function(xhr, status, error) {

                        $.each(xhr.responseJSON.errors, function(field, messages) {
                            var $field;
                            if ($('[name="' + field + '"]').attr('type') === 'file') {
                                $field = $('#' + field);
                            } else {
                                $field = $('[name="' + field + '"]');
                            }
                            $field.addClass('is-invalid');
                            $('#error-' + field).text(messages[0]).show();

                            $field.on('keyup change', function() {
                                $(this).removeClass('is-invalid');
                                $('#error-' + field).text('').hide();
                            });

                            if ($field.hasClass('datetimepicker-input') || $field
                                .hasClass('timepicker-input')) {
                                $field.click(function() {
                                    $(this).removeClass('is-invalid');
                                    $('#error-' + field).text('').hide();
                                });
                            }

                            if ($field.hasClass('select2')) {
                                $field.next().find('.select2-selection').addClass(
                                    'border border-danger');
                                $field.change(function() {
                                    $(this).next().find(
                                            '.select2-selection')
                                        .removeClass(
                                            'border border-danger');
                                });
                            }
                        });
                    }
                });
            });

            $('#add-data-toggle').click(function() {
                if ($('#tab1').hasClass('active')) {
                    $('#tab1').removeClass('active');
                    $('#tab2').addClass('active');
                    $('.card-title').text('Tambah {{ $title }}');
                    $(this).find('i').removeClass('fas fa-plus').addClass('fas fa-angle-double-left');
                    $(this).attr('title', 'Sebelumnya');
                } else {
                    $('#tab2').removeClass('active');
                    $('#tab1').addClass('active');
                    $('.card-title').text('Daftar {{ $title }}');
                    $(this).find('i').removeClass('fas fa-angle-double-left').addClass('fas fa-plus');
                    $(this).attr('title', 'Tambah {{ $title }}');
                    $('#form-data .form-control').val('').change().removeClass('is-invalid');
                }
            });
        });

        function deleteData(id) {
            Swal.fire({
                title: "Apakah Anda yakin?",
                text: "Anda tidak akan bisa mengembalikannya!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#007bff",
                cancelButtonColor: "#dc3545",
                confirmButtonText: "Ya, hapus!",
                cancelButtonText: "Tidak, batalkan!"
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#remove-' + id).submit();
                }
            });
        }
    </script>
</body>

</html>
