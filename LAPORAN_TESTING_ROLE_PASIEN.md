# LAPORAN PENGUJIAN DAN VERIFIKASI ROLE PASIEN — APLIKASI TB CARE

## 1. LINGKUNGAN PENGUJIAN (TEST ENVIRONMENT)

Pengujian dilakukan pada lingkungan lokal terintegrasi yang merefleksikan kondisi produksi sistem TB Care:
- **Sistem Operasi**: Windows 11
- **Bahasa & Runtime Backend**: PHP 8.3.23 (CLI/Laragon)
- **Framework Backend**: Laravel Framework 11.x
- **Basis Data**: MySQL 8.0.30 via Laragon (`tbcare_test_empty` & `tbcare`) pada port 3306
- **Driver Autentikasi**: Laravel Sanctum (Token-Based Bearer Authentication)
- **Framework Mobile**: Flutter 3.29.0 / Dart 3.7.0
- **Alat Uji**: PHPUnit / Laravel Test Runner & Flutter Test Framework

---

## 2. HASIL PENGUJIAN LARAVEL BACKEND (`php artisan test`)

Seluruh rangkaian pengujian fungsional, otorisasi, dan integrasi backend dijalankan dan berhasil lulus 100%.

### 2.1. Suite Pengujian Keamanan Otorisasi Pasien (`PatientAuthorizationSecurityTest.php`)
Suite pengujian khusus yang dibuat untuk menguji seluruh batas izin akses (*RBAC & Broken Access Control*) Role Pasien:

| No | Kasus Uji | Status | Keterangan |
|---|---|---|---|
| 1 | `test_patient_cannot_create_or_modify_treatment` | **PASS (200 OK -> 403 Forbidden)** | Pasien dilarang menambah atau mengubah rekam pengobatan klinis. |
| 2 | `test_patient_cannot_delete_treatment` | **PASS (403 Forbidden)** | Pasien dilarang menghapus pengobatan klinis miliknya. |
| 3 | `test_patient_cannot_update_treatment_status` | **PASS (403 Forbidden)** | Pasien dilarang mengubah status pengobatan sendiri. |
| 4 | `test_patient_cannot_create_or_update_visit_schedule` | **PASS (403 Forbidden)** | Pasien dilarang membuat atau memperbarui jadwal kontrol faskes. |
| 5 | `test_patient_cannot_delete_or_cancel_visit_schedule` | **PASS (403 Forbidden)** | Pasien dilarang membatalkan atau menghapus jadwal kunjungan. |
| 6 | `test_patient_cannot_delete_patient_master_record` | **PASS (403 Forbidden)** | Pasien dilarang menghapus master data dan profil pasien via API. |
| 7 | `test_patient_cannot_verify_medication_proof_but_officer_can` | **PASS (403 vs 200 OK)** | Pasien diblokir saat mencoba verifikasi mandiri (403). Petugas PJTB Puskesmas berhasil memverifikasi dan mencatat ID verifikator (200 OK). |
| 8 | `test_patient_medication_submission_is_pending_verification` | **PASS (201 Created)** | Bukti minum obat pasien tersimpan dengan `is_verified = 0` (Menunggu Verifikasi Petugas). |
| 9 | `test_patient_can_view_screening_history` | **PASS (200 OK)** | Pasien dapat mengambil daftar riwayat skrining mandiri & kontak serumah. |

**Hasil Suite**: `Tests: 9 passed, Time: 1.54s`

---

### 2.2. Suite Pengujian API Pengobatan Pasien (`MedicationApiTest.php`)

| No | Kasus Uji | Status | Keterangan |
|---|---|---|---|
| 1 | `test_unauthenticated_request_is_rejected` | **PASS (401)** | Akses tanpa token ditolak. |
| 2 | `test_patient_can_get_own_medications` | **PASS (200 OK)** | Pasien dapat melihat daftar obat dan dosis miliknya. |
| 3 | `test_idor_protection_patient_cannot_view_other_patient_medications` | **PASS (200 OK)** | Proteksi IDOR: request dengan parameter pasien lain tetap mengembalikan data milik pasien login. |
| 4 | `test_patient_without_prescription_returns_empty_cleanly` | **PASS (200 OK)** | Penanganan aman saat pasien belum memiliki resep. |
| 5 | `test_treatment_history_returns_normalized_medications_and_prescriptions_with_newline` | **PASS (200 OK)** | Normalisasi resep berbasis baris baru (*newline*). |
| 6 | `test_treatment_history_scopes_medications_per_treatment` | **PASS (200 OK)** | Resep obat terisolasi per riwayat pengobatan. |
| 7 | `test_treatment_history_with_null_prescription_returns_empty_cleanly` | **PASS (200 OK)** | Menangani nilai `null` pada kolom resep tanpa exception. |

**Hasil Suite**: `Tests: 7 passed, Time: 1.34s`

---

### 2.3. Suite Pengujian Integrasi Fitur Minum Obat (`MedicationFeaturesIntegrationTest.php`)

| No | Kasus Uji | Status | Keterangan |
|---|---|---|---|
| 1 | `test_patient_can_log_medication_with_photo_and_optional_note` | **PASS (201)** | Pasien mencatat minum obat dengan foto dan catatan. |
| 2 | `test_patient_cannot_log_medication_without_photo` | **PASS (422)** | Validasi mewajibkan foto bukti minum obat. |
| 3 | `test_patient_can_log_medication_with_photo_without_note` | **PASS (201)** | Catatan bersifat opsional saat foto valid dilampirkan. |
| 4 | `test_non_image_or_oversized_photo_is_rejected` | **PASS (422)** | Menolak file non-gambar (PDF) dan foto > 2MB. |
| 5 | `test_medication_note_exceeding_max_characters_is_rejected` | **PASS (422)** | Menolak catatan melebihi batas 500 karakter. |
| 6 | `test_duplicate_medication_record_on_same_day_is_prevented` | **PASS (422)** | Mencegah konfirmasi ganda pada hari yang sama (Asia/Jakarta). |
| 7 | `test_missed_medication_days_and_adherence_calculation` | **PASS (200 OK)** | Menghitung persentase kepatuhan dan mendeteksi hari terlewat. |
| 8 | `test_web_patient_monitoring_supports_period_filters` | **PASS (200 OK)** | Web monitoring mendukung filter periode (7 hari, 30 hari, bulan ini). |
| 9 | `test_notification_dispatched_only_to_responsible_officers` | **PASS (201)** | Notifikasi konfirmasi obat otomatis dikirim ke PJTB & Kader penanggung jawab. |
| 10 | `test_officer_can_mark_notification_as_read` | **PASS (302 Redirect)** | Petugas dapat menandai notifikasi telah dibaca. |

**Hasil Suite**: `Tests: 10 passed, Time: 2.70s`

---

### 2.4. Suite Pengujian Beranda Pasien Terpadu (`PatientHomeApiTest.php`)

| No | Kasus Uji | Status | Keterangan |
|---|---|---|---|
| 1 | `test_unauthenticated_request_is_rejected` | **PASS (401)** | Autentikasi wajib pada dashboard pasien. |
| 2 | `test_patient_can_get_home_data` | **PASS (200 OK)** | Mengembalikan data komprehensif pasien, pengobatan aktif, jadwal kontrol, status obat hari ini, dan percakapan terakhir. |
| 3 | `test_idor_protection_patient_cannot_view_other_patient_home` | **PASS (200 OK)** | Proteksi IDOR: Pasien dilarang melihat dashboard pasien lain. |

**Hasil Suite**: `Tests: 3 passed, Time: 1.12s`

---

### 2.5. Suite Pengujian Kontak Serumah Pasien (`CloseContactApiTest.php`)

| No | Kasus Uji | Status | Keterangan |
|---|---|---|---|
| 1 | `test_unauthenticated_request_is_rejected` | **PASS (401)** | Proteksi token pada endpoint kontak serumah. |
| 2 | `test_patient_can_list_own_contacts_only` | **PASS (200 OK)** | Pasien hanya melihat kontak miliknya sendiri. |
| 3 | `test_patient_can_create_contact_with_date_of_birth_and_auto_code` | **PASS (201)** | Pasien dapat menambahkan kontak serumah baru. |
| 4 | `test_validation_errors_when_required_fields_missing` | **PASS (422)** | Validasi field wajib (nama, hubungan, jenis kelamin). |
| 5 | `test_authorization_prevents_accessing_other_patient_contact` | **PASS (403)** | Pasien diblokir mengakses kontak milik pasien lain. |
| 6 | `test_patient_can_update_and_delete_own_contact` | **PASS (200 OK)** | Pasien dapat mengedit dan menghapus kontak miliknya. |
| 7 | `test_patient_can_screen_close_contact_and_sync_status` | **PASS (200 OK)** | Skrining kontak menyinkronkan status hasil & TPT. |
| 8 | `test_patient_cannot_submit_screening_for_other_patients_contact` | **PASS (403)** | Proteksi IDOR pada pengiriman skrining kontak. |

**Hasil Suite**: `Tests: 8 passed, Time: 1.87s`

---

### 2.6. Suite Pengujian Regresi Admin CRUD (`AdminDeleteCrudTest.php`)

| No | Kasus Uji | Status | Keterangan |
|---|---|---|---|
| 1-12 | Seluruh skenario penghapusan bertingkat (*cascading delete*) | **PASS (12/12 Passed)** | Memastikan perbaikan otorisasi pasien tidak merusak (*non-regression*) fungsi administratif Petugas dan Super Admin. |

**Hasil Suite**: `Tests: 12 passed, Time: 1.26s`

---

## 3. HASIL PENGUJIAN FLUTTER MOBILE (`flutter test` & `flutter analyze`)

### 3.1. Analisis Kode Sumber Statis (`flutter analyze` & `dart analyze`)
- **Perintah**: `flutter analyze`
- **Hasil**: **0 issues found!** (Semua file bebas dari error sintaks, warning tipe data, dan unhandled null safety).

### 3.2. Pengujian Unit & Widget Mobile Pasien (`flutter test`)
Pengujian dijalankan pada 7 test suite Flutter:
1. `test/patient_fixes_verification_test.dart` (2 passed):
   - Verifikasi fungsi pembersih URL `Connection.resolveImageUrl()` untuk Cloudflare 403 prevention.
   - Verifikasi `AlarmService.handleTreatment()` mematikan seluruh alarm saat status pengobatan `'Sembuh'`, `'Pengobatan Lengkap'`, atau `'Gagal'`.
2. `test/patient_home_test.dart` (3 passed):
   - Parsing JSON Beranda Pasien terpadu, serialisasi cache lokal, dan adaptasi legacy endpoint.
3. `test/patient_medications_test.dart` (11 passed):
   - Model obat, parsing resep newline/koma, rendering daftar obat pasien, dan penanganan empty state.
4. `test/household_members_test.dart` (9 passed):
   - Halaman anggota serumah, form tambah kontak, validasi NIK 16 digit, dan detail kontak.
5. `test/medication_notes_and_missed_days_test.dart` (10 passed):
   - Dialog konfirmasi obat, wajib unggah foto bukti, preview foto, dan penolakan tanpa foto.
6. `test/session_manager_test.dart` (3 passed):
   - Penanganan token expired (401), deactivasi akun (403), dan pembatalan alarm otomatis.
7. `test/treatment_start_date_test.dart` (3 passed):
   - Perhitungan durasi hari terapi dan format tanggal pengobatan pasien.

**Total Pengujian Mobile**: **43 passed, 0 failed** (100% Lulus).

---

## 4. MATRIKS VERIFIKASI KEAMANAN PERAN PASIEN

| Vektor Keamanan | Titik Rawan Sebelum Audit | Solusi yang Diimplementasikan | Hasil Pengujian |
|---|---|---|---|
| **Broken Access Control (Pengobatan)** | Pasien dapat mengubah diagnosis & resep di `/api/treatments/store` | Ditambahkan validasi `$user->isPatient()` -> HTTP 403 | **LULUS (403 Forbidden)** |
| **Broken Access Control (Jadwal Kontrol)** | Pasien dapat membuat/membatalkan jadwal kontrol di `/api/visits/*` | Ditambahkan validasi `$user->isPatient()` -> HTTP 403 | **LULUS (403 Forbidden)** |
| **Self-Verification Bypass** | Bukti foto langsung terverifikasi otomatis saat submit | Diatur `is_verified = false`, verifikasi dibatasi PJTB/Admin | **LULUS (Terverifikasi Pasca Telaah)** |
| **Master Data Deletion** | Pasien dapat menghapus profilnya via `DELETE /api/patients/{id}` | Diblokir dengan HTTP 403 Forbidden | **LULUS (403 Forbidden)** |
| **IDOR Skrining & Kontak** | Pasien dapat menskrining kontak milik pasien lain | Divalidasi kepemilikan `contact->patient_id == patient->id` | **LULUS (403 Forbidden)** |
| **Infinite Scroll Duplication** | UI menduplikasi record riwayat minum obat saat discroll | Ditambahkan slicing paginasi backend & deduplikasi key Flutter | **LULUS (Tidak Ada Duplikasi)** |
| **Cloudflare 403 Asset Block** | URL gambar fisik langsung ditolak WAF Cloudflare | Dialihkan ke endpoint aman `/api/image/{filename}` | **LULUS (Gambar Tampil Utuh)** |

---

## 5. KESIMPULAN

Seluruh fitur Role Pasien pada Flutter Mobile dan Laravel Web Backend telah diuji secara menyeluruh melalui puluhan kasus uji otomatis. Sistem kini memiliki pembatasan hak akses yang kokoh, alur bisnis verifikasi minum obat yang sesuai standar klinis DOTS, riwayat obat yang terpaginasi dengan rapi tanpa duplikasi, dan penanganan notifikasi/alarm yang tepat sasaran tanpa merusak fitur peran lain (Kader, Petugas PJTB, dan Admin).
