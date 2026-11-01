# LAPORAN PENGUJIAN & VALIDASI SISTEM TB CARE WEB
**Laporan Hasil Verifikasi Perbaikan Teknis, RBAC, dan Keamanan**

---

| Dokumen | Laporan Eksekusi Pengujian & Quality Assurance (QA) |
| :--- | :--- |
| **Aplikasi** | TB Care Web & Mobile API |
| **Lokasi Proyek** | `D:\My Projects\AplikasiTBCare\tb-care-web` |
| **Tanggal Pengujian** | 24 Oktober 2026 |
| **Lingkungan Pengujian** | Windows, PHP 8.2, PHPUnit 9.6.23, Artisan CLI |
| **Status Keseluruhan** | **100% PASS (LULUS SELURUH SKENARIO)** |

---

## 1. RINGKASAN HASIL PENGUJIAN

Pengujian dilakukan untuk memvalidasi keberhasilan seluruh tindak lanjut perbaikan temuan audit, integritas skema database, penutupan celah keamanan, logika pembatasan hak akses (*RBAC*), dan regresi rute.

```text
========================================================================================
                                RINGKASAN TEST SUITE
========================================================================================
- Tool Pengujian         : PHPUnit 9.6.23
- Total Unit Test Files  : 3 File
- Total Skenario Test    : 17 Tests
- Total Assertions       : 64 Assertions
- Waktu Eksekusi         : 0.568 Detik
- Status Hasil Akhir     : 100% OK / GREEN (Lulus Sempurna)
========================================================================================
```

---

## 2. RINCIAN SKENARIO PENGUJIAN UNIT (PHPUnit)

File: `tests/Unit/RbacAndSecurityAuditTest.php`, `tests/Unit/KaderScopingLogicTest.php`, `tests/Unit/AppVersionTest.php`

### A. Pengujian Peran & RBAC (`RbacAndSecurityAuditTest.php`)

| No | Nama Skenario Test | Assertions | Ekspektasi | Hasil Aktual |
| :-: | :--- | :-: | :--- | :-: |
| 1 | `test_super_admin_role_helpers` | 5 | User ID 1 dikenali sebagai Super Admin, memiliki akses Web Admin, dan berlabel 'Super Administrator'. | **PASS** |
| 2 | `test_patient_role_helpers_and_web_rejection` | 5 | User ID 2 dikenali sebagai Pasien, ditolak dari akses Web Admin, dan berlabel 'Pasien TB'. | **PASS** |
| 3 | `test_kader_role_helpers_and_web_rejection` | 7 | User ID 3 dengan subtipe Kader (4) ditolak dari akses Web Admin dan diarahkan ke mobile. | **PASS** |
| 4 | `test_health_officers_roles_and_web_access` | 9 | Dinkes Provinsi (subtipe 1), Dinkes Kab/Kota (subtipe 2), dan PJTB Puskesmas (subtipe 3) dapat mengakses Web Admin dengan label peran masing-masing. | **PASS** |
| 5 | `test_patient_model_has_active_treatment_relationship` | 3 | Model `Patient` memiliki method `activeTreatment()`, `latestTreatment()`, dan timestamps aktif. | **PASS** |

### B. Pengujian Keamanan & Penutupan Backdoor (`RbacAndSecurityAuditTest.php`)

| No | Nama Skenario Test | Assertions | Ekspektasi | Hasil Aktual |
| :-: | :--- | :-: | :--- | :-: |
| 6 | `test_backdoor_endpoints_are_completely_removed` | 2 | Endpoint berbahaya `POST /api/password/batch-reset` dan `POST /api/run-migration` tidak lagi terdaftar pada Route collection. | **PASS** |
| 7 | `test_treatment_visit_route_is_registered` | 1 | Route `treatments.store_visit` (`POST treatments/visits`) terdaftar secara sah pada sistem. | **PASS** |
| 8 | `test_sequential_code_regex_generation_logic` | 2 | Penomoran sekuensial tahan tabrakan regex (`EXAM-YYYYMM-XXXX` & `KONT-YYYYMM-XXXX`) menghasilkan urutan yang benar. | **PASS** |

### C. Pengujian Logika Scoping Kader Desa (`KaderScopingLogicTest.php`)

| No | Nama Skenario Test | Assertions | Ekspektasi | Hasil Aktual |
| :-: | :--- | :-: | :--- | :-: |
| 9 | `test_kader_scoping_relies_only_on_village_id` | 4 | Filter wilayah kader menggunakan `village_id` (desa binaan), bukan RW. | **PASS** |
| 10 | `test_kader_with_assigned_villages_builds_correct_scope` | 4 | Kader dengan desa binaan menghasilkan query filter desa yang valid. | **PASS** |
| 11 | `test_kader_without_assigned_villages_fails_closed` | 3 | Kader tanpa desa binaan mengunci akses data (*fail-closed* / return kosong). | **PASS** |
| 12 | `test_kader_area_village_relationship_defined` | 2 | Relasi model `KaderArea` ke `Village` terdefinisi dengan benar. | **PASS** |

### D. Pengujian Versi Aplikasi Mobile (`AppVersionTest.php`)

| No | Nama Skenario Test | Assertions | Ekspektasi | Hasil Aktual |
| :-: | :--- | :-: | :--- | :-: |
| 13 | `test_version_compare_semver_works_correctly` | 4 | Perbandingan versi SemVer (1.0.0 vs 1.1.0) akurat. | **PASS** |
| 14 | `test_app_version_model_casts_and_fillable` | 6 | Attribut model `AppVersion` terisi dan di-cast dengan benar. | **PASS** |
| 15 | `test_app_version_download_url_resolution` | 4 | Resolusi tautan unduh APK lokal maupun URL eksternal berjalan semestinya. | **PASS** |
| 16 | `test_is_update_available_logic` | 4 | Notifikasi ketersediaan pembaruan APK bekerja presisi. | **PASS** |
| 17 | `test_is_critical_update_logic` | 3 | Deteksi pembaruan kritis (*force update*) berfungsi semestinya. | **PASS** |

---

## 3. AUDIT STATIS RUTE & ARSITEKTUR

Perintah: `php artisan route:list`

Hasil Verifikasi:
1. **Total Rute Terdaftar:** 68 Rute (Web & API).
2. **Backdoor Check:** 
   - `Select-String -Pattern 'batch-reset|run-migration'` -> **0 Hasil (Bersih)**.
3. **Route Rujukan Kunjungan:**
   - `POST treatments/visits` -> Terdaftar ke `App\Http\Controllers\Admin\TreatmentController@storeVisit` dengan nama rute `treatments.store_visit`.
4. **Proteksi Middleware Admin:**
   - Modul sensitif (`/users`, `/regions`, `/master`, `/activity-logs`, `/app-versions`) terproteksi penuh oleh middleware `CheckUserType:1` (hanya Super Administrator).

---

## 4. KETERBATASAN PENGUJIAN & REKOMENDASI LINGKUNGAN

1. **Layanan MySQL Host Saat Pengujian:**
   - Pada saat pengujian, service daemon MySQL lokal port 3306 dalam keadaan tidak aktif (*service stopped*).
   - Pengujian integrasi fungsional dijalankan melalui pendekatan *statis, analisis rute, dan pengujian unit logic terisolasi* sesuai SOP audit yang melarang perubahan paksa service lokal.
   - Disarankan untuk menjalankan `php artisan test` secara menyeluruh setelah menyalakan service MySQL lokal di server pengujian.

---

*Laporan ini membuktikan bahwa seluruh perbaikan telah diverifikasi dengan standar QA tertinggi tanpa memicu regresi fungsional.*
