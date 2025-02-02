<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
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
                {{-- <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
                    <li class="nav-item d-lg-none">
                        <a class="btn btn-default btn-sm" href="{{ route('login') }}" style="font-weight: bold;"><i
                                class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                </ul> --}}
            </div>
        </nav>
        <div class="content-wrapper" style="background-color: white;">
            <div class="content-header">
                <div class="container"></div>
            </div>
            @php
                $screening =
                    '
<div class="card card-primary card-outline">
    <div class="card-header">
        <h6 class="card-title">Selamat Datang di TB CARE</h6>
    </div>
    <div class="card-body">
        <a href="' .
                    route('screening') .
                    '" type="button" class="btn btn-primary btn-block" style="font-weight: bold;">
            <i class="fas fa-user-md"></i> Skrining
        </a> 
        <a href="' .
                    route('login') .
                    '" type="button" class="btn btn-outline-primary btn-block" style="font-weight: bold;">
            <i class="fas fa-sign-in-alt"></i> Login
        </a> 
    </div>
</div>';
            @endphp

            <div class="content">
                <div class="container">
                    <div class="row">
                        <div class="col-12 d-lg-none">
                            {!! $screening !!}
                        </div>
                        <div class="col-lg-8">
                            <div class="row">
                                @foreach ($materials as $item)
                                    <div class="col-lg-6 col-12">
                                        <div class="card card-primary card-outline">
                                            <img class="card-img-top p-4"
                                                src="{{ asset('storage/materials/' . $item['material_file']) }}"
                                                alt="" style="width: 100%; height: 300px;">
                                            <div class="card-body">
                                                <h6 class="card-title mb-2"><a href=""
                                                        style="text-decoration: none;">{{ Str::limit($item['title_material'], 100) }}</a>
                                                </h6>
                                                <p class="card-text">{{ Str::limit($item['description'], 250) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-12">
                                    <nav class="mb-3">
                                        <ul class="pagination justify-content-center m-0">
                                            {{ $materials->links('pagination::bootstrap-4') }}
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 d-none d-lg-block">
                            {!! $screening !!}
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
    <script src="{{ asset('assets/dist/js/tbcare.js') }}"></script>
</body>

</html>
