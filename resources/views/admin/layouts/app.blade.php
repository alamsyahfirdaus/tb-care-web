<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} | {{ config('constants.APP_NAME', 'TB Care') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- SweetAlert2 & Toastr -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
        href="{{ asset('assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/daterangepicker/daterangepicker.css') }}">
    <!-- Theme style (AdminLTE) -->
    <link rel="stylesheet" href="{{ asset('assets/dist/css/tbcare.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">

    <!-- Healthcare Custom Styling -->
    <style>
        :root {
            --tb-primary: #1b75bb;
            --tb-primary-dark: #12558a;
            --tb-teal: #17a2b8;
            --tb-success: #28a745;
            --tb-warning: #ffc107;
            --tb-danger: #dc3545;
            --tb-light: #f4f6f9;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--tb-light);
            color: #333333;
        }

        .main-header {
            border-bottom: 1px solid #dee2e6;
        }

        .navbar-navy {
            background-color: var(--tb-primary);
        }

        .brand-link {
            background-color: var(--tb-primary-dark) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0.8125rem 1rem;
        }

        .brand-link .brand-text {
            font-weight: 700;
            letter-spacing: 0.5px;
            font-size: 1.25rem;
            color: #ffffff;
        }

        .brand-link .brand-badge {
            background-color: #20c997;
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 5px;
            vertical-align: middle;
            text-transform: uppercase;
        }

        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active,
        .sidebar-light-primary .nav-sidebar>.nav-item>.nav-link.active {
            background-color: var(--tb-primary);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
        }

        .nav-sidebar .nav-treeview>.nav-item>.nav-link.active {
            background-color: rgba(27, 117, 187, 0.12);
            color: var(--tb-primary);
            font-weight: 600;
        }

        .nav-sidebar .nav-header {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8898aa;
            padding: 0.75rem 1rem 0.35rem 1rem;
        }

        .card {
            border: 0;
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, .08);
            padding: 0.85rem 1.25rem;
        }

        .card-title {
            font-weight: 600;
            font-size: 1.05rem;
            color: #2c3e50;
        }

        .badge-tb-rendah {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }

        .badge-tb-sedang {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }

        .badge-tb-tinggi {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }

        .badge-tb-lanjut {
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }

        .badge-tb-berjalan {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
            font-weight: 600;
            padding: 0.35em 0.65em;
        }

        .small-box {
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .info-box {
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #eef2f5;
        }

        .table thead th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
            font-size: 0.88rem;
            vertical-align: middle;
            background-color: #f8f9fa;
        }

        .table tbody td {
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .btn-action-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .user-panel img {
            object-fit: cover;
        }

        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 0;
            font-size: 0.85rem;
        }

        .filter-card {
            background-color: #ffffff;
            border-left: 4px solid var(--tb-primary);
        }
    </style>
    @stack('styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed text-sm">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link"><i
                            class="fas fa-tachometer-alt mr-1"></i> Dashboard</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('admin.screenings.index') }}" class="nav-link"><i
                            class="fas fa-stethoscope mr-1"></i> Skrining TB</a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ route('admin.patients.index') }}" class="nav-link"><i
                            class="fas fa-user-injured mr-1"></i> Data Pasien</a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Quick Link to Public Portal -->
                <li class="nav-item">
                    <a class="nav-link" href="{{ url('/') }}" target="_blank"
                        title="Buka Portal Pasien / Web Publik">
                        <i class="fas fa-external-link-alt mr-1"></i> Portal Publik
                    </a>
                </li>

                <!-- Notifications Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-bell"></i>
                        @php
                            $notifCount = \App\Models\Screening::where('status', 'Perlu Tindak Lanjut')->count();
                        @endphp
                        @if ($notifCount > 0)
                            <span class="badge badge-danger navbar-badge">{{ $notifCount }}</span>
                        @endif
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header font-weight-bold">{{ $notifCount }} Peringatan
                            Tindak Lanjut</span>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('admin.screenings.action_needed') }}" class="dropdown-item">
                            <i class="fas fa-exclamation-circle text-danger mr-2"></i> {{ $notifCount }} Skrining
                            Perlu Tindak Lanjut
                            <span class="float-right text-muted text-sm">Segera</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('admin.notifications.index') }}" class="dropdown-item dropdown-footer">Lihat
                            Semua Notifikasi</a>
                    </div>
                </li>

                <!-- User Account Menu -->
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                        <img src="{{ Auth::user() && Auth::user()->photo ? asset('upload_images/' . Auth::user()->photo) : asset('assets/img/profile.png') }}"
                            class="user-image img-circle elevation-1" alt="User Image">
                        <span
                            class="d-none d-md-inline font-weight-bold">{{ Auth::user() ? Auth::user()->name : 'Admin' }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <!-- User image -->
                        <li class="user-header bg-primary">
                            <img src="{{ Auth::user() && Auth::user()->photo ? asset('upload_images/' . Auth::user()->photo) : asset('assets/img/profile.png') }}"
                                class="img-circle elevation-2" alt="User Image">
                            <p>
                                {{ Auth::user() ? Auth::user()->name : 'Administrator' }}
                                <small>{{ Auth::user() && Auth::user()->userType ? Auth::user()->userType->name : 'Admin Sistem' }}</small>
                            </p>
                        </li>
                        <!-- Menu Footer-->
                        <li class="user-footer d-flex justify-content-between">
                            <a href="{{ route('admin.settings.profile') }}" class="btn btn-default btn-flat"><i
                                    class="fas fa-user mr-1"></i> Profil</a>
                            <a href="{{ route('logout') }}" class="btn btn-danger btn-flat"><i
                                    class="fas fa-sign-out-alt mr-1"></i> Keluar</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="{{ route('admin.dashboard') }}" class="brand-link">
                <span class="brand-text font-weight-bold">TB CARE</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user panel -->
                {{-- <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
                    <div class="image">
                        <img src="{{ Auth::user() && Auth::user()->photo ? asset('upload_images/' . Auth::user()->photo) : asset('assets/img/profile.png') }}"
                            class="img-circle elevation-1" style="width: 36px; height: 36px;" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="{{ route('admin.settings.profile') }}"
                            class="d-block font-weight-bold text-white text-truncate" style="max-width: 170px;">
                            {{ Auth::user() ? Auth::user()->name : 'Administrator' }}
                        </a>
                        <span class="text-xs text-success"><i class="fas fa-circle text-xs mr-1"></i> Online</span>
                    </div>
                </div> --}}

                <!-- Sidebar Menu -->
                <nav class="mt-4 mb-4">
                    <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview"
                        role="menu" data-accordion="false">

                        <!-- 1. DASHBOARD -->
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}"
                                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-home"></i>
                                <p>Beranda</p>
                            </a>
                        </li>

                        <li class="nav-header">MANAJEMEN UTAMA</li>

                        <!-- 2. PENGGUNA -->
                        <li class="nav-item {{ request()->is('admin/users*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users text-primary"></i>
                                <p>
                                    Pengguna
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.users.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.users.index') && !request('role') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Semua Pengguna</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.users.index', ['role' => 2]) }}"
                                        class="nav-link {{ request('role') == '2' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Masyarakat / Pasien</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.users.index', ['role' => 3]) }}"
                                        class="nav-link {{ request('role') == '3' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Petugas / Nakes</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.users.index', ['role' => 1]) }}"
                                        class="nav-link {{ request('role') == '1' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Administrator</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 3. DATA PASIEN -->
                        <li class="nav-item {{ request()->is('admin/patients*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/patients*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-injured text-danger"></i>
                                <p>
                                    Data Pasien
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.patients.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.patients.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Semua Pasien</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.patients.create') }}"
                                        class="nav-link {{ request()->routeIs('admin.patients.create') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Tambah Pasien Baru</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 4. SKRINING TB -->
                        <li class="nav-item {{ request()->is('admin/screenings*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/screenings*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-stethoscope text-warning"></i>
                                <p>
                                    Skrining TB
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.screenings.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.screenings.index') && !request('risk') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Semua Skrining</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Rendah']) }}"
                                        class="nav-link {{ request('risk') == 'Risiko Rendah' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>Risiko Rendah</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Sedang']) }}"
                                        class="nav-link {{ request('risk') == 'Risiko Sedang' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon text-warning"></i>
                                        <p>Risiko Sedang</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Tinggi']) }}"
                                        class="nav-link {{ request('risk') == 'Risiko Tinggi' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon text-danger"></i>
                                        <p>Risiko Tinggi</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.screenings.action_needed') }}"
                                        class="nav-link {{ request()->routeIs('admin.screenings.action_needed') ? 'active' : '' }}">
                                        <i class="fas fa-exclamation-triangle nav-icon text-danger"></i>
                                        <p>Perlu Tindak Lanjut</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 5. PEMERIKSAAN -->
                        <li class="nav-item {{ request()->is('admin/examinations*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/examinations*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-microscope text-cyan"></i>
                                <p>
                                    Pemeriksaan
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.examinations.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.examinations.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Semua Pemeriksaan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.examinations.index', ['view' => 'results']) }}"
                                        class="nav-link {{ request('view') == 'results' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Hasil Pemeriksaan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.examinations.index', ['view' => 'diagnosis']) }}"
                                        class="nav-link {{ request('view') == 'diagnosis' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Diagnosis Pasien</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 6. PENGOBATAN -->
                        <li class="nav-item {{ request()->is('admin/treatments*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/treatments*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-pills text-success"></i>
                                <p>
                                    Pengobatan
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.treatments.index', ['status' => 'Berjalan']) }}"
                                        class="nav-link {{ request('status') == 'Berjalan' || (request()->routeIs('admin.treatments.index') && !request('status')) ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon text-primary"></i>
                                        <p>Dalam Pengobatan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.treatments.monitoring') }}"
                                        class="nav-link {{ request()->routeIs('admin.treatments.monitoring') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon text-info"></i>
                                        <p>Monitoring Pengobatan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.treatments.index', ['status' => 'Selesai']) }}"
                                        class="nav-link {{ request('status') == 'Selesai' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon text-success"></i>
                                        <p>Hasil Pengobatan</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 7. KONTAK ERAT -->
                        <li class="nav-item {{ request()->is('admin/contacts*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/contacts*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-people-arrows text-teal"></i>
                                <p>
                                    Kontak Erat
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.contacts.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.contacts.index') && !request('filter') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Semua Kontak</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.contacts.index', ['filter' => 'keluarga']) }}"
                                        class="nav-link {{ request('filter') == 'keluarga' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Kontak Keluarga</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.contacts.index', ['filter' => 'screening']) }}"
                                        class="nav-link {{ request('filter') == 'screening' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Hasil Skrining Kontak</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-header">FASKES & WILAYAH</li>

                        <!-- 8. FASILITAS KESEHATAN -->
                        <li class="nav-item {{ request()->is('admin/puskesmas*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/puskesmas*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-hospital-alt text-info"></i>
                                <p>
                                    Fasilitas Kesehatan
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.puskesmas.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.puskesmas.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Puskesmas</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.puskesmas.index', ['type' => 'klinik']) }}"
                                        class="nav-link {{ request('type') == 'klinik' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Klinik</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.puskesmas.index', ['type' => 'rs']) }}"
                                        class="nav-link {{ request('type') == 'rs' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Rumah Sakit</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 9. WILAYAH -->
                        <li class="nav-item {{ request()->is('admin/regions*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/regions*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-map-marked-alt text-warning"></i>
                                <p>
                                    Wilayah
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.regions.index', ['tier' => 'provinces']) }}"
                                        class="nav-link {{ request('tier') == 'provinces' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Provinsi</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.regions.index', ['tier' => 'districts']) }}"
                                        class="nav-link {{ request('tier') == 'districts' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Kabupaten / Kota</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.regions.index', ['tier' => 'subdistricts']) }}"
                                        class="nav-link {{ request('tier') == 'subdistricts' || !request('tier') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Kecamatan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.regions.index', ['tier' => 'villages']) }}"
                                        class="nav-link {{ request('tier') == 'villages' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Desa / Kelurahan</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-header">MEDIA & KOMUNIKASI</li>

                        <!-- 10. EDUKASI -->
                        <li class="nav-item {{ request()->is('admin/education*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/education*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book-medical text-primary"></i>
                                <p>
                                    Edukasi
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.education.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.education.index') && !request('type') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Semua Konten</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.education.index', ['type' => 'image']) }}"
                                        class="nav-link {{ request('type') == 'image' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Poster & Infografis</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.education.index', ['type' => 'video']) }}"
                                        class="nav-link {{ request('type') == 'video' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Video Edukasi</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 11. NOTIFIKASI -->
                        <li class="nav-item {{ request()->is('admin/notifications*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/notifications*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-bell text-secondary"></i>
                                <p>
                                    Notifikasi
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.index') }}"
                                        class="nav-link {{ request()->routeIs('admin.notifications.index') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Semua Notifikasi</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.notifications.consultations') }}"
                                        class="nav-link {{ request()->routeIs('admin.notifications.consultations') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Pesan Konsultasi</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-header">LAPORAN & ANALISIS</li>

                        <!-- 12. LAPORAN -->
                        <li class="nav-item {{ request()->is('admin/reports*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-invoice text-success"></i>
                                <p>
                                    Laporan
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.reports.index', ['type' => 'screening']) }}"
                                        class="nav-link {{ request('type') == 'screening' || !request('type') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Laporan Skrining</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.reports.index', ['type' => 'patients']) }}"
                                        class="nav-link {{ request('type') == 'patients' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Laporan Pasien TB</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.reports.index', ['type' => 'regions']) }}"
                                        class="nav-link {{ request('type') == 'regions' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Laporan Wilayah</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.reports.index', ['type' => 'users']) }}"
                                        class="nav-link {{ request('type') == 'users' ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Laporan Pengguna</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 13. ANALITIK -->
                        <li class="nav-item">
                            <a href="{{ route('admin.analytics.index') }}"
                                class="nav-link {{ request()->routeIs('admin.analytics.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-chart-pie text-purple"></i>
                                <p>Analitik TB</p>
                            </a>
                        </li>

                        <li class="nav-header">SISTEM & KONFIGURASI</li>

                        <!-- 14. MASTER DATA -->
                        <li class="nav-item {{ request()->is('admin/master*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('admin/master*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-database text-warning"></i>
                                <p>
                                    Master Data
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.master.questions') }}"
                                        class="nav-link {{ request()->routeIs('admin.master.questions') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Pertanyaan Skrining</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.master.treatment_types') }}"
                                        class="nav-link {{ request()->routeIs('admin.master.treatment_types') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Jenis Pengobatan</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.master.categories') }}"
                                        class="nav-link {{ request()->routeIs('admin.master.categories') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Kategori Data</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- 15. LOG AKTIVITAS -->
                        <li class="nav-item">
                            <a href="{{ route('admin.activity_logs.index') }}"
                                class="nav-link {{ request()->routeIs('admin.activity_logs.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-history text-secondary"></i>
                                <p>Log Aktivitas</p>
                            </a>
                        </li>

                        <!-- 16. PENGATURAN -->
                        <li class="nav-item {{ request()->is('admin/settings*') ? 'menu-open' : '' }}">
                            <a href="#"
                                class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-cog text-muted"></i>
                                <p>
                                    Pengaturan
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('admin.settings.profile') }}"
                                        class="nav-link {{ request()->routeIs('admin.settings.profile') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Profil Admin</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.settings.roles') }}"
                                        class="nav-link {{ request()->routeIs('admin.settings.roles') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Role & Hak Akses</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.settings.system') }}"
                                        class="nav-link {{ request()->routeIs('admin.settings.system') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Pengaturan Sistem</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header py-3">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h1 class="m-0 font-weight-bold text-dark" style="font-size: 1.5rem;">
                                {{ $pageTitle ?? ($title ?? 'Admin Dashboard') }}
                            </h1>
                            @if (isset($pageSubtitle))
                                <span class="text-muted text-sm">{{ $pageSubtitle }}</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i
                                            class="fas fa-home"></i> Beranda</a></li>
                                @yield('breadcrumb')
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <!-- Flash Messages -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show text-dark" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('warning') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="font-weight-bold"><i class="fas fa-ban mr-1"></i> Terdapat Kesalahan Pengisian
                                Form:</h6>
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <footer class="main-footer text-sm">
            <strong>Copyright &copy; 2024-{{ date('Y') }} <a href="{{ url('/') }}"
                    class="text-primary font-weight-bold">TB Care</a>.</strong>
            Sistem Informasi & Pengawasan Pengobatan Tuberkulosis Terintegrasi.
            <div class="float-right d-none d-sm-inline-block">
                <b>Versi</b> 2.5 (Production)
            </div>
        </footer>
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->
    <!-- jQuery -->
    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- DataTables & Plugins -->
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('assets/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- Moment -->
    <script src="{{ asset('assets/plugins/moment/moment.min.js') }}"></script>
    <!-- Daterangepicker -->
    <script src="{{ asset('assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="{{ asset('assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <!-- ChartJS -->
    <script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
    <!-- OverlayScrollbars -->
    <script src="{{ asset('assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('assets/dist/js/tbcare.min.js') }}"></script>

    <script>
        // Global setup for AJAX CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(function() {
            // Initialize Select2 Elements
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Function to safely initialize standard DataTables
            window.initTbDataTable = function(selector, customOptions = {}) {
                var defaultOptions = {
                    responsive: true,
                    autoWidth: false,
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, 100, -1],
                        [10, 25, 50, 100, "Semua"]
                    ],
                    ordering: true,
                    searching: true,
                    paging: true,
                    info: true,
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                        infoEmpty: "Tidak ada data",
                        infoFiltered: "(disaring dari _MAX_ total data)",
                        zeroRecords: "Data tidak ditemukan",
                        emptyTable: "Belum ada data",
                        paginate: {
                            first: "Pertama",
                            last: "Terakhir",
                            next: "Berikutnya",
                            previous: "Sebelumnya"
                        }
                    }
                };

                var finalOptions = $.extend(true, {}, defaultOptions, customOptions);

                $(selector).each(function() {
                    if (!$.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable(finalOptions);
                    }
                });
            };

            // Auto-initialize any .data-table on document ready
            initTbDataTable('.data-table');

            // Adjust column sizing when switching tabs
            $('a[data-toggle="pill"], a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                $.fn.dataTable.tables({
                    visible: true,
                    api: true
                }).columns.adjust().responsive.recalc();
            });
        });

        // Confirmation Modal for Deletion
        function confirmDelete(formId, itemName) {
            Swal.fire({
                title: 'Konfirmasi Hapus Data',
                text: itemName ? 'Apakah Anda yakin ingin menghapus data "' + itemName + '"?' :
                    'Apakah Anda yakin ingin menghapus data ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
    </script>

    @stack('scripts')
</body>

</html>
