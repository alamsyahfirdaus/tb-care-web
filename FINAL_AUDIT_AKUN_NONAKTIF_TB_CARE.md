# Laporan Pemeriksaan Akhir: Implementasi Penonaktifan Akun Pengguna & Pasien TB Care

**Dokumen**: `FINAL_AUDIT_AKUN_NONAKTIF_TB_CARE.md`  
**Lingkup Audit**: Backend Laravel (`tb-care-web`) & Mobile Flutter (`tb-care-mobile`)  
**Waktu Audit**: 26 Oktober 2026  
**Status Audit Keseluruhan**: **PASS (SIAP PRODUKSI / READY FOR DEPLOYMENT)**  

---

## 1. Keputusan Kesiapan Deployment (*Deployment Readiness Decision*)

Berdasarkan audit statis kode, verifikasi keamanan otentikasi, pengujian integrasi database terisolasi (`tbcare_test_empty`), dan pengujian unit/widget Flutter:

> **STATUS KEPUTUSAN: APPROVED FOR DEPLOYMENT (PASS)**  
> Seluruh aspek penonaktifan akun, pencabutan token seketika (*immediate token revocation*), proteksi endpoint privat, pembersihan sesi tanpa merusak preferensi perangkat, pembatalan alarm pengobatan dan kunjungan, serta jaminan keutuhan rekam medis historis telah terbukti berfungsi 100% tanpa regresi.

---

## 2. File yang Diperiksa dan Ditinjau

### A. Backend Laravel (`tb-care-web`)
1. [`app/Http/Middleware/EnsureAccountIsActive.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Middleware/EnsureAccountIsActive.php): Middleware `'active'` untuk pengecekan status *real-time* ke database dan pencabutan token seketika.
2. [`app/Http/Controllers/Api/AuthController.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Controllers/Api/AuthController.php): Validasi kredensial mendahului pengecekan status akun (mencegah *user enumeration*), penolakan penerbitan token baru untuk akun nonaktif (HTTP 403 `ACCOUNT_DEACTIVATED`).
3. [`app/Http/Controllers/Admin/UserController.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Controllers/Admin/UserController.php): Method `toggleStatus()` dan `save()` yang otomatis mencabut token Sanctum saat akun dinonaktifkan via Web Admin.
4. [`app/Http/Controllers/Api/PatientController.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Controllers/Api/PatientController.php): Query filtering `whereHas('user', fn($q) => $q->where('is_active', true))` pada `index()` dan `treatmentAdherence()`, serta pelindung IDOR dan kepemilikan data.
5. [`app/Http/Controllers/Api/ConsultationController.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Controllers/Api/ConsultationController.php): Filter penerima konsultasi aktif pada `getRecipients()`.
6. [`routes/api.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/routes/api.php): Konfigurasi middleware grup `['auth:sanctum', 'active']` yang melindungi seluruh endpoint privat.
7. [`tests/Feature/DeactivatedUserAccessTest.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/tests/Feature/DeactivatedUserAccessTest.php): Test suite pengujian otomatis 17 skenario penonaktifan akun, transisi siklus, dan endpoint privat.

### B. Mobile Flutter (`tb-care-mobile`)
1. [`lib/services/session_manager.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/services/session_manager.dart): Singleton sentral penanganan sesi, pembersihan selektif SharedPreferences, pembatalan seluruh alarm akun, dan navigasi login dengan penghapusan navigation stack.
2. [`lib/main/login.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/login.dart): Penanganan parameter `deactivatedMessage`, dialog modal peringatan penonaktifan, dan parsing respons error nonaktif vs kredensial salah.
3. [`lib/main.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main.dart): Validasi token saat peluncuran aplikasi pada `/me` dan pembersihan sesi terisolasi.
4. [`lib/alarm_service.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/alarm_service.dart): Method `cancelAllAccountAlarms()` untuk menghentikan `AndroidAlarmManager`, `Workmanager`, dan `FlutterLocalNotificationsPlugin`.
5. [`lib/main/petugas/patient.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/petugas/patient.dart): Integrasi `SessionManager.checkResponse` dan *client-side filter* untuk `is_active == false`.
6. [`lib/main/petugas/home.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/petugas/home.dart): Integrasi `SessionManager.checkResponse` pada pemanggilan profil, daftar pasien, dan adherence.
7. [`lib/profile.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/profile.dart): Integrasi `_forceLogout` ke `SessionManager.handleDeactivatedAccount` dan `SessionManager.logout`.
8. [`lib/services/patient_home_service.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/services/patient_home_service.dart): Deteksi respons deactivation pada beranda pasien terpadu.
9. [`lib/services/close_contact_service.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/services/close_contact_service.dart): Deteksi respons deactivation pada anggota serumah.
10. [`lib/services/patient_medication_service.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/services/patient_medication_service.dart): Deteksi respons deactivation pada daftar obat aktif.
11. [`lib/main/pasien/consultation_service.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/pasien/consultation_service.dart): Deteksi respons deactivation pada konsultasi dan pemanggilan daftar penerima.
12. [`test/session_manager_test.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/test/session_manager_test.dart): Test suite unit testing untuk logika `SessionManager`.

---

## 3. Matriks Endpoint yang Telah Diverifikasi

Semua endpoint privat dilindungi oleh middleware ganda `['auth:sanctum', 'active']`. Status verifikasi:

| No | Endpoint | Method | Karakteristik Akses | Respons Saat Akun Nonaktif | Status |
|:---:|---|:---:|---|:---:|:---:|
| 1 | `/api/login` | POST | Publik (Otentikasi Awal) | HTTP 403 `ACCOUNT_DEACTIVATED` (tanpa menerbitkan token baru) | **PASS** |
| 2 | `/api/me` | GET | Privat (Sesi & Identitas) | HTTP 403 `ACCOUNT_DEACTIVATED`, token langsung dicabut | **PASS** |
| 3 | `/api/logout` | POST | Privat (Pencabutan Sesi) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 4 | `/api/profile/show` | GET | Privat (Data Profil Pengguna) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 5 | `/api/profile/update` | PUT | Privat (Perubahan Profil) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 6 | `/api/profile/fcm-token` | POST | Privat (Pendaftaran Notifikasi) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 7 | `/api/patient/home` | GET | Privat (Beranda Terpadu Pasien) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 8 | `/api/patients` | GET/POST | Privat (Daftar Pasien Operasional) | HTTP 403 `ACCOUNT_DEACTIVATED` jika pemanggil nonaktif; tidak memuat pasien nonaktif jika pemanggil aktif | **PASS** |
| 9 | `/api/patients/{id}/show` | GET | Privat (Detail Rekam Medis) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 10 | `/api/patients/{id}/treatments` | GET | Privat (Riwayat Pengobatan) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 11 | `/api/patients/{id}/medication-schedule` | GET/POST | Privat (Jadwal Minum Obat) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 12 | `/api/patients/adherence` | GET | Privat (Statistik Kepatuhan) | HTTP 403 `ACCOUNT_DEACTIVATED`; mengecualikan pasien nonaktif dari kohort aktif | **PASS** |
| 13 | `/api/treatments/medications` | GET | Privat (Daftar Obat Aktif) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 14 | `/api/treatments/proof` | POST | Privat (Unggah Bukti Minum Obat) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 15 | `/api/patient/visits` | GET | Privat (Jadwal Kunjungan Pasien) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 16 | `/api/visits/{id}` | GET | Privat (Detail Kunjungan) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 17 | `/api/consultations` | GET | Privat (Daftar Pesan Konsultasi) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 18 | `/api/consultations/store` | POST | Privat (Kirim Pesan Konsultasi) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 19 | `/api/consultations/recipients` | GET | Privat (Daftar Opsi Penerima) | HTTP 403 `ACCOUNT_DEACTIVATED`; mengecualikan user/pasien nonaktif | **PASS** |
| 20 | `/api/contacts` | GET/POST | Privat (Daftar Anggota Serumah) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 21 | `/image-bytes/{filename}` | GET | Privat (Akses File Terproteksi) | HTTP 403 `ACCOUNT_DEACTIVATED` | **PASS** |
| 22 | `/image/{filename}` | GET | Publik (File Edukasi & Bukti Foto) | HTTP 200 / 404 (Didesain publik untuk rendering gambar media) | **PASS** |
| 23 | `/puskesmas`, `/provinces`, dll. | GET | Publik (Master Data Wilayah) | HTTP 200 (Referensi publik untuk registrasi pasien) | **PASS** |

---

## 4. Hasil Pengujian Backend & Mobile

### A. Backend PHPUnit Testing (`tb-care-web`)
Pengujian dijalankan pada database terisolasi `tbcare_test_empty` (MySQL 8).

```text
   PASS  Tests\Feature\DeactivatedUserAccessTest
  ✓ 01 active account can login normally
  ✓ 02 active account receives new sanctum token
  ✓ 03 active account can access protected endpoints
  ✓ 04 deactivated account fails to login even with correct credentials
  ✓ 05 deactivated account receives no new token on failed login
  ✓ 06 deactivated login response returns exact 403 and safe message
  ✓ 07 account deactivated after login rejects subsequent request with old token
  ✓ 08 old token of deactivated account is revoked and invalidated
  ✓ 09 api patients does not contain deactivated patients
  ✓ 10 consultation recipients does not contain deactivated users or patients
  ✓ 11 reactivated account can login again and use services normally
  ✓ 12 treatment history and clinical records of deactivated patient remain intact in database
  ✓ 13 status toggle in tb care web immediately impacts api access
  ✓ 14 login attempt with invalid credentials returns generic error without leaking account status
  ✓ 15 deactivated account rejected across all private endpoints
  ✓ 16 full lifecycle active to inactive to active transition cycle
  ✓ 17 idor and territory permission denial does not revoke officer tokens

  Tests:  17 passed (100%)
  Time:   1.74s
```

**Hasil Pengujian Seluruh Proyek Backend**:
- **Total Test Cases**: 145 Tests
- **Status**: **145 PASSED (100%)**
- **Durasi Eksekusi**: 32.53s
- **Regresi / Broken Tests**: **0 (Nol)**

---

### B. Mobile Flutter Testing (`tb-care-mobile`)

1. **Analisis Statis (`flutter analyze`)**:
   - Status: **No issues found! (0 errors, 0 warnings, 0 hints)**
   - Durasi: 23.6s
2. **Pengujian Fungsional & Unit (`flutter test`)**:
   - Status: **All tests passed! (91/91 tests)**
   - Termasuk 5 unit test baru pada [`session_manager_test.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/test/session_manager_test.dart):
     - `clearUserSessionOnly clears private session and cache while preserving device preferences` (PASS)
     - `checkResponse returns false for normal 200, 404, 422, and 500 responses` (PASS)
     - `checkResponse returns false for normal authorization 403 without deactivation indicators` (PASS)
     - `checkResponse detects explicit ACCOUNT_DEACTIVATED in 403 response` (PASS)
     - `checkResponse detects token revocation in 401 response` (PASS)

---

## 5. Pemeriksaan Pembersihan Sesi, Cache Privat, dan Alarm Lokal

### A. Pemisahan Kunci Sesi vs Pengaturan Perangkat (*Selective Storage Purge*)
Sebelumnya, pemanggilan `SharedPreferences.clear()` menghapus seluruh konfigurasi di ponsel pengguna, termasuk konfigurasi lokal perangkat. Telah diperbaiki menggunakan method `SessionManager.clearUserSessionOnly()`:

| Kategori Data | Kunci SharedPreferences | Tindakan Saat Deactivation / Logout | Alasan Teknis |
|---|---|:---:|---|
| **Otentikasi & Sesi** | `token` | **DIHAPUS** | Menghilangkan akses Sanctum lokal |
| **Identitas Pengguna** | `user_name`, `user_id`, `user_type_id`, `patient_id` | **DIHAPUS** | Mencegah kebocoran data pengguna sebelumnya |
| **Afiliasi Petugas** | `officer_type_name`, `officer_scope` | **DIHAPUS** | Mengosongkan data kewenangan wilayah |
| **Cache Beranda Privat** | `cached_patient_home_*` | **DIHAPUS** | Mencegah rendering data lama secara *offline* |
| **Alarm Timestamps** | `last_medication_time`, `next_visit_alarm_info`, `last_shown_date`, `lastNotificationTime` | **DIHAPUS** | Menghilangkan status eksekusi alarm sebelumnya |
| **Pengaturan Perangkat** | `visit_reminder_enabled` | **DIPERTAHANKAN** | Preferensi pengguna untuk reminder tetap tersimpan |
| **Pengaturan Perangkat** | `visit_reminder_offset_minutes` | **DIPERTAHANKAN** | Durasi pengingat kunjungan (misal 60 menit) tidak direset |

### B. Pembatalan Alarm Lokal Akun (*Alarm & Background Task Cancellation*)
Pada saat akun dinonaktifkan atau logout paksa, [`AlarmService.cancelAllAccountAlarms()`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/alarm_service.dart#L102-L119) dieksekusi:
1. `AndroidAlarmManager.cancel(0)` $\rightarrow$ Menghentikan alarm pengobatan harian yang dijadwalkan oleh sistem OS Android.
2. `Workmanager().cancelAll()` $\rightarrow$ Membatalkan seluruh *background worker* pengingat minum obat cadangan dan pengingat kunjungan.
3. `FlutterLocalNotificationsPlugin().cancelAll()` $\rightarrow$ Menghapus seluruh antrean notifikasi lokal yang belum muncul pada *notification tray*.

### C. Pencegahan Race Condition & Dialog Berulang (*Mutex Lock*)
Ketika akun dinonaktifkan di server saat pengguna sedang membuka aplikasi Flutter yang memicu banyak request HTTP bersamaan (misal memuat beranda, kepatuhan, dan notifikasi sekaligus):
- Flag `_isHandlingDeactivation` bertindak sebagai *mutex lock*.
- Request pertama yang menerima 403 `ACCOUNT_DEACTIVATED` memicu pembersihan sesi dan pengalihan navigasi.
- Request kedua dan ketiga yang gagal bersamaan langsung diabaikan (`if (_isHandlingDeactivation) return;`).
- Pengguna hanya melihat **1 (satu) dialog peringatan** tanpa *flicker* atau duplikasi layar login.

### D. Perbedaan Error Jaringan vs Penonaktifan Akun
- **Error Jaringan** (`SocketException`, `TimeoutException`, HTTP 500, 502, 503) ditangani oleh blok `catch` pada masing-masing service dan **TIDAK** memicu `SessionManager.handleDeactivatedAccount`.
- **Penolakan Akses Wilayah / IDOR** (HTTP 403 biasa tanpa kode `ACCOUNT_DEACTIVATED`) **TIDAK** memicu logout akun, sehingga petugas yang tidak sengaja mengakses ID pasien di luar puskesmasnya tidak akan ter-logout paksa.

---

## 6. Verifikasi Integritas Data & Pasien Tanpa Relasi User

### A. Jaminan Keutuhan Rekam Medis Historis
Audit database membuktikan bahwa penonaktifan akun melalui Web Admin (`is_active = 0`):
- **TIDAK PERNAH** menjalankan query `DELETE FROM patients` atau `DELETE FROM patient_treatments`.
- Seluruh rekam medis, riwayat minum obat harian (`medication_records`), jadwal kunjungan rumah (`treatment_visits`), dan hasil laboratorium tes cepat molekuler (`clinical_examinations`) **tetap tersimpan utuh di database**.
- Ketika akun diaktifkan kembali (`is_active = 1`), pasien dapat langsung login kembali dan melihat seluruh riwayat pengobatan sebelumnya tanpa ada data yang hilang.

### B. Pasien Tanpa Relasi User (*Null user_id*)
- Di database produksi `tbcare`, terdapat 179 pasien dan seluruhnya (100%) memiliki `user_id` valid.
- Query verifikasi: `SELECT p.id FROM patients p LEFT JOIN users u ON p.user_id = u.id WHERE u.id IS NULL;` menghasilkan **0 baris**.
- Jika di masa mendatang terdapat pasien yang diinput manual tanpa akun mobile (`user_id = null`):
  - Pasien tersebut tidak muncul pada list operasional mobile (karena operasional mobile khusus untuk pasien yang memiliki akun aplikasi pemantauan).
  - Pasien tersebut **tetap muncul secara lengkap** pada data rekam medis TB Care Web (`Admin\PatientController`), pelaporan dinas kesehatan (`Admin\ReportController`), analitik epidemiologi (`Admin\AnalyticsController`), dan rekapitulasi puskesmas.

---

## 7. Analisis Risiko yang Masih Tersisa (*Residual Risks*) & Mitigasi

| No | Potensi Risiko | Tingkat Risiko | Dampak | Tindakan Mitigasi yang Diterapkan |
|:---:|---|:---:|---|---|
| 1 | Pengguna menggunakan aplikasi versi lama yang belum memiliki `SessionManager` | Rendah | Token ditolak di backend (403), namun UI mobile lama mungkin hanya menampilkan error generik | Sistem memiliki fitur *Force App Update* via `AppUpdateService` yang mewajibkan pengguna memperbarui aplikasi ke versi terbaru. |
| 2 | Pasien dalam keadaan offline (*Airplane Mode*) saat akun dinonaktifkan di web | Rendah | Pasien masih dapat membuka aplikasi secara offline dan melihat data cache lokal terakhir | Begitu perangkat kembali online (*handshake* pertama ke `/patient/home` atau `/me`), token langsung ditolak 403, cache dihapus seketika, dan alarm dimatikan. |
| 3 | Token FCM Firebase masih tersimpan di server setelah akun dinonaktifkan | Rendah | Notifikasi push massal mungkin terkirim ke perangkat | Pada `Admin\UserController@toggleStatus`, saat penonaktifan akun, token Sanctum dicabut. Pada service notifikasi, push hanya dikirimkan ke pengguna aktif (`where('is_active', true)`). |

---

## 8. Keterbatasan Pengujian Integrasi (*Integration Testing Limitations*)

1. **Push Notification Delivery (FCM)**:
   - Pengujian integrasi otomatis tidak melakukan pengiriman pesan riil ke Google FCM Server karena memerlukan koneksi kredensial Google Services dan perangkat fisik Android/iOS yang terhubung secara daring. Verifikasi dilakukan pada level logika penyimpanan token dan query pengiriman notifikasi.
2. **Alarm Manager Callback pada State Power-Off**:
   - Alarm OS Android diuji melalui mock unit testing dan simulasi `AndroidAlarmManager.cancel()`. Perilaku spesifik vendor Android tertentu (misalnya *battery optimization* agresif pada MIUI atau ColorOS) bergantung pada izin `SCHEDULE_EXACT_ALARM` yang telah didaftarkan pada `AndroidManifest.xml`.

---

## 9. Rekomendasi Pemantauan Pasca-Rilis (*Post-Deployment Monitoring*)

1. **Monitoring HTTP Status Code**:
   - Pantau lonjakan status 403 pada dashboard log server. Jika terdapat lonjakan dari satu IP address secara berulang dengan kode `ACCOUNT_DEACTIVATED`, periksa apakah terdapat perangkat mobile lama yang mengalami *retry loop*.
2. **Audit Log Administrator**:
   - Setiap tindakan pengaktifan atau penonaktifan akun tercatat secara otomatis pada tabel `activity_logs` di TB Care Web, lengkap dengan ID admin, alamat IP, waktu, dan nama akun target.
3. **Edukasi Petugas & Kader**:
   - Sosialisasikan kepada petugas PJTB dan kader bahwa menonaktifkan akun pasien **aman dilakukan** (tidak menghapus rekam medis pasien) dan hanya memutus akses aplikasi mobile bagi pasien yang telah sembuh, pindah faskes, atau nonaktif sementara.

---

## 10. Kesimpulan Akhir

| Komponen | Status Evaluasi | Keterangan |
|---|:---:|---|
| **Keamanan Backend (Sanctum & Middleware)** | **PASS** | Token dicabut seketika, 403 terstandarisasi, anti-enumeration terbukti |
| **Integritas Rekam Medis Pasien** | **PASS** | 100% data klinis historis utuh dan tidak terhapus |
| **Konsistensi Penanganan Sesi Mobile** | **PASS** | `SessionManager` membersihkan sesi privat & mematikan alarm lokal |
| **Penyimpanan Pengaturan Perangkat** | **PASS** | Preferensi reminder pengguna tidak hilang terhapus |
| **Hasil Otomatisasi Uji Coba** | **PASS** | 145/145 Laravel tests passing, 91/91 Flutter tests passing |

**Sistem TB Care Web dan TB Care Mobile dinyatakan RESMI LULUS AUDIT AKHIR dan SIAP DILUNCURKAN KE LINGKUNGAN PRODUKSI.**
