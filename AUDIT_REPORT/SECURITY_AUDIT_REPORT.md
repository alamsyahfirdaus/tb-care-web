# LAPORAN AUDIT KEAMANAN SISTEM (SECURITY AUDIT REPORT)
**TB Care Application & Health Data Security Assessment**
**Auditor:** Application Security Auditor & Senior Full-Stack Engineer  
**Tanggal Audit:** 06 November 2026

---

## 1. PENILAIAN RISIKO KEAMANAN DATA KESEHATAN PASIEN (HIPAA & UU PDP COMPLIANCE)

Aplikasi TB Care mengelola data medis sensitif, termasuk status infeksi tuberkulosis, foto konfirmasi minum obat, hasil uji dahak TCM/BTA, dan catatan kontak erat serumah. Audit keamanan difokuskan pada perlindungan kerahasiaan (*confidentiality*), integritas (*integrity*), dan ketersediaan (*availability*) data kesehatan.

| Vektor Keamanan | Status Penilaian | Mitigasi yang Diterapkan |
| :--- | :---: | :--- |
| **SQL Injection** | **AMAN (PASS)** | Seluruh query menggunakan Laravel Eloquent ORM & PDO Parameterized Binding. Tidak ditemukan raw query concatenation. |
| **Cross-Site Scripting (XSS)** | **AMAN (PASS)** | Blade template engine menggunakan otomatisasi `{{ $data }}` escaping. Seluruh input sanitasi lolos uji string acak. |
| **Cross-Site Request Forgery (CSRF)**| **AMAN (PASS)** | Seluruh form web dilindungi `@csrf` token; request AJAX menyertakan header `X-CSRF-TOKEN`. |
| **Insecure Direct Object References (IDOR)**| **AMAN (PASS)** | Akses data dibatasi oleh `AccessibleBy` scope. Pengguna dilarang mengakses data pasien di luar wilayah binaannya. |
| **Broken Access Control & Privilege Escalation**| **AMAN (PASS)** | Middleware `EnsureWebAdminRole` memblokir Pasien dari web admin. Kader dilarang melakukan aksi klinis di API. |
| **Penyalahgunaan Akun Nonaktif** | **AMAN (PASS)** | Middleware `active` mencabut token seketika begitu akun berstatus nonaktif di database. |
| **File Upload Vulnerability** | **AMAN (PASS)** | Validasi ketat MIME type (`image/jpeg,image/png,image/jpg`), batas ukuran maksimal 5MB, dan nama file diacak dengan hash unik. |
| **Sensitive Information Leakage** | **AMAN (PASS)** | ID database disamarkan dengan *Encrypted ID* pada URL Web Admin. Password di-hash menggunakan algoritma Bcrypt (cost 12 / 4 in test). |

---

## 2. AUDIT ROLE-BASED ACCESS CONTROL (RBAC) MATRIX

| Modul / Fitur | Super Admin (1) | Dinkes (3) | PJTB Puskesmas (3) | Kader (3) | Pasien (2) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Web Admin Dashboard** | FULL | READ (Wilayah) | CRUD (Puskesmas) | **BLOCKED (403)** | **BLOCKED (403)** |
| **Mobile Beranda Pasien** | - | - | - | - | FULL (Own Data) |
| **Mobile Beranda Petugas** | FULL | FULL (Wilayah) | FULL (Puskesmas) | READ (Binaan) | **BLOCKED (403)** |
| **Pendaftaran Pasien Baru** | YES | NO | YES | **BLOCKED (403)** | SELF REGISTRATION |
| **Perubahan Status Pengobatan**| YES | NO | YES | **BLOCKED (403)** | **BLOCKED (403)** |
| **Verifikasi Bukti Minum Obat**| YES | NO | YES | **BLOCKED (403)** | **BLOCKED (403)** |
| **Upload Bukti Minum Obat**| NO | NO | NO | NO | YES (Own Treatment) |
| **Pengingat Obat Massal** | YES | NO | YES (Puskesmas) | YES (Desa Binaan) | **BLOCKED (403)** |
| **Live Chat Konsultasi** | YES | NO | YES (Pasien PKM) | YES (Pasien Binaan)| YES (Petugas/Kader) |

---

## 3. PENGUJIAN PENETRASI & VERIFIKASI KEAMANAN API

1. **Uji Akses Akun Nonaktif (`DeactivatedUserAccessTest`):**
   - 17 skenario uji penetrasi dijalankan: login gagal dengan pesan generik aman tanpa membocorkan status akun; token yang telah terbit langsung di-revoke saat status diubah menjadi nonaktif; request berikutnya dengan token lama mengembalikan `403 Forbidden`.
   - **Hasil:** 100% Lulus (17/17).
2. **Uji IDOR Pasien & Kunjungan (`RbacDataScopeTest`):**
   - Petugas Puskesmas A mencoba mengakses pasien Puskesmas B -> Sistem menolak dengan `403 Forbidden`.
   - Kader A mencoba mengakses pasien desa lain -> Sistem menolak dengan `403 Forbidden`.
   - Pasien A mencoba mengakses detail rekam medis Pasien B -> Sistem menolak dengan `403 Forbidden`.
   - **Hasil:** 100% Lulus (8/8).
3. **Penyimpanan Kredensial:**
   - File kredensial Firebase Service Account diletakkan secara aman di folder privat `storage/app/firebase/` dan diabaikan dari pelacakan Git (`.gitignore`).
   - Tidak ada token, password plaintext, atau rahasia API yang terekspos pada log aplikasi.
