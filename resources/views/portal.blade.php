<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TB Care</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dist/css/tbcare.css') }}">
</head>

<body class="hold-transition register-page" style="font-family: 'Poppins', sans-serif !important">
    <div class="register-box">
        <div class="card card-outline card-primary">
            <div class="card-header py-2">
                <h3 style="text-align: center; font-weight: bold; margin: 0; color: #000;">TB Care</h3>
            </div>
            <div class="card-body">
                <div class="social-auth-links text-center mb-3">
                    <a href="#" class="btn btn-block btn-primary mb-3">
                        <i class="fab fa-google-play mr-2"></i>
                        Unduh Aplikasi Android
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-block btn-outline-primary">
                        <i class="fas fa-user-shield mr-2"></i>
                        Login Administrator
                    </a>
                </div>
                <div class="text-center" style="font-size: 14px;">
                    Butuh Bantuan? <br class="m-0">
                    <a href="https://wa.me/62893829624" target="_blank" rel="noopener noreferrer">Hubungi
                        admin melalui WhatsApp</a>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/dist/js/tbcare.js') }}"></script>
</body>

</html>
