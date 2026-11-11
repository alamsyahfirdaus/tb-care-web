# LOKALISASI WAKTU RELATIF ROLE PASIEN (TB CARE)

## 1. Kondisi Sebelum Perubahan

Pada dialog **Detail Minum Obat** khusus Role Pasien (`lib/main/pasien/history.dart`), informasi waktu pengisian menampilkan timestamp absolut berbahasa Indonesia bersamaan dengan waktu relatif berbahasa Inggris:

```text
Waktu Pengisian
Rabu, 11 November 2026 – 05:20 WIB
7 hours ago
```

Masalah ini terjadi karena:
1. Kolom `submitted_relative` pada respons backend Laravel (`TreatmentController.php` dan `PatientTreatment.php`) memanggil method `diffForHumans()` dari Carbon pada konfigurasi default bahasa Inggris (`'locale' => 'en'`), sehingga menghasilkan teks seperti `7 hours ago`, `1 day ago`, `2 days ago`, dsb.
2. Aplikasi mobile menampilkan string `submitted_relative` secara langsung tanpa memvalidasi atau melokalisasi bahasa ke dalam Bahasa Indonesia.
3. Beberapa fitur notifikasi dan pengingat juga berpotensi menampilkan format relatif berbahasa Inggris jika menerima nilai mentah `time_ago`.

---

## 2. Format Waktu Relatif yang Diperbaiki

Seluruh teks waktu relatif kini dikonversi dan dihitung secara dinamis ke dalam **Bahasa Indonesia** sesuai tabel standar tata bahasa:

| Bahasa Inggris | Bahasa Indonesia | Status Pengujian |
|---|---|:---:|
| `just now` / `right now` | **Baru saja** | ✅ Lolos |
| `a few seconds ago` | **Beberapa detik yang lalu** | ✅ Lolos |
| `1 minute ago` | **1 menit yang lalu** | ✅ Lolos |
| `5 minutes ago` | **5 menit yang lalu** | ✅ Lolos |
| `1 hour ago` | **1 jam yang lalu** | ✅ Lolos |
| `7 hours ago` | **7 jam yang lalu** | ✅ Lolos |
| `1 day ago` | **1 hari yang lalu** | ✅ Lolos |
| `2 days ago` | **2 hari yang lalu** | ✅ Lolos |
| `1 week ago` | **1 minggu yang lalu** | ✅ Lolos |
| `3 weeks ago` | **3 minggu yang lalu** | ✅ Lolos |
| `1 month ago` | **1 bulan yang lalu** | ✅ Lolos |
| `2 months ago` | **2 bulan yang lalu** | ✅ Lolos |
| `1 year ago` | **1 tahun yang lalu** | ✅ Lolos |
| `2 years ago` | **2 tahun yang lalu** | ✅ Lolos |

### Ketentuan Implementasi
- **Dinamis**: Menghitung selisih waktu berdasarkan timestamp aktual ([DateTime.now() - submittedAt]).
- **Anti-Negatif & Clock Drift**: Jika terdapat deviasi waktu (misal jam HP sedikit lebih lambat atau zona waktu WIB UTC+7), selisih negatif dalam batas toleransi diperlakukan dengan aman sebagai `"Baru saja"`.
- **Dual-Layer Protection**: 
  - Di layer **Flutter Mobile**, `TimeAgoHelper` melakukan komputasi dinamis dari `submitted_at`, sekaligus menyediakan parser/lokalisator `localizeString` untuk memetakan string bahasa Inggris dari server jika timestamp tanggal tidak lengkap.
  - Di layer **Laravel Backend**, Carbon dikonfigurasi ke locale `id` (`Carbon::setLocale('id')` dan `->locale('id')->diffForHumans()`), memastikan seluruh payload JSON API selalu berbahasa Indonesia.

---

## 3. File yang Dimodifikasi

### A. Flutter Mobile (`tb-care-mobile`)
1. **`lib/services/time_ago_helper.dart`** *(Baru)*:
   - Helper modular untuk format waktu relatif dinamis Bahasa Indonesia.
   - Fungsi `format(DateTime? dateTime, {DateTime? clock, String? rawFallback})`.
   - Fungsi `localizeString(String raw)` untuk fallback terjemahan string bahasa Inggris.
2. **`lib/main/pasien/history.dart`**:
   - Memodifikasi dialog `_showDetailsDialog` untuk memformat waktu pengisian dengan `TimeAgoHelper.format(submittedAt, rawFallback: rawRelative)`.
   - Menggantikan teks statis `submitted_relative` agar 100% menggunakan Bahasa Indonesia (`7 jam yang lalu`).
3. **`lib/main/notifications/notification_list_page.dart`**:
   - Memastikan `time_ago` pada notifikasi dilokalisasi melalui `TimeAgoHelper.localizeString()`.
4. **`lib/main/kader/reminders.dart`**:
   - Memastikan `time_ago` pada daftar riwayat pengingat kader dilokalisasi melalui `TimeAgoHelper.localizeString()`.
5. **`test/time_ago_helper_test.dart`** *(Baru)*:
   - 19 skenario pengujian komprehensif untuk seluruh rentang waktu (detik, menit, jam, hari, minggu, bulan, tahun, selisih negatif, dan lokalisasi string).
6. **`test/patient_medication_detail_localization_test.dart`** *(Baru)*:
   - Pengujian widget pada dialog Detail Minum Obat untuk memastikan `7 jam yang lalu` muncul menggantikan `7 hours ago` dan tidak ada kata kunci bahasa Inggris yang tertinggal.

### B. Laravel Backend (`tb-care-web`)
1. **`app/Providers/AppServiceProvider.php`**:
   - Mendaftarkan `\Carbon\Carbon::setLocale('id');` dan `\Illuminate\Support\Carbon::setLocale('id');` pada `boot()`.
2. **`app/Http/Controllers/Api/TreatmentController.php`**:
   - Mengubah `'submitted_relative' => $record->created_at ? $record->created_at->locale('id')->diffForHumans() : null`.
3. **`app/Models/PatientTreatment.php`**:
   - Mengubah `'submitted_relative' => $rec->created_at ? $rec->created_at->locale('id')->diffForHumans() : null` pada record minum obat harian.
4. **`app/Http/Controllers/Api/KaderController.php`**:
   - Mengubah `'time_ago' => $r->created_at ? $r->created_at->locale('id')->diffForHumans() : null`.
5. **`app/Http/Controllers/Api/NotificationController.php`**:
   - Mengubah `'time_ago' => $createdAt ? $createdAt->locale('id')->diffForHumans() : null`.

---

## 4. Helper atau Package yang Digunakan

- Tidak menambahkan package pihak ketiga baru (menjaga dependensi aplikasi tetap bersih dan ringan).
- Mengoptimalkan package bawaan yang sudah ada:
  - **`intl`**: Format tanggal dan waktu absolut (`DateFormat('EEEE, d MMMM yyyy - HH:mm', 'id_ID')`).
  - **`nesbot/carbon` (Backend)**: Built-in localization bahasa Indonesia (`locale('id')`).
  - **`TimeAgoHelper` (Mobile)**: Custom high-performance utility class di `lib/services/time_ago_helper.dart`.

---

## 5. Hasil Flutter Analyze

```bash
flutter analyze --no-pub lib/services/time_ago_helper.dart lib/main/pasien/history.dart lib/main/notifications/notification_list_page.dart lib/main/kader/reminders.dart test/time_ago_helper_test.dart test/patient_medication_detail_localization_test.dart
```

**Status**: `No issues found!` (0 error, 0 warning).

---

## 6. Hasil Flutter Test

```bash
flutter test test/time_ago_helper_test.dart test/patient_medication_detail_localization_test.dart
```

**Hasil Pengujian**:
- `TimeAgoHelper Dynamic Time Tests` (16 skenario waktu): **PASSED**
- `TimeAgoHelper String Localization Tests` (3 skenario string & fallback): **PASSED**
- `Converts English time strings from backend to Indonesian without English keywords`: **PASSED**
- `Detail Minum Obat dialog renders 7 jam yang lalu instead of 7 hours ago`: **PASSED**
- **Total**: 21/21 tests passed (100%).

---

## 7. Verifikasi Visual Target

### Sebelum
```text
Detail Minum Obat

Status
Sudah Minum

Waktu Pengisian
Rabu, 11 November 2026 – 05:20 WIB
7 hours ago

Bukti Foto
[FOTO]

Keterangan / Catatan Pasien
-
```

### Sesudah
```text
Detail Minum Obat

Status
Sudah Minum

Waktu Pengisian
Rabu, 11 November 2026 – 05:20 WIB
7 jam yang lalu

Bukti Foto
[FOTO]

Keterangan / Catatan Pasien
-
```
Semua styling dialog, border radius 20, warna header biru AppColors.primary, tombol Tutup, dan preview bukti foto tetap 100% terjaga dan berfungsi normal.
