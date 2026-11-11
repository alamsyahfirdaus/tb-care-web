# REFINEMENT CARD RIWAYAT PENGOBATAN ROLE PASIEN (TB CARE)

## 1. Kondisi Tampilan Sebelum Perubahan

Berdasarkan audit dan screenshot referensi riwayat pengobatan pasien sebelumnya:
1. **Badge Status Berikon:** Pada sudut kanan atas card pengobatan, badge status (seperti `Berjalan`, `Selesai`, `Pengobatan Lengkap`, `Gagal`) memuat ikon kecil (`Icons.play_circle_fill_rounded`, `Icons.check_circle_rounded`, `Icons.help_rounded`) di sebelah kiri teks status.
2. **Ikon Kalender & Jam Redundan:**
   - Bagian **Periode Pengobatan** menampilkan ikon kalender (`Icons.calendar_today_rounded`) di sebelah kiri tanggal mulai & selesai.
   - Bagian **Waktu Minum Obat** menampilkan ikon jam (`Icons.access_time_filled_rounded`) di sebelah kiri waktu minum obat.
3. **Potensi Ketidakurutan Episode Pengobatan:** Riwayat pengobatan pasien berisiko menampilkan pengobatan lama di atas pengobatan yang sedang aktif jika query tidak secara eksplisit mengurutkan berdasarkan tanggal mulai terbaru dan primary key secara berjenjang. Selain itu, penomoran episode dapat terbalik jika nomor urut mengandalkan index array biasa.

---

## 2. Penghapusan Ikon Badge Status Pengobatan

- **Implementasi:** Seluruh ikon di sebelah kiri teks badge status dihilangkan, menghasilkan badge teks bersih (*clean text-only pill badge*).
- **Struktur Widget:**
  ```dart
  if (status.isNotEmpty)
    Container(
      padding: const EdgeInsets.symmetric(
        horizontal: 10,
        vertical: 4,
      ),
      decoration: BoxDecoration(
        color: _getStatusColor(status, true),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        status,
        style: GoogleFonts.plusJakartaSans(
          fontWeight: FontWeight.bold,
          fontSize: 11,
          color: _getStatusTextColor(status),
        ),
      ),
    ),
  ```
- **Konsistensi Warna Status:**
  - `Berjalan`: Background `Colors.green.shade50`, Teks `Colors.green.shade800` (Hijau).
  - `Selesai` / `Sembuh`: Background `#ECFDF5`, Teks `#065F46` (Hijau Segar).
  - `Pengobatan Lengkap`: Background `AppColors.primaryLight` (`#EAF1FB`), Teks `AppColors.primaryDark` (`#274C91`) (Biru).
  - `Gagal` / `Putus Berobat` / `Meninggal`: Background `#FEF2F2`, Teks `#B91C1C` (Merah).
  - Status lainnya: Background `Colors.grey.shade100`, Teks `Colors.grey.shade800` (Abu-abu Netral).

---

## 3. Penghapusan Ikon Kalender dan Jam

- **Periode Pengobatan:** Ikon kalender dihilangkan. Tanggal periode langsung dirender di bawah label dengan font semi-bold (13px, Plus Jakarta Sans, `#1F2937`):
  ```dart
  Text(
    'Periode Pengobatan',
    style: GoogleFonts.plusJakartaSans(
      fontSize: 11,
      fontWeight: FontWeight.w600,
      color: Colors.grey.shade500,
    ),
  ),
  const SizedBox(height: 4),
  Text(
    '$formattedStartDate — $formattedEndDate',
    style: GoogleFonts.plusJakartaSans(
      fontSize: 13,
      fontWeight: FontWeight.w600,
      color: Colors.grey.shade800,
    ),
  ),
  ```
- **Waktu Minum Obat:** Ikon jam dihilangkan. Waktu minum obat dengan format 24 jam dan akhiran "WIB" langsung dirender di bawah label:
  ```dart
  Text(
    'Waktu Minum Obat',
    style: GoogleFonts.plusJakartaSans(
      fontSize: 11,
      fontWeight: FontWeight.w600,
      color: Colors.grey.shade500,
    ),
  ),
  const SizedBox(height: 4),
  Text(
    '$medicationTime WIB',
    style: GoogleFonts.plusJakartaSans(
      fontSize: 13,
      fontWeight: FontWeight.w600,
      color: Colors.grey.shade800,
    ),
  ),
  ```
- **Skeleton Loader:** Skeleton shimmer juga diperbarui agar selaras tanpa lingkaran shimmer ikon sebelum tanggal dan waktu.

---

## 4. Perbaikan Urutan Episode Pengobatan (Terbaru di Posisi Teratas)

Pengurutan ganda (*two-tier sorting*) diterapkan secara konsisten pada Backend Laravel dan Frontend Flutter:

### A. Backend Laravel (`tb-care-web/app/Http/Controllers/Api/PatientController.php`)
1. Data episode pengobatan diambil secara kronologis untuk menetapkan nomor episode historis yang akurat (`episode_number` 1 untuk pengobatan pertama, 2 untuk pengobatan berikutnya, dst.).
2. Data kemudian diurutkan secara **descending** berdasarkan `start_date` dan `id` (`orderByDesc`):
   ```php
   $sortedTreatments = $treatmentsWithEpisode->sortByDesc(function ($treatment) {
       return ($treatment->start_date ?? '1970-01-01') . '_' . str_pad($treatment->id, 10, '0', STR_PAD_LEFT);
   })->values();
   ```
3. Respons API menyertakan kunci `episode_number` untuk setiap episode.

### B. Frontend Flutter (`tb-care-mobile/lib/main/pasien/treatment_history.dart`)
1. Method `_fetchTreatmentHistory()` melakukan proteksi pengurutan tambahan pada layer client:
   ```dart
   list.sort((a, b) {
     final aDate = a['start_date']?.toString() ?? '';
     final bDate = b['start_date']?.toString() ?? '';
     final dateCompare = bDate.compareTo(aDate);
     if (dateCompare != 0) return dateCompare;
     final aId = (a['id'] is int) ? a['id'] as int : int.tryParse(a['id']?.toString() ?? '') ?? 0;
     final bId = (b['id'] is int) ? b['id'] as int : int.tryParse(b['id']?.toString() ?? '') ?? 0;
     return bId.compareTo(aId);
   });
   ```
2. Judul card mengambil nomor episode secara presisi:
   ```dart
   final episodeNumber = treatment['episode_number'] ?? (totalCount - index);
   Text('Pengobatan #$episodeNumber', ...)
   ```
   Sehingga jika pasien memiliki 3 episode pengobatan:
   - Posisi paling atas: **Pengobatan #3** (Episode terbaru, misal: *Berjalan*).
   - Posisi tengah: **Pengobatan #2** (Episode terdahulu, misal: *Gagal*).
   - Posisi paling bawah: **Pengobatan #1** (Episode awal, misal: *Pengobatan Lengkap*).

---

## 5. File Source Code yang Dimodifikasi

| Komponen | File Path | Modifikasi Utama |
|---|---|---|
| **Flutter Mobile** | `tb-care-mobile/lib/main/pasien/treatment_history.dart` | Penghapusan ikon badge status, penghapusan ikon kalender dan jam, pengurutan client-side terbaru di atas, dan penomoran `Pengobatan #$episodeNumber`. |
| **Flutter Test** | `tb-care-mobile/test/patient_treatment_history_refinement_test.dart` | Unit & widget tests verifikasi ketiadaan ikon kalender/jam, badge teks murni, dan logika pengurutan episode terbaru. |
| **Laravel Backend** | `tb-care-web/app/Http/Controllers/Api/PatientController.php` | Pengurutan `sortByDesc` pada `start_date` dan `id`, serta penambahan field `episode_number` kronologis pada endpoint `GET /api/patients/{id}/treatments`. |
| **Laravel Test** | `tb-care-web/tests/Feature/MedicationEpisodeAndVerificationTest.php` | Feature test pengurutan episode terbaru di posisi teratas dan nomor episode kronologis. |

---

## 6. Konfirmasi Integritas Data Pengobatan

- Tidak ada skema database atau migrasi yang diubah.
- Data historis, relasi pasien dengan episode pengobatan, resep obat, dan riwayat minum obat tetap utuh dan terisolasi per episode.
- Hak akses dan endpoint role lain (Kader, Petugas Kesehatan PJTB, Super Admin) tidak mengalami perubahan atau degradasi fungsi.

---

## 7. Hasil Pengujian & Verifikasi

### A. Flutter Static Analysis (`flutter analyze`)
```bash
flutter analyze --no-pub lib/main/pasien/treatment_history.dart test/patient_treatment_history_refinement_test.dart
Analyzing 2 items...
No issues found! (ran in 5.3s)
```

### B. Flutter Widget & Unit Tests (`flutter test`)
```bash
flutter test test/patient_treatment_history_refinement_test.dart
00:02 +2: All tests passed!
✓ TreatmentHistoryPage UI/UX Refinement Tests Renders treatment cards with text-only status badges and no calendar/clock icons
✓ TreatmentHistoryPage UI/UX Refinement Tests Sorting logic orders newest start_date and highest id first
```

### C. Laravel PHPUnit Feature Tests (`php artisan test`)
```bash
php artisan test tests/Feature/MedicationEpisodeAndVerificationTest.php
PASS  Tests\Feature\MedicationEpisodeAndVerificationTest
✓ medication history strictly scoped by treatment id
✓ today unconfirmed dose is marked not confirmed and not counted as missed
✓ medication verification status distinguishes patient and kader
✓ treatment history orders newest first with chronological episode number

Tests:  4 passed (14 assertions)
Time:   0.54s
```

---

## 8. Checklist Verifikasi Batasan Sistem

- [x] Badge status tidak lagi memiliki ikon kecil.
- [x] Label status tetap tampil (`Berjalan`, `Selesai`, `Pengobatan Lengkap`, `Gagal`, dsb.).
- [x] Warna badge tetap sesuai status pengobatan.
- [x] Ikon kalender di sebelah kiri periode pengobatan telah dihapus.
- [x] Ikon jam di sebelah kiri waktu minum obat telah dihapus.
- [x] Tanggal mulai dan selesai tetap tampil lengkap (`05 November 2026 — 05 Mei 2027`).
- [x] Waktu minum obat tetap tampil format 24 jam dengan akhiran WIB (`08:00 WIB`).
- [x] Alignment teks rapi dan bersih.
- [x] Episode terbaru selalu berada di paling atas (`Pengobatan #3` di atas `Pengobatan #2` dan `Pengobatan #1`).
- [x] Daftar obat tetap tampil sesuai resep episode masing-masing.
- [x] Tidak ada regresi pada Role Kader, Petugas, maupun Admin.
