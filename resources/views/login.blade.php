<!DOCTYPE html>

<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default"
    data-assets-path="{{ asset('assets/sneat/') }}" data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>Login - {{ Config::get('constants.APP_NAME') }}</title>

    <meta name="description" content="" />
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/css/core.css') }}"
        class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/css/theme-default.css') }}"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('assets/sneat/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/sneat/vendor/css/pages/page-auth.css') }}" />
    <script src="{{ asset('assets/sneat/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/sneat/js/config.js') }}"></script>
</head>

<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <div class="app-brand justify-content-center">
                            <a href="" class="app-brand-link gap-2">
                                <span class="app-brand-text demo text-body fw-bolder">TB Care</span>
                            </a>
                        </div>
                        <div id="alert-message">
                            @if (session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif
                        </div>
                        <form class="mb-3" action="{{ route('login') }}" method="post" enctype="multipart/form-data"
                            id="form-login">
                            @csrf
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="Username/Email" autocomplete="off" />
                                <span id="error-username" class="error invalid-feedback"></span>
                            </div>
                            <div class="mb-4 form-password-toggle">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="password">Password</label>
                                </div>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="password" />
                                    <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                                </div>
                                <span id="error-password" class="error invalid-feedback"></span>
                            </div>
                            <div class="mb-3">
                                <button class="btn btn-primary d-grid w-100 fw-bolder" type="submit">Login</button>
                            </div>
                        </form>
                        <p class="text-center">
                            <a href="javascript:void(0)" class="text-primary">Lupa Password?</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/sneat/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/sneat/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/sneat/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/sneat/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('assets/sneat/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/sneat/js/main.js') }}"></script>
    <script>
        $(function() {
            if ($('#alert-message .alert').length) {
                setTimeout(function() {
                    $('#alert-message .alert').slideUp('slow');
                }, 5000);
            }
        });

        $('#form-login').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: new FormData($(this)[0]),
                dataType: 'json',
                contentType: false,
                processData: false,
                success: function(response) {
                    window.location.href = '{{ route('home') }}';
                },
                error: function(xhr, status, error) {
                    if (xhr.responseJSON.error) {
                        $("#alert-message")
                            .html('<div class="alert alert-danger" role="alert">' + xhr.responseJSON
                                .error + '</div>')
                            .slideDown()
                            .delay(3000)
                            .slideUp(function() {
                                $(this).empty().show();
                            });
                    } else {
                        $.each(xhr.responseJSON.errors, function(field, messages) {
                            $('[name="' + field + '"]').addClass('is-invalid');
                            $('#error-' + field).text(messages[0]).show();
                            $('[name="' + field + '"]').keyup(function() {
                                $('[name="' + field + '"]').removeClass('is-invalid');
                                $('#error-' + field).text('').hide();
                            });
                        });
                    }
                }
            });
        });
    </script>
</body>

</html>
