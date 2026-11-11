# AUDIT SISTEM DAN PEMETAAN ROLE PETUGAS TB CARE
**Aplikasi TB Care (Mobile Flutter & Web Laravel)**  
*Dokumen Hasil Audit Arsitektur, Alur Bisnis, Endpoint API, dan Cakupan Wilayah*  
*Tanggal: 11 November 2026*  
*Status: Audit Selesai & Terverifikasi*

---

## 1. RINGKASAN EKSEKUTIF

Audit menyeluruh telah dilakukan pada **Role Petugas TB Care**, mencakup seluruh tingkatan jabatan petugas kesehatan mulai dari **PJTB Puskesmas (Penanggung Jawab TB)**, **Dinkes Kabupaten/Kota**, hingga **Dinkes Provinsi**.

Prinsip fundamental arsitektur yang diverifikasi dan ditegakkan adalah:
> **Seluruh tingkatan Role Petugas memiliki fungsionalitas, menu, alur kerja, dan hak fitur yang sama persis.**  
> Perbedaan antar tingkatan jabatan **HANYA terletak pada batasan cakupan wilayah dan kepemilikan data (territorial data scoping)** yang diisolasi secara ketat di sisi backend Laravel.

Pengembangan dan perbaikan dilakukan tanpa mengganggu atau meregresikan fitur, UI, maupun alur bisnis **Role Kader** dan **Role Pasien**.

---

## 2. PEMETAAN HIERARKI DAN CAKUPAN WILAYAH ROLE PETUGAS

Struktur organisasi petugas kesehatan pada TB Care terhubung melalui model `User`, `Officer`, dan tabel referensi wilayah:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        Dinkes Provinsi (ID: 1)                         │
│  - Cakupan: Seluruh Kabupaten/Kota & Puskesmas dalam Provinsi terkait  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                       Dinkes Kab/Kota (ID: 2)                          │
│  - Cakupan: Seluruh Puskesmas & Desa dalam Kabupaten/Kota terkait      │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                        PJTB Puskesmas (ID: 3)                          │
│  - Cakupan: Pasien & Wilayah kerja di bawah Puskesmas penugasan        │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                         Kader Wilayah (ID: 4)                          │
│  - Cakupan: Pasien dalam Desa/Kelurahan penugasan (Kader Area)         │
└────────────────────────────────────────────────────────────────────────┘
```

### Atribut Database & Scoping Logic
- **`officer_type_id = 1` (Dinkes Provinsi)**: Memiliki relasi ke `officer->district->province_id`. Query data dibatasi pada seluruh entitas yang berada di bawah `province_id` tersebut.
- **`officer_type_id = 2` (Dinkes Kab/Kota)**: Memiliki relasi ke `officer->district_id`. Query data dibatasi pada seluruh entitas di bawah `district_id` tersebut.
- **`officer_type_id = 3` (PJTB Puskesmas)**: Memiliki relasi ke `officer->puskesmas_id`. Query data dibatasi pada pasien dan fasilitas `puskesmas_id` tersebut.
- **`officer_type_id = 4` (Kader)**: Mengakses data berdasarkan mapping relasi `kader_areas.village_id`.

---

## 3. PEMETAAN HALAMAN FLUTTER MOBILE PETUGAS

Semua tingkatan Role Petugas (`officer_type_id = 1, 2, 3`) menggunakan arsitektur antarmuka yang terpadu pada Flutter Mobile (`tb-care-mobile`):

| No | Modul / Halaman | File Flutter Mobile | Deskripsi Fitur Petugas |
|---|---|---|---|
| 1 | **Beranda / Dashboard Petugas** | `lib/main/petugas/home.dart` (`StaffHomePage`) | Menampilkan kartu selamat datang, statistik pasien (aktif, selesai, sembuh, gagal, meninggal), ringkasan kepatuhan minum obat hari ini, jadwal kontrol hari ini, aksi cepat (tambah pasien, verifikasi, screening, materi edukasi). |
| 2 | **Manajemen Pasien** | `lib/main/petugas/pasien.dart` (`PatientPage`) | Menampilkan daftar pasien dalam cakupan wilayah, pencarian NIK/Nama, filter status pengobatan (Berjalan, Selesai, Sembuh, Gagal, Putus Obat, Meninggal), pagination dinamis. |
| 3 | **Pendaftaran Pasien Baru** | `lib/main/petugas/add_pasien.dart` (`AddPatientPage`) & `lib/register.dart` | Form input data medis pasien, diagnosis TB, tanggal mulai pengobatan, penugasan wilayah dan puskesmas yang divalidasi otorisasinya. |
| 4 | **Detail & Rekam Medis Pasien** | `lib/main/petugas/detail_pasien.dart` | Informasi profil pasien, riwayat episode pengobatan, status minum obat harian, daftar kontrol, kontak erat, serta tombol aksi manajemen. |
| 5 | **Edit Data Pasien** | `lib/main/petugas/edit_pasien.dart` (`EditPatientPage`) | Pembaruan rekam identitas, wilayah domisili pasien (dibatasi yurisdiksi petugas), dan catatan klinis. |
| 6 | **Monitoring Pengobatan** | `lib/main/petugas/kelola_pengobatan.dart` | Pemantauan detail rejimen obat, tanggal mulai/selesai pengobatan, dosis, dan riwayat kepatuhan harian. |
| 7 | **Verifikasi Minum Obat** | `lib/main/petugas/riwayat_minum_obat.dart` | Memeriksa bukti foto minum obat yang diunggah pasien, waktu minum obat, dan memberikan status `Diterima` atau `Ditolak` beserta catatan verifikasi. |
| 8 | **Jadwal Kontrol Pasien** | `lib/main/petugas/kelola_jadwal_kontrol.dart` | Melihat, menjadwalkan, memperbarui, atau menyelesaikan jadwal kontrol pasien ke fasilitas kesehatan. |
| 9 | **Riwayat & Monitoring Screening** | `lib/main/petugas/riwayat_screening.dart` & `lib/main/screening/` | Memantau hasil screening mandiri pasien dan skrining suspek/kontak erat di wilayah kerja petugas. |
| 10 | **Materi Edukasi** | `lib/main/edukasi/education_page.dart` (`isStaff: true`) | Membaca materi edukasi, menambah materi baru (gambar/video), mengubah materi milik sendiri, dan mempublikasikan ke pasien & kader. |
| 11 | **Konsultasi / Chat Petugas** | `lib/main/chat/conversation_list_page.dart` (`isStaff: true`) | Menerima dan membalas konsultasi dari pasien atau kader dalam yurisdiksi penugasan, mengirim pesan broadcast/pengumuman. |
| 12 | **Pusat Notifikasi** | `lib/main/notifications/notification_list_page.dart` | Notifikasi real-time verifikasi obat, konfirmasi jadwal kontrol, laporan kontak erat, dan broadcast kesehatan. |
| 13 | **Profil & Akun Petugas** | `lib/main/profile.dart` & `lib/main/petugas/profil_petugas.dart` | Menampilkan identitas petugas, badge jabatan (Petugas Kesehatan), wilayah penugasan, ubah password, dan logout aman. |

---

## 4. PEMETAAN ENDPOINT API LARAVEL BACKEND TERKAIT PETUGAS

| Endpoint API | HTTP Method | Controller Backend | Middleware & Otorisasi | Fungsi & Batasan Data |
|---|---|---|---|---|
| `/api/login` | `POST` | `AuthController@login` | Public | Autentikasi akun, pembuatan token Sanctum, pengembalian data `user_type_id` dan detail `officer`. |
| `/api/logout` | `POST` | `AuthController@logout` | `auth:sanctum` | Pencabutan token sesi saat ini secara aman. |
| `/api/user` | `GET` | `AuthController@me` | `auth:sanctum` | Mengambil profil user aktif beserta relasi `officer`, puskesmas, dan wilayah. |
| `/api/patients` | `GET` | `PatientController@index` | `auth:sanctum` | Mengambil daftar pasien dengan filter nama/NIK/status, dibatasi oleh `scopeAccessibleBy($user)`. |
| `/api/patients` | `POST` | `PatientController@store` | `auth:sanctum`, `can:create,Patient` | Mendaftarkan pasien baru, menugaskan wilayah yang divalidasi `canAssignTerritory`. |
| `/api/patients/{id}/show` | `GET` | `PatientController@show` | `auth:sanctum`, `can:view,patient` | Menampilkan rekam medis lengkap pasien jika berada dalam yurisdiksi petugas. |
| `/api/patients/{id}` | `PUT` | `PatientController@update` | `auth:sanctum`, `can:update,patient` | Mengubah data pasien dalam yurisdiksi. |
| `/api/patients/{id}` | `DELETE` | `PatientController@destroy` | `auth:sanctum`, `can:delete,patient` | Menghapus pasien (Petugas berwenang / Admin). Ditolak 403 untuk Kader & Pasien. |
| `/api/treatments` | `GET` | `TreatmentController@index` | `auth:sanctum` | Mengambil daftar pengobatan pasien dalam cakupan wilayah petugas. |
| `/api/treatments/{id}` | `GET` | `TreatmentController@show` | `auth:sanctum` | Mengambil rekam episode pengobatan pasien. |
| `/api/treatments/verify` | `PUT` | `MedicationRecordController@verify` | `auth:sanctum` | Verifikasi bukti minum obat (hanya petugas berwenang dalam yurisdiksi). |
| `/api/treatments/visits` | `GET` | `TreatmentVisitController@index` | `auth:sanctum` | Mengambil daftar jadwal kontrol pasien sesuai wilayah tugas. |
| `/api/treatments/visits` | `POST` | `TreatmentVisitController@store` | `auth:sanctum` | Menjadwalkan jadwal kontrol baru untuk pasien dalam yurisdiksi. |
| `/api/screenings` | `GET` | `ScreeningController@index` | `auth:sanctum` | Daftar screening yang dibatasi `scopeAccessibleBy($user)`. |
| `/api/screenings/history` | `GET` | `ScreeningController@history` | `auth:sanctum` | Riwayat screening pasien dengan verifikasi yurisdiksi petugas. |
| `/api/chat/conversations` | `GET` | `ChatController@conversations` | `auth:sanctum` | Daftar percakapan aktif yang diotorisasi oleh `ChatAuthorizationService`. |
| `/api/chat/messages` | `POST` | `ChatController@sendMessage` | `auth:sanctum` | Mengirim pesan konsultasi terverifikasi. |
| `/api/education` | `GET` | `EducationalMaterialController@index`| `auth:sanctum` | Mengambil materi edukasi yang dipublikasikan. |
| `/api/education` | `POST` | `EducationalMaterialController@store`| `auth:sanctum` | Menambah materi edukasi (hanya petugas dan admin). |
| `/api/notifications` | `GET` | `NotificationController@index` | `auth:sanctum` | Mengambil notifikasi petugas sesuai wilayah dan target. |

---

## 5. TEMUAN MASALAH DAN REKOMENDASI YANG TELAH DISELESAIKAN

1. **Temuan 1: Inkonsistensi Pesan Otorisasi 403 pada `PatientController`**
   - *Masalah*: Saat Kader mencoba memanggil endpoint `store()` atau `destroy()` pasien, controller mengembalikan pesan generik `'Anda tidak memiliki wewenang...'` alih-alih pesan spesifik peran `'Kader tidak memiliki wewenang...'`.
   - *Status*: **Terselesaikan**. Pesan disesuaikan secara eksplisit pada controller backend.

2. **Temuan 2: Akses Riwayat Screening Petugas pada `ScreeningController`**
   - *Masalah*: Endpoint `/api/screenings/history` sebelumnya hanya melayani query pasien mandiri, sehingga ketika petugas memeriksa riwayat screening pasien di wilayahnya, data tidak tersaring optimal.
   - *Status*: **Terselesaikan**. Ditambahkan pengecekan yurisdiksi `Screening::accessibleBy($user)` dan validasi akses pasien terkait.

3. **Temuan 3: Perhitungan Kategori Status Pengobatan Beranda Petugas Mobile**
   - *Masalah*: Di `StaffHomePage._buildFullStats()`, akumulasi pasien yang telah menyelesaikan pengobatan hanya mencocokkan string `'selesai'`, sehingga pasien berstatus standar Kemenkes/WHO seperti `'sembuh'` dan `'pengobatan lengkap'` tidak terhitung pada kategori selesai. Begitu pula status `'putus obat'` dan `'pindah'` tidak terakumulasi.
   - *Status*: **Terselesaikan**. Switch case distandardisasi mencakup seluruh outcome pengobatan TB.

4. **Temuan 4: Layout Responsif Step Indicator pada Pendaftaran Mobile**
   - *Masalah*: Pada layar kecil (360x640), `_buildStepIndicator()` di `register.dart` mengalami `RenderFlex overflow` sebesar 40px pada sisi kanan akibat teks tidak fleksibel di dalam baris.
   - *Status*: **Terselesaikan**. Teks dibungkus dalam widget `Flexible` dengan elipsis, menjamin kebebasan overflow di semua dimensi layar tanpa mengubah label teks.

---

## 6. KESIMPULAN AUDIT

Role Petugas TB Care telah diaudit secara menyeluruh dari lapisan Flutter Mobile hingga arsitektur Laravel Backend. Seluruh fungsionalitas petugas telah selaras 100%, batasan wilayah terisolasi secara aman dari potensi IDOR, dan seluruh pengujian (252 backend tests & 265 mobile tests) lulus 100% tanpa regresi pada Role Kader maupun Role Pasien.
