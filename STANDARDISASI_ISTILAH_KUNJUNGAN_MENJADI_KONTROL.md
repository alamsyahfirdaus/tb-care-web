# DOKUMENTASI STANDARDISASI ISTILAH "KUNJUNGAN" MENJADI "KONTROL" PADA SELURUH ROLE APLIKASI TB CARE

Dokumen ini mencatat standardisasi terminologi dari kata **"Kunjungan"** menjadi **"Kontrol"** pada seluruh user interface (UI), notifikasi, pesan sistem, dan label di aplikasi **TB Care** (Flutter Mobile dan Laravel Web).

---

## 1. LATAR BELAKANG DAN TUJUAN

Dalam konteks pengobatan Tuberkulosis (TB), istilah yang lazim, baku, dan lebih mudah dipahami oleh pasien serta tenaga medis di fasilitas pelayanan kesehatan (Puskesmas/Rumah Sakit) adalah **"Kontrol"** atau **"Jadwal Kontrol"** (bukan "Kunjungan").

Standardisasi ini bertujuan untuk:
1. Memberikan konsistensi bahasa di seluruh antarmuka aplikasi.
2. Memudahkan pemahaman bagi pasien bahwa aktivitas yang dimaksud adalah pemeriksaan berkala dan pengambilan obat lanjutan di faskes.
3. Menyelaraskan istilah antar empat peran pengguna (**Pasien**, **Kader**, **Petugas**, dan **Admin**).

---

## 2. TABEL PEMETAAN PERUBAHAN ISTILAH

| No | Istilah Lama (Sebelum) | Istilah Baru (Sesudah) | Konteks Penggunaan |
|---|---|---|---|
| 1 | Jadwal Kunjungan | **Jadwal Kontrol** | Judul halaman, menu navigasi, kartu ringkasan |
| 2 | Kunjungan Berikutnya | **Kontrol Berikutnya** | Card beranda pasien & kader |
| 3 | Pengingat Kunjungan | **Pengingat Kontrol** | Pengaturan alarm, notifikasi pengingat, modal kader |
| 4 | Akan Datang (Kunjungan) | **Jadwal Mendatang** | Seksi daftar jadwal kontrol mendatang |
| 5 | Semua Kunjungan | **Riwayat Jadwal Kontrol** | Seksi daftar jadwal yang sudah lampau |
| 6 | Tambah Kunjungan | **Tambah Jadwal Kontrol** | Modal input petugas kesehatan |
| 7 | Edit Kunjungan | **Edit Jadwal Kontrol** | Modal ubah jadwal kontrol petugas |
| 8 | Hapus Kunjungan | **Hapus Jadwal Kontrol** | Dialog konfirmasi hapus jadwal |
| 9 | Status Kunjungan | **Status Kontrol** | Label dropdown status (Terjadwal/Hadir/Tidak Hadir) |
| 10 | Tanggal Kunjungan | **Tanggal Kontrol** | Label form & kolom tabel admin |
| 11 | Waktu Kunjungan | **Waktu Kontrol** | Label waktu kedatangan di Puskesmas |
| 12 | Kunjungan Puskesmas | **Jadwal Kontrol Puskesmas** | Tipe pengingat kader & judul notifikasi push |
| 13 | Belum ada kunjungan | **Belum ada jadwal kontrol** | Tampilan status kosong (empty state) |
| 14 | Kunjungan berhasil ditambahkan / diperbarui / dihapus | **Jadwal kontrol berhasil ditambahkan / diperbarui / dihapus** | Pesan SnackBar pada aplikasi mobile & flash message web admin |

---

## 3. CAKUPAN PENERAPAN BERDASARKAN ROLE

### A. Role Pasien (Patient)
- **Halaman Beranda (`pasien/home.dart`):** Card jadwal berlabel *"Kontrol Berikutnya"* dengan status jadwal mendatang.
- **Halaman Jadwal Kontrol (`pasien/visit_schedule_page.dart`):**
  - Judul App Bar: *"Jadwal Kontrol"*.
  - Seksi: *"Jadwal Mendatang"* dan *"Riwayat Jadwal Kontrol"*.
  - Card Pengingat: *"Pengingat Kontrol"*.
  - Dialog Detail: *"Detail Jadwal Kontrol"*.
- **Alarm & Notifikasi Lokal (`alarm_service.dart` & `notification_service.dart`):**
  - Deskripsi channel notifikasi: *"Channel untuk pengingat minum obat & jadwal kontrol"*.
  - Judul pengingat: *"Jadwal Kontrol Puskesmas"*.

### B. Role Kader (Community Health Worker)
- **Halaman Riwayat Pengingat (`kader/reminders.dart`):**
  - Filter chip pencarian diganti dari `'Kunjungan'` menjadi `'Kontrol'`.
  - Tipe log notifikasi menggunakan label `'Jadwal Kontrol Puskesmas'`.
- **Halaman Detail Pasien Binaan (`kader/patient_detail.dart`):**
  - Read-only review jadwal kontrol faskes pasien binaan.
- **Kader API Controller (`app/Http/Controllers/Api/KaderController.php`):**
  - FCM Title: *"Pengingat Jadwal Kontrol TB"*.
  - Type label: *"Jadwal Kontrol Puskesmas"*.

### C. Role Petugas (Healthcare Staff)
- **Halaman Beranda Petugas (`petugas/home.dart`):**
  - Tombol aksi: `ElevatedButton.icon` dengan label `'Jadwal Kontrol'`.
  - Info ringkasan card pasien: `'Jadwal Kontrol: $date @ $time'`.
- **Halaman Manajemen Pasien Petugas (`petugas/patient.dart`):**
  - Tombol aksi: `'Kontrol'`.
  - Tooltip & baris informasi: `'Kontrol: $nextVisit'`.
- **Halaman Kelola Jadwal Petugas (`petugas/visit_managment.dart`):**
  - Judul App Bar: `'Jadwal Kontrol - $patientName'`.
  - Form dialog: `'Tambah Jadwal Kontrol'` dan `'Edit Jadwal Kontrol'`.
  - Label kolom: `'Tanggal Kontrol*'`, `'Waktu Kontrol'`, `'Status Kontrol*'`.
  - Dialog hapus: `'Hapus Jadwal Kontrol'`, *"Apakah Anda yakin ingin menghapus jadwal kontrol ini?"*.
  - SnackBar: *"Jadwal kontrol berhasil ditambahkan / diperbarui / dihapus"*.

### D. Role Admin (Web Admin TB Care)
- **Halaman Rincian Pasien Admin (`resources/views/admin/patients/show.blade.php`):**
  - Kolom tabel jadwal: *"Tanggal Kontrol"*, *"Waktu Kontrol"*, *"Status Kontrol"*.
  - Header rincian pengingat: *"Jadwal Kontrol Puskesmas:"*.
- **Admin Treatment Controller (`app/Http/Controllers/Admin/TreatmentController.php`):**
  - Pesan peringatan hak akses dan penghapusan menggunakan terminologi *"jadwal kontrol"*.

---

## 4. INTEGRITAS ARSITEKTUR TEKNIS (TIDAK BERUBAH)

Sesuai instruksi dan prinsip integritas arsitektur sistem, identifier teknis **TIDAK DIUBAH** guna menjaga kompatibilitas database, API schema, dan stabilitas modul:

- **Tabel Database:** Tetap `treatment_visits`
- **Kolom Database:** Tetap `visit_date`, `visit_time`, `visit_status`, `notes`
- **API Endpoints:**
  - `GET/POST /api/treatment-visits`
  - `GET /api/treatments/{treatment_id}/visits`
  - `GET /api/patient/visits`
  - `DELETE /api/visits/{id}/delete`
- **Controller & Model:** Tetap `TreatmentVisit` dan `TreatmentVisitController`
- **Variabel Internal Program:** Variabel lokal seperti `$visit`, `visitId`, dsb. tetap dipertahankan.

---

## 5. DAFTAR FILE YANG DIPERBARUI

### Proyek Flutter Mobile (`tb-care-mobile`):
1. `lib/main/pasien/visit_schedule_page.dart`
2. `lib/alarm_service.dart`
3. `lib/services/notification_service.dart`
4. `lib/services/session_manager.dart`
5. `lib/main/kader/reminders.dart`
6. `lib/main/petugas/home.dart`
7. `lib/main/petugas/patient.dart`
8. `lib/main/petugas/visit_managment.dart`
9. `test/patient_visit_schedule_refinement_test.dart`
10. `test/kader_reminders_and_rbac_test.dart`

### Proyek Laravel Backend (`tb-care-web`):
1. `app/Models/Puskesmas.php`
2. `app/Http/Controllers/Api/TreatmentVisitController.php`
3. `app/Http/Controllers/Api/TreatmentController.php`
4. `app/Http/Controllers/Api/PatientController.php`
5. `app/Http/Controllers/Api/KaderController.php`
6. `app/Http/Controllers/Admin/TreatmentController.php`
7. `resources/views/admin/patients/show.blade.php`
8. `tests/Feature/PatientAuthorizationSecurityTest.php`

---

## 6. HASIL VALIDASI PENGUJIAN

Semua pengujian pada kedua repositori telah dijalankan dan lulus 100%:

1. **`flutter analyze`**: **0 issues found** (clean linting & static analysis).
2. **`flutter test test/patient_visit_schedule_refinement_test.dart`**: **Passed (2/2)**.
3. **`flutter test test/kader_reminders_and_rbac_test.dart`**: **Passed (8/8)**.
4. **`flutter test test/home_page_widget_test.dart test/kader_dashboard_and_navigation_test.dart`**: **Passed (7/7)**.
5. **`php artisan test --filter=PatientAuthorizationSecurityTest`**: **Passed (9/9)**.
6. **`php artisan test --filter=TreatmentVisitApiTest`**: **Passed (2/2)**.
7. **`php artisan test --filter=PatientHomeApiTest`**: **Passed (3/3)**.
8. **`php artisan test --filter=KaderReminderFlowTest`**: **Passed (8/8)**.
