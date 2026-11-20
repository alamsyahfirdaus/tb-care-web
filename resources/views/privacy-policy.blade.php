<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kebijakan Privasi TB Care | Sistem Informasi & Pengawasan Pengobatan Tuberkulosis</title>

    <!-- Meta Tags SEO & Transparansi Data -->
    <meta name="description" content="Kebijakan Privasi resmi aplikasi TB Care. Informasi transparan mengenai pengumpulan, pemanfaatan, penyimpanan, pembatasan akses, dan perlindungan data pasien serta tenaga kesehatan pada ekosistem TB Care.">
    <meta name="keywords" content="TB Care, Kebijakan Privasi, Privacy Policy, Tuberkulosis, UMTAS, Rekam Medis, Pasien TB, Kader TB, Puskesmas">
    <meta name="author" content="Universitas Muhammadiyah Tasikmalaya (UMTAS)">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Metadata Berbagi -->
    <meta property="og:title" content="Kebijakan Privasi TB Care">
    <meta property="og:description" content="Kebijakan Pengelolaan dan Perlindungan Data Pengguna pada Ekosistem Aplikasi TB Care.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/privacy-policy') }}">
    <meta property="og:site_name" content="TB Care">

    <!-- Favicon & Tipografi -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #ffffff;
            --bg-subtle: #f8fafc;
            --text-main: #1f2937;
            --text-muted: #4b5563;
            --text-light: #6b7280;
            --border-color: #e5e7eb;
            --border-light: #f3f4f6;
            --accent: #1b75bb;
            --accent-hover: #12558a;
            --content-width: 760px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
            overflow-x: hidden;
            max-width: 100%;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.8;
            font-size: 0.95rem;
            font-weight: 400;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            max-width: 100%;
        }

        /* Navigasi Atas */
        .site-nav {
            border-bottom: 1px solid var(--border-color);
            background-color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .nav-inner {
            max-width: 1080px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-brand {
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
        }

        .nav-brand-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text-main);
            letter-spacing: -0.2px;
        }

        .nav-brand-org {
            font-size: 0.75rem;
            color: var(--text-light);
            font-weight: 400;
            margin-top: -2px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .nav-link-item {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.15s ease;
        }

        .nav-link-item:hover {
            color: var(--accent);
        }

        .nav-link-button {
            display: inline-block;
            padding: 6px 14px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-main);
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .nav-link-button:hover {
            border-color: var(--accent);
            color: var(--accent);
            background-color: var(--bg-subtle);
        }

        /* Pembungkus Halaman */
        .page-wrapper {
            max-width: 1080px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }

        /* Header Dokumen Resmi */
        .doc-header {
            padding-bottom: 28px;
            margin-bottom: 36px;
            border-bottom: 1px solid var(--border-color);
        }

        .doc-kicker {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--accent);
            margin-bottom: 8px;
        }

        .doc-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-main);
            line-height: 1.3;
            margin-bottom: 12px;
            letter-spacing: -0.4px;
        }

        .doc-lead {
            font-size: 1rem;
            color: var(--text-muted);
            line-height: 1.7;
            max-width: 760px;
            margin-bottom: 18px;
        }

        .doc-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 24px;
            font-size: 0.82rem;
            color: var(--text-light);
            border-top: 1px dashed var(--border-color);
            padding-top: 14px;
        }

        .doc-meta-item strong {
            color: var(--text-main);
            font-weight: 500;
        }

        /* Grid Layout: Sidebar Daftar Isi + Artikel */
        .doc-layout {
            display: flex;
            align-items: flex-start;
            gap: 48px;
        }

        /* Sidebar Daftar Isi */
        .doc-sidebar {
            width: 250px;
            flex-shrink: 0;
            position: sticky;
            top: 80px;
        }

        .toc-box {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 18px;
            background-color: var(--bg-subtle);
        }

        .toc-title {
            font-size: 0.82rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text-main);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
        }

        .toc-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .toc-menu li a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.82rem;
            line-height: 1.4;
            display: block;
            padding: 3px 0;
            transition: color 0.15s ease;
        }

        .toc-menu li a:hover {
            color: var(--accent);
            text-decoration: underline;
        }

        /* Konten Artikel Utama */
        .doc-content {
            flex: 1;
            min-width: 0;
            max-width: var(--content-width);
        }

        .section-block {
            margin-bottom: 40px;
            scroll-margin-top: 90px;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
            letter-spacing: -0.2px;
        }

        .subsection-title {
            font-size: 0.98rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 22px 0 8px;
        }

        p {
            margin-bottom: 14px;
            color: var(--text-main);
            text-align: justify;
        }

        p:last-child {
            margin-bottom: 0;
        }

        ul.list-standard,
        ol.list-standard {
            margin-left: 20px;
            margin-bottom: 16px;
        }

        ul.list-standard li,
        ol.list-standard li {
            margin-bottom: 8px;
            color: var(--text-main);
        }

        ul.list-standard li:last-child,
        ol.list-standard li:last-child {
            margin-bottom: 0;
        }

        /* Kotak Catatan Sederhana */
        .callout-note {
            padding: 14px 18px;
            margin-bottom: 22px;
            background-color: var(--bg-subtle);
            border-left: 3px solid var(--accent);
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.7;
        }

        .callout-note strong {
            color: var(--text-main);
        }

        /* Tabel Institusi Bersih */
        .table-responsive {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 18px 0 24px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            display: block;
        }

        .editorial-table {
            width: 100%;
            min-width: 520px;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }

        .editorial-table th {
            background-color: var(--bg-subtle);
            color: var(--text-main);
            font-weight: 600;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .editorial-table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: top;
            color: var(--text-main);
            line-height: 1.6;
        }

        .editorial-table tr:last-child td {
            border-bottom: none;
        }

        /* Daftar Data yang Dikumpulkan */
        .data-group {
            margin-bottom: 20px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--border-light);
        }

        .data-group:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .data-group-title {
            font-size: 0.96rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .data-group-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.7;
        }

        /* Kotak Kontak Sederhana */
        .contact-panel {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 20px;
            background-color: var(--bg-subtle);
            margin-top: 14px;
        }

        .contact-row {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .contact-row-item {
            display: flex;
            flex-direction: column;
            font-size: 0.88rem;
        }

        .contact-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--text-light);
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .contact-value {
            color: var(--text-main);
            font-weight: 500;
            word-break: break-word;
        }

        .contact-value a {
            color: var(--accent);
            text-decoration: none;
        }

        .contact-value a:hover {
            text-decoration: underline;
        }

        /* Footer Halaman */
        .site-footer {
            border-top: 1px solid var(--border-color);
            background-color: #ffffff;
            padding: 30px 24px;
            margin-top: 40px;
        }

        .footer-inner {
            max-width: 1080px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 12px;
            font-size: 0.82rem;
            color: var(--text-light);
        }

        .footer-nav {
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .footer-nav a {
            color: var(--text-muted);
            text-decoration: none;
        }

        .footer-nav a:hover {
            color: var(--accent);
        }

        /* Penyesuaian Responsif Tablet & Mobile */
        @media (max-width: 860px) {
            .doc-layout {
                display: block;
                width: 100%;
            }

            .doc-sidebar {
                width: 100%;
                position: static;
                margin-bottom: 28px;
            }

            .doc-content {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }

            .toc-box {
                padding: 14px 16px;
            }

            .toc-menu {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 6px 12px;
            }

            .page-wrapper {
                padding: 24px 16px 60px;
                max-width: 100%;
                overflow-x: hidden;
            }

            .doc-title {
                font-size: 1.6rem;
            }

            .section-title {
                font-size: 1.15rem;
            }

            p {
                text-align: left;
            }
        }

        @media (max-width: 540px) {
            .nav-inner {
                padding: 12px 16px;
            }

            .doc-meta {
                flex-direction: column;
                gap: 4px;
            }

            .toc-menu {
                grid-template-columns: 1fr;
            }
        }

        /* Mode Cetak Bersih */
        @media print {
            .site-nav,
            .doc-sidebar,
            .site-footer,
            .nav-link-button {
                display: none !important;
            }

            body {
                background: #ffffff;
                color: #000000;
                font-size: 11pt;
                line-height: 1.5;
            }

            .page-wrapper {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            .doc-header {
                border-bottom: 1px solid #000000;
                padding-bottom: 14px;
                margin-bottom: 20px;
            }

            .section-block {
                page-break-inside: avoid;
                margin-bottom: 20px;
            }

            a {
                color: #000000;
                text-decoration: none;
            }

            .table-responsive {
                border: 1px solid #000000;
            }

            .editorial-table th,
            .editorial-table td {
                border-bottom: 1px solid #000000;
            }
        }
    </style>
</head>

<body>

    <!-- Bar Navigasi Sederhana -->
    <header class="site-nav">
        <div class="nav-inner">
            <a href="{{ route('portal') }}" class="nav-brand">
                <span class="nav-brand-title">TB Care</span>
                <span class="nav-brand-org">Universitas Muhammadiyah Tasikmalaya</span>
            </a>
            <nav class="nav-links">
                <a href="{{ route('portal') }}" class="nav-link-item">Beranda</a>
                <a href="{{ route('login') }}" class="nav-link-item">Portal Admin</a>
                <a href="javascript:window.print()" class="nav-link-button">Cetak</a>
            </nav>
        </div>
    </header>

    <!-- Konten Dokumen Utama -->
    <main class="page-wrapper">

        <!-- Header Dokumen Resmi -->
        <header class="doc-header">
            <div class="doc-kicker">Dokumen Resmi</div>
            <h1 class="doc-title">Kebijakan Privasi TB Care</h1>
            <p class="doc-lead">
                Informasi mengenai pengumpulan, penggunaan, penyimpanan, pembatasan akses, dan perlindungan data pengguna dalam sistem informasi TB Care.
            </p>
            <div class="doc-meta">
                <div class="doc-meta-item">Pengelola: <strong>Universitas Muhammadiyah Tasikmalaya (UMTAS)</strong></div>
                <div class="doc-meta-item">Terakhir Diperbarui: <strong>20 November 2026</strong></div>
                <div class="doc-meta-item">Domain: <strong>tbcare.umtas.ac.id</strong></div>
                <div class="doc-meta-item">Aplikasi: <strong>id.alsyafir.tbcare</strong></div>
            </div>
        </header>

        <!-- Layout Dua Kolom: Daftar Isi + Artikel -->
        <div class="doc-layout">

            <!-- Sidebar Navigasi Cepat -->
            <aside class="doc-sidebar" aria-label="Daftar Isi">
                <div class="toc-box">
                    <div class="toc-title">Daftar Isi</div>
                    <ul class="toc-menu">
                        <li><a href="#pendahuluan">1. Pendahuluan</a></li>
                        <li><a href="#peran-pengguna">2. Peran Pengguna</a></li>
                        <li><a href="#data-yang-dikumpulkan">3. Data yang Dikumpulkan</a></li>
                        <li><a href="#tujuan-penggunaan">4. Tujuan Penggunaan</a></li>
                        <li><a href="#akses-pengelolaan">5. Akses & Pembatasan Data</a></li>
                        <li><a href="#penyimpanan-data">6. Penyimpanan Data</a></li>
                        <li><a href="#pembagian-data">7. Pembagian Data</a></li>
                        <li><a href="#keamanan-data">8. Keamanan Data</a></li>
                        <li><a href="#retensi-data">9. Retensi Data</a></li>
                        <li><a href="#hak-pengguna">10. Hak Pengguna</a></li>
                        <li><a href="#privasi-anak">11. Pasien Usia Anak</a></li>
                        <li><a href="#perubahan-kebijakan">12. Perubahan Kebijakan</a></li>
                        <li><a href="#kontak">13. Kontak Pengelola</a></li>
                    </ul>
                </div>
            </aside>

            <!-- Isi Artikel Kebijakan Privasi -->
            <article class="doc-content">

                <!-- 1. Pendahuluan -->
                <section id="pendahuluan" class="section-block">
                    <h2 class="section-title">1. Pendahuluan</h2>
                    <p>
                        Aplikasi <strong>TB Care</strong> adalah sistem informasi kesehatan terintegrasi yang dirancang untuk mendukung pemantauan, pendampingan kepatuhan minum obat, investigasi kontak serumah, penapisan gejala mandiri, dan penyuluhan kesehatan bagi pasien Tuberkulosis (TBC). Ekosistem TB Care mencakup aplikasi mobile Android (<em>package: <code>id.alsyafir.tbcare</code></em>) serta portal web operasional fasilitas pelayanan kesehatan (<em><code>tbcare.umtas.ac.id</code></em>).
                    </p>
                    <p>
                        Kebijakan Privasi ini disusun sebagai wujud transparansi pengelola kepada seluruh pengguna mengenai cara sistem mengumpulkan, memproses, menyimpan, melindungi, dan membatasi akses terhadap data. Dokumen ini dapat diakses secara publik kapan saja tanpa memerlukan autentikasi login atau pembuatan akun.
                    </p>
                    <p>
                        Dengan mendaftar, memasang, atau menggunakan aplikasi TB Care, pengguna mengakui bahwa pengelolaan data pada akunnya berlangsung sesuai dengan prinsip dan ketentuan yang tercantum dalam dokumen ini.
                    </p>
                </section>

                <!-- 2. Peran Pengguna Aplikasi -->
                <section id="peran-pengguna" class="section-block">
                    <h2 class="section-title">2. Peran Pengguna Aplikasi</h2>
                    <p>
                        TB Care menerapkan pengelolaan data berjenjang berdasarkan peran (<em>Role-Based Access Control</em>) untuk memastikan data rekam medis hanya dapat diakses oleh pihak yang sah sesuai fungsi dan batas kewenangannya:
                    </p>

                    <div class="table-responsive">
                        <table class="editorial-table">
                            <thead>
                                <tr>
                                    <th style="width: 26%;">Peran Pengguna</th>
                                    <th style="width: 22%;">Kanal Akses</th>
                                    <th>Cakupan Kewenangan dan Batasan Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Pasien TB</strong></td>
                                    <td>Mobile Android</td>
                                    <td>Mengisi konfirmasi minum obat harian, mengunggah foto bukti minum obat, melihat jadwal kontrol, mendaftarkan kontak erat keluarga, mengakses materi edukasi, dan berkonsultasi dengan petugas. Akses ke portal web diblokir oleh sistem.</td>
                                </tr>
                                <tr>
                                    <td><strong>Kader Puskesmas</strong></td>
                                    <td>Mobile Android</td>
                                    <td>Mendampingi pasien dan memverifikasi asupan obat khusus pasien yang tinggal di desa/kelurahan binaan (<em>kader areas</em>) penugasan Puskesmas. Akses ke portal web diblokir oleh sistem.</td>
                                </tr>
                                <tr>
                                    <td><strong>Petugas PJTB Puskesmas</strong></td>
                                    <td>Web & Mobile</td>
                                    <td>Tenaga kesehatan penanggung jawab program TB di tingkat Puskesmas. Mengelola rekam medis pasien, episode pengobatan, jadwal kontrol, pembinaan kader, dan koordinasi wilayah kerja Puskesmas terkait.</td>
                                </tr>
                                <tr>
                                    <td><strong>Petugas Dinas Kesehatan</strong><br><small style="color: var(--text-light);">(Kota/Kabupaten & Provinsi)</small></td>
                                    <td>Web & Mobile</td>
                                    <td>Memantau rekapitulasi data agregat, surveilans epidemiologis, dan laporan program TB sesuai wilayah administratif (Dinkes Kota/Kabupaten Tasikmalaya dan Dinkes Provinsi Jawa Barat).</td>
                                </tr>
                                <tr>
                                    <td><strong>Administrator Sistem</strong></td>
                                    <td>Portal Web</td>
                                    <td>Mengelola keandalan teknis sistem, data master fasilitas kesehatan dan wilayah, audit log aktivitas operasional, serta pemeliharaan versi aplikasi.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- 3. Data yang Dikumpulkan -->
                <section id="data-yang-dikumpulkan" class="section-block">
                    <h2 class="section-title">3. Data yang Dikumpulkan oleh TB Care</h2>
                    <p>
                        TB Care hanya mengumpulkan data yang relevan dan dibutuhkan untuk menjalankan pendampingan terapi, pencatatan rekam medis penanggulangan TB, dan operasional layanan. Data aktual yang dikumpulkan meliputi:
                    </p>

                    <div class="data-group">
                        <div class="data-group-title">A. Data Identitas dan Akun Pengguna</div>
                        <p class="data-group-desc">
                            Nama lengkap, nama pengguna (<em>username</em>), nomor telepon seluler/WhatsApp, alamat surat elektronik (email, opsional), jenis kelamin, tempat dan tanggal lahir, foto profil (opsional), serta kata sandi akun yang disimpan dalam bentuk hash kriptografi satu arah (bcrypt).
                        </p>
                    </div>

                    <div class="data-group">
                        <div class="data-group-title">B. Data Medis dan Demografis Pasien</div>
                        <p class="data-group-desc">
                            Nomor Induk Kependudukan (NIK 16 digit untuk verifikasi kependudukan dan penelusuran faskes), alamat domisili lengkap (RT, RW, Desa/Kelurahan, Kecamatan, Kabupaten/Kota), Puskesmas pembina, pekerjaan, data antropometri fisik (tinggi badan, berat badan, golongan darah), riwayat penyakit penyerta/komorbiditas (seperti Diabetes Melitus, HIV, atau kondisi kronis lain), tanggal diagnosis TB, dan tanggal mulai terapi.
                        </p>
                    </div>

                    <div class="data-group">
                        <div class="data-group-title">C. Data Pengobatan dan Pemantauan Kepatuhan Minum Obat</div>
                        <p class="data-group-desc">
                            Tipe regimen pengobatan (Kategori 1, Kategori 2, TB Anak, atau TB Resistan Obat), tanggal mulai dan estimasi selesai pengobatan, durasi hari terapi, jadwal jam minum obat harian, rincian resep obat, serta catatan konfirmasi minum obat harian. Konfirmasi mencakup foto bukti fisik saat minum obat yang diambil melalui kamera perangkat, stempel waktu (<em>timestamp</em>), status minum obat (Diminum, Terlewat, Terverifikasi), sumber konfirmasi (pasien atau kader), catatan keluhan efek samping yang dialami pasien, serta catatan telaah verifikasi dari petugas faskes.
                        </p>
                    </div>

                    <div class="data-group">
                        <div class="data-group-title">D. Data Kontak Erat Serumah (Investigasi Kontak)</div>
                        <p class="data-group-desc">
                            Data anggota keluarga atau orang yang tinggal serumah dengan pasien TB untuk pelacakan dini penularan: nama kontak, NIK, hubungan kekerabatan, jenis kelamin, tanggal lahir/usia, tanggal penapisan gejala, hasil penapisan, dan status Terapi Pencegahan Tuberkulosis (TPT).
                        </p>
                    </div>

                    <div class="data-group">
                        <div class="data-group-title">E. Data Skrining Gejala Mandiri dan Kontak</div>
                        <p class="data-group-desc">
                            Hasil penapisan gejala tuberkulosis berdasarkan kelompok usia (anak &lt;15 tahun atau dewasa &ge;15 tahun), klasifikasi dugaan (TB Paru atau TB Ekstra Paru), riwayat jawaban atas kuesioner gejala (batuk berdahak &ge;2 minggu, demam hilang timbul, keringat malam tanpa aktivitas fisik, penurunan berat badan, penurunan nafsu makan, dan riwayat kontak erat), skor total risiko (Risiko Rendah atau Risiko Tinggi), serta rekomendasi tindak lanjut ke Puskesmas.
                        </p>
                    </div>

                    <div class="data-group">
                        <div class="data-group-title">F. Data Jadwal Kontrol dan Kunjungan Faskes</div>
                        <p class="data-group-desc">
                            Tanggal dan jam rencana kunjungan pemeriksaan ulang/kontrol pasien ke Puskesmas pembina, status kehadiran (Terjadwal, Hadir, Batal), dan catatan arahan kunjungan dari tenaga kesehatan.
                        </p>
                    </div>

                    <div class="data-group">
                        <div class="data-group-title">G. Data Pemeriksaan Klinis dan Penunjang Medis</div>
                        <p class="data-group-desc">
                            Nomor registrasi pemeriksaan klinis, jenis pemeriksaan medis (Tes Cepat Molekuler/TCM, mikroskopis BTA sputum dahak, foto rontgen dada/thorax), hasil laboratorium, kesimpulan diagnosis medis, catatan petugas laboratorium, dan file lampiran bukti pemeriksaan bila diunggah oleh petugas berwenang.
                        </p>
                    </div>

                    <div class="data-group">
                        <div class="data-group-title">H. Data Komunikasi (Fitur Konsultasi / Live Chat)</div>
                        <p class="data-group-desc">
                            Pesan teks komunikasi dua arah antara pasien dengan petugas Puskesmas atau kader pendamping terkait pengobatan tuberkulosis, file lampiran gambar yang dikirimkan dalam konsultasi, waktu pengiriman, waktu penerimaan, dan status terbaca.
                        </p>
                    </div>

                    <div class="data-group">
                        <div class="data-group-title">I. Data Teknis Perangkat dan Log Aktivitas</div>
                        <p class="data-group-desc">
                            Token perangkat <em>Firebase Cloud Messaging</em> (FCM Token) yang digunakan secara eksklusif untuk mengirimkan notifikasi pengingat minum obat, jadwal kontrol, dan pesan konsultasi ke perangkat pengguna. Pada portal web administrator, sistem mencatat alamat IP peramban, tipe peramban (<em>user agent</em>), modul yang diakses, jenis tindakan administratif, dan stempel waktu untuk kebutuhan audit operasional.
                        </p>
                    </div>
                </section>

                <!-- 4. Tujuan Penggunaan Data -->
                <section id="tujuan-penggunaan" class="section-block">
                    <h2 class="section-title">4. Tujuan Penggunaan Data</h2>
                    <p>
                        Seluruh data yang dimasukkan ke dalam aplikasi TB Care digunakan secara langsung untuk mendukung operasional layanan kesehatan:
                    </p>
                    <ul class="list-standard">
                        <li><strong>Autentikasi Pengguna:</strong> Memverifikasi identitas pengguna saat masuk aplikasi dan mengarahkan antarmuka sesuai batas perannya.</li>
                        <li><strong>Pemantauan Kepatuhan Minum Obat:</strong> Membantu pasien mengingat waktu minum obat setiap hari serta menyediakan sarana verifikasi foto bukti bagi kader dan petugas Puskesmas guna memastikan terapi tuntas berbulan-bulan, mencegah putus berobat (<em>loss to follow-up</em>), dan mencegah kuman TB Resistan Obat.</li>
                        <li><strong>Pengelolaan Jadwal Kunjungan:</strong> Membantu pasien dan fasilitas kesehatan menyepakati serta mengingat jadwal kontrol evaluasi dahak, penimbangan berat badan, dan pengambilan paket obat berikutnya.</li>
                        <li><strong>Pelacakan Kontak Erat:</strong> Menemukan sedini mungkin kasus penularan di lingkungan keluarga atau orang yang tinggal serumah dengan pasien untuk segera dilakukan skrining dan pemberian terapi pencegahan (TPT).</li>
                        <li><strong>Saluran Komunikasi Langsung:</strong> Memfasilitasi konsultasi langsung antara pasien dengan tenaga kesehatan mengenai efek samping obat atau keluhan medis tanpa harus menunggu jadwal kunjungan.</li>
                        <li><strong>Pengiriman Notifikasi Pengingat:</strong> Mengirimkan pemberitahuan push notification ke ponsel pengguna terkait jadwal minum obat harian, jadwal kontrol, dan materi edukasi baru.</li>
                        <li><strong>Penyuluhan Edukasi:</strong> Menyajikan informasi kesehatan terpercaya mengenai tata laksana tuberkulosis, etika batuk, dan gizi penunjang pemulihan.</li>
                        <li><strong>Surveilans dan Pelaporan Program TB:</strong> Menyediakan data agregat yang akurat bagi pengelola program tuberkulosis di Puskesmas dan Dinas Kesehatan untuk evaluasi cakupan pengobatan dan angka kesembuhan wilayah.</li>
                    </ul>
                </section>

                <!-- 5. Akses dan Pengelolaan Data -->
                <section id="akses-pengelolaan" class="section-block">
                    <h2 class="section-title">5. Akses dan Pembatasan Pengelolaan Data</h2>
                    <p>
                        Aplikasi TB Care menerapkan prinsip <em>Least Privilege</em> (hak akses seminimal mungkin yang diperlukan) dan pemisahan kewenangan yang tegas:
                    </p>
                    <ul class="list-standard">
                        <li><strong>Pasien:</strong> Hanya dapat mengakses data pribadinya sendiri, rekam pengobatan miliknya, daftar kontak erat keluarganya, dan ruang percakapan dengan petugas pembina. Pasien diblokir secara sistem dari portal web admin.</li>
                        <li><strong>Kader TB:</strong> Hanya dapat melihat daftar dan memverifikasi obat pasien yang tinggal di desa atau wilayah binaan yang secara resmi ditugaskan oleh Puskesmas. Kader dibatasi pada aplikasi mobile dan tidak memiliki izin mengakses web admin.</li>
                        <li><strong>Petugas PJTB Puskesmas:</strong> Akses data dibatasi pada pasien, kader, dan kegiatan penanggulangan TB di bawah naungan Puskesmas tempatnya bertugas.</li>
                        <li><strong>Dinas Kesehatan:</strong> Akses data terfokus pada pemantauan dan laporan rekapitulasi wilayah kerja administratif kabupaten/kota atau provinsi terkait.</li>
                        <li><strong>Proteksi URL dan Enkripsi ID:</strong> Portal web TB Care menerapkan enkripsi pengenal data (<em>encrypted identifier</em>) pada parameter tautan untuk mencegah pihak luar menebak atau memanipulasi nomor rekam medis (mencegah kerentanan <em>ID enumeration</em>).</li>
                    </ul>
                </section>

                <!-- 6. Penyimpanan Data -->
                <section id="penyimpanan-data" class="section-block">
                    <h2 class="section-title">6. Penyimpanan Data</h2>
                    <p>
                        Data aplikasi TB Care disimpan secara elektronik pada peladen basis data terkelola dengan protokol keamanan yang wajar:
                    </p>
                    <ul class="list-standard">
                        <li><strong>Enkripsi Kata Sandi:</strong> Kata sandi pengguna tidak disimpan dalam bentuk teks biasa, melainkan di-<em>hash</em> menggunakan algoritma kriptografi satu arah (bcrypt).</li>
                        <li><strong>Sesi Terotentikasi:</strong> Komunikasi antara aplikasi mobile Android dan peladen web menggunakan token otentikasi aman (Laravel Sanctum) melalui koneksi HTTPS terenkripsi. Token ini dicabut saat pengguna keluar atau akun dinonaktifkan.</li>
                        <li><strong>Penyimpanan Berkas Foto:</strong> Foto bukti minum obat dan dokumen pemeriksaan disimpan dalam direktori penyimpanan sistem yang dilindungi dan hanya dapat diakses melalui mekanisme tautan aplikasi yang divalidasi.</li>
                    </ul>
                </section>

                <!-- 7. Pembagian dan Pengungkapan Data -->
                <section id="pembagian-data" class="section-block">
                    <h2 class="section-title">7. Pembagian dan Pengungkapan Data</h2>
                    <p>
                        <strong>Data pengguna tidak pernah dijual, disewakan, atau diperdagangkan kepada pihak ketiga manapun untuk tujuan komersial maupun periklanan.</strong>
                    </p>
                    <p>
                        Data hanya dapat dibagikan atau diakses dalam lingkup penanggulangan tuberkulosis resmi pada kondisi berikut:
                    </p>
                    <ul class="list-standard">
                        <li><strong>Fasilitas Pelayanan Kesehatan Terkait:</strong> Data pasien dibagikan kepada dokter, perawat penanggung jawab TB, dan kader pendamping di Puskesmas wilayah domisili pasien untuk kepentingan pelayanan medis langsung.</li>
                        <li><strong>Dinas Kesehatan:</strong> Data agregat dan laporan epidemiologis disampaikan secara berjenjang ke Dinas Kesehatan Kota/Kabupaten Tasikmalaya dan Dinas Kesehatan Provinsi Jawa Barat sebagai bagian dari program penanggulangan penyakit menular nasional.</li>
                        <li><strong>Layanan Pendukung Fungsional:</strong>
                            <ul style="margin-left: 20px; margin-top: 6px;">
                                <li><em>Google Firebase Cloud Messaging (FCM):</em> Digunakan semata-mata untuk mengantarkan sinyal notifikasi push pengingat minum obat dan jadwal kontrol ke perangkat Android. Payload notifikasi didesain minim data tanpa menyertakan rincian rekam medis sensitif.</li>
                                <li><em>YouTube (Google LLC):</em> Digunakan untuk menayangkan video edukasi kesehatan publik yang disematkan dalam aplikasi.</li>
                                <li><em>Jaringan Keamanan Cloudflare:</em> Digunakan pada tingkat infrastruktur domain web untuk menyediakan sertifikat SSL/HTTPS, proteksi lalu lintas, dan penyaringan serangan siber.</li>
                            </ul>
                        </li>
                        <li><strong>Kewajiban Hukum:</strong> Data dapat diungkapkan apabila secara tegas diwajibkan oleh ketentuan perundang-undangan Republik Indonesia yang berlaku, putusan pengadilan yang sah, atau instruksi resmi otoritas kesehatan dalam situasi darurat kesehatan masyarakat.</li>
                    </ul>
                </section>

                <!-- 8. Keamanan Data -->
                <section id="keamanan-data" class="section-block">
                    <h2 class="section-title">8. Keamanan Data</h2>
                    <p>
                        Pengelola menerapkan langkah-langkah keamanan teknis dan organisasi yang wajar dan proporsional untuk melindungi data dari akses tanpa hak, manipulasi, kehilangan, atau pengungkapan yang tidak sah. Pengamanan ini mencakup komunikasi jaringan protokol HTTPS/TLS, validasi permintaan API berbasis token individual, pembatasan hak akses berbasis wilayah kerja, pencatatan jejak audit (<em>audit logs</em>), serta pemisahan data sensitif dari payload notifikasi publik.
                    </p>
                    <p>
                        Meskipun demikian, disadari bahwa tidak ada metode transmisi data melalui internet atau media penyimpanan elektronik yang dapat digaransi bebas dari seluruh potensi gangguan tanpa terkecuali. Pengelola secara berkelanjutan melakukan pembaruan kode sumber, penambalan celah keamanan, dan peninjauan arsitektur untuk mempertahankan standar perlindungan data yang andal.
                    </p>
                </section>

                <!-- 9. Retensi Data -->
                <section id="retensi-data" class="section-block">
                    <h2 class="section-title">9. Retensi dan Masa Simpan Data</h2>
                    <p>
                        Data rekam pengobatan dan pemantauan pasien disimpan selama masa terapi aktif berlangsung (umumnya berkisar antara 6 bulan untuk TB sensitif obat hingga 24 bulan untuk kasus tertentu).
                    </p>
                    <p>
                        Setelah periode terapi dinyatakan selesai (sembuh atau pengobatan lengkap), rekam data pengobatan dan riwayat penapisan tetap disimpan dalam basis data sebagai arsip rekam medis kesehatan elektronik. Hal ini diperlukan untuk mendukung evaluasi klinis lanjutan apabila terjadi kekambuhan (relaps), kebutuhan rujukan fasilitas kesehatan di masa mendatang, serta kewajiban pelaporan riwayat penanggulangan tuberkulosis sesuai ketentuan rekam medis di fasilitas pelayanan kesehatan.
                    </p>
                    <p>
                        Token sesi perangkat (FCM Token dan token akses API) akan dihapus secara otomatis dari basis data saat pengguna keluar (<em>logout</em>) dari aplikasi atau saat akun pengguna dinonaktifkan oleh administrator.
                    </p>
                </section>

                <!-- 10. Hak dan Permintaan Pengguna -->
                <section id="hak-pengguna" class="section-block">
                    <h2 class="section-title">10. Hak dan Permintaan Pengguna</h2>
                    <p>
                        Pengguna memiliki hak atas data pribadi yang tersimpan dalam sistem TB Care sebagai berikut:
                    </p>
                    <ul class="list-standard">
                        <li><strong>Pembaruan Data Mandiri:</strong> Pengguna dapat memperbarui informasi nama, nomor telepon seluler, foto profil, dan kata sandi akun secara mandiri melalui menu Pengaturan Profil di dalam aplikasi.</li>
                        <li><strong>Koreksi Data Rekam Medis:</strong> Demi menjaga integritas data klinis dan mencegah kekeliruan resep, perubahan pada data vital seperti NIK, Puskesmas pembina, diagnosis, atau riwayat obat hanya dapat dilakukan melalui verifikasi oleh Petugas PJTB di Puskesmas terdaftar.</li>
                        <li><strong>Permohonan Penonaktifan Akun atau Peninjauan Data:</strong> Pengguna yang ingin menonaktifkan akun, mengajukan pertanyaan mengenai data yang tersimpan, atau mengajukan permohonan terkait data dapat menghubungi Petugas PJTB di Puskesmas pembina atau menghubungi saluran kontak resmi pengelola. Permintaan akan ditinjau dengan memperhatikan kewajiban hukum pelaporan dan penyimpanan data rekam medis penyakit menular yang diatur oleh otoritas kesehatan.</li>
                    </ul>
                </section>

                <!-- 11. Pasien Usia Anak -->
                <section id="privasi-anak" class="section-block">
                    <h2 class="section-title">11. Pasien Usia Anak (&lt;15 Tahun)</h2>
                    <p>
                        Penyakit tuberkulosis dapat menjangkiti individu dari segala rentang usia, termasuk anak-anak (kategori TB Anak usia &lt;15 tahun). Aplikasi TB Care menyediakan kuesioner penapisan khusus anak dan pemantauan pengobatan anak.
                    </p>
                    <p>
                        Seluruh proses pendaftaran akun, pengisian skrining gejala anak, pengunggahan foto bukti minum obat, dan pemantauan jadwal kontrol untuk pasien anak wajib dilakukan di bawah pendampingan, pengawasan, dan persetujuan orang tua, wali sah, atau petugas kesehatan keluarga yang mendampingi. Pengelola tidak secara sengaja mengumpulkan data anak tanpa keterlibatan orang tua atau wali yang bertanggung jawab.
                    </p>
                </section>

                <!-- 12. Perubahan Kebijakan Privasi -->
                <section id="perubahan-kebijakan" class="section-block">
                    <h2 class="section-title">12. Perubahan Kebijakan Privasi</h2>
                    <p>
                        Kebijakan Privasi ini dapat diperbarui dari waktu ke waktu untuk menyesuaikan dengan pengembangan fitur aplikasi, integrasi teknologi baru, atau pembaruan regulasi hukum dan kesehatan digital di Indonesia.
                    </p>
                    <p>
                        Setiap perubahan akan dicantumkan secara langsung pada halaman ini dengan memperbarui tanggal "Terakhir Diperbarui" pada bagian atas dokumen. Pengguna dianjurkan untuk meninjau halaman ini secara berkala. Penggunaan aplikasi yang berkelanjutan setelah adanya pembaruan menandakan persetujuan pengguna terhadap kebijakan yang diperbarui.
                    </p>
                </section>

                <!-- 13. Kontak Resmi Pengelola -->
                <section id="kontak" class="section-block">
                    <h2 class="section-title">13. Kontak Resmi Pengelola</h2>
                    <p>
                        Apabila pengguna memiliki pertanyaan, kendala teknis, atau hendak mengajukan permohonan terkait pengelolaan data pribadi dalam aplikasi TB Care, silakan menghubungi pengelola resmi melalui saluran berikut:
                    </p>

                    <div class="contact-panel">
                        <div class="contact-row">
                            <div class="contact-row-item">
                                <span class="contact-label">Institusi Pengelola</span>
                                <span class="contact-value">Universitas Muhammadiyah Tasikmalaya (UMTAS)</span>
                            </div>
                            <div class="contact-row-item">
                                <span class="contact-label">Portal Resmi</span>
                                <span class="contact-value"><a href="https://tbcare.umtas.ac.id" target="_blank" rel="noopener noreferrer">https://tbcare.umtas.ac.id</a></span>
                            </div>
                            <div class="contact-row-item">
                                <span class="contact-label">Layanan Bantuan WhatsApp</span>
                                <span class="contact-value"><a href="https://wa.me/6282215161998" target="_blank" rel="noopener noreferrer">+62 822-1516-1998</a></span>
                            </div>
                            <div class="contact-row-item">
                                <span class="contact-label">Alamat Kampus</span>
                                <span class="contact-value">Jl. Tamansari No.KM 2,5, Mulyasari, Kec. Tamansari, Kota Tasikmalaya, Jawa Barat 46196</span>
                            </div>
                            <div class="contact-row-item">
                                <span class="contact-label">Layanan Langsung Fasilitas Kesehatan</span>
                                <span class="contact-value">Pasien terdaftar dapat berkonsultasi atau mengajukan pembaruan data medis secara langsung melalui Petugas Penanggung Jawab TB (PJTB) pada unit Puskesmas pembina masing-masing di wilayah Kabupaten dan Kota Tasikmalaya.</span>
                            </div>
                        </div>
                    </div>
                </section>

            </article>
        </div>
    </main>

    <!-- Footer Dokumen -->
    <footer class="site-footer">
        <div class="footer-inner">
            <nav class="footer-nav">
                <a href="{{ route('portal') }}">Beranda TB Care</a>
                <a href="{{ route('privacy-policy') }}">Kebijakan Privasi</a>
                <a href="{{ route('login') }}">Portal Login Web</a>
                <a href="https://wa.me/6282215161998" target="_blank" rel="noopener noreferrer">Bantuan WhatsApp</a>
                <a href="#top" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">Kembali ke Atas</a>
            </nav>
            <p>&copy; 2024&ndash;{{ date('Y') }} <strong>TB Care</strong> &mdash; Universitas Muhammadiyah Tasikmalaya (UMTAS). Seluruh hak cipta dilindungi undang-undang.</p>
        </div>
    </footer>

</body>

</html>
