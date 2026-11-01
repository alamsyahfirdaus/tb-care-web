# DOKUMENTASI ARSITEKTUR & IMPLEMENTASI ROLE-BASED ACCESS CONTROL (RBAC)
# TB CARE WEB & MOBILE

---

| Dokumen | Panduan Teknis & Arsitektur Keamanan Hak Akses Pengguna |
| :--- | :--- |
| **Aplikasi** | TB Care Web (Web Petugas & Administrator) & TB Care Mobile (API) |
| **Lokasi Proyek** | `D:\My Projects\AplikasiTBCare\tb-care-web` |
| **Versi Dokumen** | 1.0 (Produksi) |
| **Tanggal Implementasi** | 24 Oktober 2026 |
| **Prinsip Utama** | *Least Privilege*, *Defense in Depth*, *Fail-Closed Policy*, & *Server-Side Scoping* |

---

## 1. PENDAHULUAN & PRINSIP ARSITEKTUR

Aplikasi **TB Care** dirancang untuk melayani berbagai tingkatan pengguna dalam tata kelola penanggulangan tuberkulosis, mulai dari pembuat kebijakan di tingkat dinas kesehatan, tenaga medis operasional di Puskesmas, relawan kader di desa/kelurahan, hingga pasien yang sedang menjalani pengobatan.

Untuk menjamin kerahasiaan data medis (*data privacy*) serta mencegah akses lintas batas kewenangan (*cross-tenant / IDOR risks*), arsitektur RBAC TB Care dibangun di atas fondasi prinsip:

1. **Least Privilege (Hak Akses Minimum):** Setiap peran pengguna hanya diberikan akses terhadap menu, fitur, dan data yang mutlak diperlukan untuk menyelesaikan tugas kedinasannya.
2. **Defense in Depth (Pertahanan Berlapis):** Pembatasan tidak hanya dilakukan dengan menyembunyikan elemen antarmuka (*UI hiding*), melainkan ditegakkan secara ketat pada layer HTTP Route, Middleware, Controller, Policy/Gate, dan Query Database (Eloquent Scope).
3. **Fail-Closed (Tolak Akses secara Default):** Apabila seorang petugas tidak memiliki penugasan wilayah kerja yang sah (*unassigned territory*), sistem secara otomatis mengunci query data (`whereRaw('1 = 0')`), sehingga data faskes atau pasien lain tidak pernah bocor.
4. **Isolasi Kanal Antarmuka (Web vs Mobile):**
   - **Web Admin:** Dikhususkan bagi Administrator, Dinkes Provinsi, Dinkes Kab/Kota, dan PJTB Puskesmas untuk kebutuhan administrasi, pencatatan klinis, dan pemantauan epidemiologi.
   - **Mobile App:** Dikhususkan bagi Pasien TB (minum obat & konsultasi) dan Kader Puskesmas (kunjungan lapangan & investigasi kontak desa binaan).

---

## 2. TAKSONOMI ROLE & IDENTIFIKASI SISTEM

Pengenalan peran pengguna diatur melalui kombinasi dua tabel referensi: `users.user_type_id` dan `officers.officer_type_id`.

```text
                                  +------------------+
                                  |   Tabel: users   |
                                  |   user_type_id   |
                                  +--------+---------+
                                           |
                 +-------------------------+-------------------------+
                 |                         |                         |
                 v                         v                         v
        user_type_id = 1          user_type_id = 2          user_type_id = 3
     [Super Administrator]          [Pasien TB]                 [Petugas]
     (Akses Penuh Sistem)       (Khusus Mobile App)                  |
                                                                     v
                                                            +------------------+
                                                            |  Tabel: officers |
                                                            |  officer_type_id |
                                                            +--------+---------+
                                                                     |
                 +-------------------+-------------------+-----------+-----------+
                 |                   |                   |                       |
                 v                   v                   v                       v
        officer_type_id = 1  officer_type_id = 2  officer_type_id = 3   officer_type_id = 4
        [Dinkes Provinsi]   [Dinkes Kab/Kota]    [PJTB Puskesmas]       [Kader Desa]
         (Cakupan Prov)       (Cakupan Kab)       (Cakupan Pkm)       (Khusus Mobile)
```

### Rincian Peran Pengguna

| Role Pengguna | `user_type_id` | `officer_type_id` | Kanal Akses | Cakupan Data Legal (*Data Scope*) |
| :--- | :---: | :---: | :---: | :--- |
| **Super Administrator** | `1` | - | Web Admin | Global (Seluruh modul, konfigurasi, user, dan data nasional). |
| **Pasien TB** | `2` | - | Mobile App | Data pribadi: resep, jadwal obat harian, konsultasi, dan anggota serumah. Ditolak dari Web Admin. |
| **Dinkes Provinsi** | `3` | `1` | Web Admin | Agregat wilayah provinsi yang ditugaskan (`district.province_id`). Tidak memiliki akses menu admin global. |
| **Dinkes Kabupaten/Kota** | `3` | `2` | Web Admin | Data fasilitas kesehatan dan pasien pada kabupaten/kota yang ditugaskan (`officers.district_id`). |
| **PJTB Puskesmas** | `3` | `3` | Web Admin | Operasional klinis lengkap untuk pasien binaan pada Puskesmas yang ditugaskan (`officers.puskesmas_id`). |
| **Kader Puskesmas** | `3` | `4` | Mobile App | Warga binaan pada desa/kelurahan yang ditugaskan (`kader_areas.village_id`). Ditolak dari Web Admin. |

---

## 3. IMPLEMENTASI TEKNIS PERTAHANAN BERLAPIS

### Layer 1: Middleware Autentikasi & Kanal Akses Web (`EnsureWebAdminRole`)
File: `app/Http/Middleware/EnsureWebAdminRole.php`

Middleware ini mencegat setiap request yang masuk ke dashboard Web Admin:
1. Menolak akun nonaktif (`is_active = 0`) dan langsung menganulir session (*invalidate & regenerate token*).
2. Menolak akun Pasien (`user_type_id = 2`), menghapus session, dan mengarahkan ke halaman login dengan notifikasi bahwa akun pasien hanya dapat digunakan pada aplikasi Android.
3. Menolak akun Kader (`user_type_id = 3 && officer.officer_type_id = 4`), menghentikan session, dan mengarahkan ke login dengan pemberitahuan bahwa tugas kader dilaksanakan melalui aplikasi mobile.
4. Mengizinkan hanya Super Admin (`1`) dan Petugas Kesehatan berwenang (`3` dengan subtipe `1`, `2`, atau `3`).

### Layer 2: Middleware Pembatasan Peran Tingkat Rute (`CheckUserType`)
File: `app/Http/Middleware/CheckUserType.php` & `routes/web.php`

Rute web dikelompokkan secara deklaratif berdasarkan level hak akses:
- **Grup `checkrole:1-3`:** Modul operasional bersama (Pasien, Skrining, Pemeriksaan, Pengobatan, Kontak Erat, Laporan, Analitik, dan Faskes).
- **Grup `checkrole:1` (Khusus Super Admin):**
  - Manajemen Pengguna Global (`/users`)
  - Master Data Sistem (`/master`)
  - Pengelolaan Wilayah Administratif (`/regions`)
  - Audit Trail / Log Aktivitas Global (`/activity-logs`)
  - Distribusi & Versi Aplikasi Mobile APK (`/app-versions`)
  - Pengaturan Role & Pengaturan Sistem (`/settings?tab=roles`, `/settings?tab=system`)

### Layer 3: Model Helpers Terstandarisasi (`User.php`)
File: `app/Models/User.php`

Tersedia metode helper terpadu sehingga pengecekan hak akses tidak tersebar dengan logika rapuh:
```php
$user->isSuperAdmin();      // bool: user_type_id === 1
$user->isPatient();         // bool: user_type_id === 2
$user->isOfficer();         // bool: user_type_id === 3
$user->isDinkesProvinsi();  // bool: Petugas dengan officer_type_id === 1
$user->isDinkesKabKota();   // bool: Petugas dengan officer_type_id === 2
$user->isPJTB();            // bool: Petugas dengan officer_type_id === 3
$user->isKader();           // bool: Petugas dengan officer_type_id === 4
$user->canAccessWebAdmin(); // bool: true hanya untuk Admin dan Nakes Dinkes/PJTB
$user->role_display_name;   // string: Nama resmi peran untuk tampilan UI
```

### Layer 4: Sentralisasi Pembatasan Data Backend (Eloquent Query Scope)
Seluruh query ke basis data dibatasi di layer backend sebelum data diambil ke memori:

#### 1. Pasien TB (`Patient::accessibleBy($user)`)
- **Super Admin:** Mengakses seluruh record tanpa batasan.
- **PJTB Puskesmas:** Memfilter pasien yang terdaftar di Puskesmas miliknya (`puskesmas_id = $officer->puskesmas_id`).
- **Dinkes Kab/Kota:** Memfilter pasien yang berada di Puskesmas atau Kecamatan dalam wilayah kabupaten/kotanya (`district_id = $officer->district_id`).
- **Dinkes Provinsi:** Memfilter pasien yang berada dalam wilayah provinsi penugasannya.
- **Kader:** Memfilter pasien yang tinggal di desa/kelurahan binaannya (`village_id in (kader_areas)`).
- **Fail-Closed:** Jika pengguna tidak memiliki instansi/wilayah tugas, query menghasilkan `whereRaw('1 = 0')`.

#### 2. Skrining TB (`Screening::accessibleBy($user)`)
- Menerapkan scoping berbasis `puskesmas_id` dan relasi wilayah (`subdistrict_id` / `village_id`).
- Pada update terbaru, seluruh skrining mandiri mobile telah dipastikan memiliki referensi wilayah sehingga tidak pernah lolos dari pengawasan petugas wilayah bersangkutan.

#### 3. Fasilitas Kesehatan (`Puskesmas::accessibleBy($user)`)
- PJTB hanya melihat Puskesmas tempatnya bertugas.
- Dinkes Kab/Kota melihat seluruh Puskesmas di kabupaten/kotanya.
- Dinkes Provinsi melihat seluruh Puskesmas di provinsinya.

### Layer 5: Antarmuka Terkondisi (Blade Conditional Rendering)
File: `resources/views/admin/layouts/app.blade.php`

Menu sidebar dinamis hanya menampilkan tautan yang sah sesuai wewenang pengguna yang sedang login:
- Menu **Pengguna**, **Wilayah**, **Master Data**, **Log Aktivitas**, **Versi Mobile**, serta **Role & Konfigurasi Sistem** dibungkus dengan direktif `@if(Auth::user()->isSuperAdmin())`.
- Label profil navbar dan header secara dinamis menampilkan peran aktual pengguna (`Auth::user()->role_display_name`) dan wilayah kerjanya.
- Sub-menu laporan hanya menampilkan laporan pengguna jika pengguna adalah Super Administrator.

---

## 4. KEBIJAKAN PENANGANAN ERROR & PENGUJIAN KEAMANAN

1. **Akses URL Langsung Tanpa Hak:** Menghasilkan respon HTTP `403 Forbidden` dengan pesan deskriptif.
2. **Manipulasi Parameter ID (IDOR):**
   - Seluruh ID numerik tabel dienkripsi menggunakan AES-256 via `encrypt_id()` / `decrypt_id()`.
   - Controller melakukan verifikasi kewenangan melalui `$model->isAccessibleBy($user)` sebelum menampilkan atau memperbarui data.
3. **Penyaringan Laporan & Ekspor:**
   - Parameter jenis laporan dinormalisasi (`patients` -> `patient`, `regions` -> `regional`).
   - Ekspor CSV pengguna dilindungi di level controller: request non-admin langsung digagalkan dengan HTTP 403.

---

*Dokumentasi ini menjadi standar arsitektur keamanan akses bagi seluruh siklus pengembangan aplikasi TB Care.*
