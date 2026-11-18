# DOKUMENTASI REFINEMENT CARD INFORMASI PENGOBATAN ROLE PASIEN TB CARE
## Penyederhanaan Visual & Pembersihan Ikon pada Card Informasi Pengobatan

Dokumen ini mencatat analisis, detail perubahan arsitektur tampilan, implementasi teknis, dan hasil verifikasi pengujian terkait penyempurnaan UI/UX card **Informasi Pengobatan** pada menu Pengobatan Role Pasien aplikasi TB Care.

---

## 1. KONDISI SEBELUM PERUBAHAN

Berdasarkan screenshot referensi aktual dan inspeksi source code:
- Card **Informasi Pengobatan** pada Tab 1 menu Pengobatan Role Pasien menampilkan 5 pasang label dan nilai informasi medis:
  1. `[Icons.medical_services_rounded]` Jenis Pengobatan (misal: "TB Laten")
  2. `[Icons.calendar_today_rounded]` Tanggal Mulai (misal: "18 November 2026")
  3. `[Icons.event_available_rounded]` Tanggal Selesai (misal: "18 Mei 2027")
  4. `[Icons.timelapse_rounded]` Durasi Pengobatan (misal: "6 Bulan")
  5. `[Icons.access_time_rounded]` Waktu Minum Obat (misal: "08:00 WIB")
- Terdapat indentasi buatan (`padding: const EdgeInsets.only(left: 22)`) pada nilai data untuk menyelaraskan teks nilai dengan teks label setelah ikon selebar 16px dan jarak 6px.
- Penggunaan ikon dekoratif di setiap baris membuat visual card tampak padat dan menyita ruang horizontal, serta kurang konsisten dengan gaya desain minimalis yang telah diterapkan pada card-card lainnya di TB Care.
- Label tanggal akhir menggunakan kata "Tanggal Selesai", yang dapat menimbulkan kesan bahwa pasien pasti sembuh pada tanggal tersebut, padahal durasi pengobatan TB merupakan estimasi waktu evaluasi klinis.

---

## 2. PERUBAHAN 1 — PENGHAPUSAN SELURUH IKON DALAM CARD

Seluruh ikon kecil di dalam card Informasi Pengobatan telah dihapus tanpa meninggalkan sisa ruang kosong:

1. **Ikon yang Dihapus:**
   - Ikon kotak obat/layanan medis (`Icons.medical_services_rounded`) pada **Jenis Pengobatan**.
   - Ikon kalender (`Icons.calendar_today_rounded`) pada **Tanggal Mulai**.
   - Ikon kalender ketersediaan (`Icons.event_available_rounded`) pada **Perkiraan Selesai**.
   - Ikon jam pasir/durasi (`Icons.timelapse_rounded`) pada **Durasi Pengobatan**.
   - Ikon jam dinding (`Icons.access_time_rounded`) pada **Waktu Minum Obat**.

2. **Perataan Teks Rata Kiri (*Flush Left Alignment*):**
   - Elemen `Row` pembungkus ikon dan label dihilangkan.
   - Indentasi `left: 22px` pada teks nilai dihapus.
   - Teks label (`fontSize: 12`, `color: Colors.grey.shade500`, `fontWeight: FontWeight.w500`) dan teks nilai (`fontSize: 14`, `color: Colors.grey.shade800`, `fontWeight: FontWeight.bold`) kini tersusun vertikal dalam `Column(crossAxisAlignment: CrossAxisAlignment.start)` yang rata kiri secara presisi.

3. **Konsistensi Layout Dua Kolom & Garis Pemisah:**
   - Baris Tanggal Mulai & Perkiraan Selesai tetap menggunakan tata letak dua kolom (`Expanded` dengan jarak `SizedBox(width: 16)`).
   - Baris Durasi Pengobatan & Waktu Minum Obat tetap menggunakan tata letak dua kolom.
   - Garis pemisah tipis (`Divider(height: 24, thickness: 1, color: Color(0xFFF1F5F9))`) tetap dipertahankan antarbagian informasi.

---

## 3. PERUBAHAN 2 — PERUBAHAN LABEL TANGGAL SELESAI

Label informasi tanggal akhir pengobatan telah diperbarui:

- **Sebelum:** `Tanggal Selesai`
- **Sesudah:** `Perkiraan Selesai`

### Ketentuan & Klarifikasi Medis:
- Label "Perkiraan Selesai" mengindikasikan estimasi tanggal akhir periode pengobatan standar, bukan garansi kesembuhan mutlak.
- Nilai tanggal tetap dihitung dari tanggal mulai dan durasi episode pengobatan yang sedang berjalan, diformat rapi dalam Bahasa Indonesia (contoh: "18 Mei 2027").
- Perubahan ini murni pada presentasi teks UI, tanpa mengubah struktur database, kolom API, maupun rumus kalkulasi backend.

---

## 4. ILUSTRASI PERBANDINGAN TAMPILAN

### Sebelum Perubahan:
```text
┌──────────────────────────────────────────┐
│ INFORMASI PENGOBATAN                     │
│ ──────────────────────────────────────── │
│                                          │
│ [💼] Jenis Pengobatan                    │
│      TB Laten                            │
│ ──────────────────────────────────────── │
│ [📅] Tanggal Mulai     [📅] Tanggal Selesai │
│      18 November 2026       18 Mei 2027  │
│ ──────────────────────────────────────── │
│ [⏱️] Durasi Pengobatan [🕒] Waktu Minum Obat│
│      6 Bulan                08:00 WIB    │
│                                          │
└──────────────────────────────────────────┘
```

### Sesudah Perubahan (Minimalis, Rata Kiri & Bersih):
```text
┌──────────────────────────────────────────┐
│ INFORMASI PENGOBATAN                     │
│ ──────────────────────────────────────── │
│                                          │
│ Jenis Pengobatan                         │
│ TB Laten                                 │
│ ──────────────────────────────────────── │
│ Tanggal Mulai           Perkiraan Selesai │
│ 18 November 2026        18 Mei 2027      │
│ ──────────────────────────────────────── │
│ Durasi Pengobatan      Waktu Minum Obat  │
│ 6 Bulan                08:00 WIB         │
│                                          │
└──────────────────────────────────────────┘
```

---

## 5. FILE YANG DIMODIFIKASI

### File: `tb-care-mobile/lib/main/pasien/treatment.dart`

1. **Fungsi `_buildTreatmentInfoCard`:**
   Pemanggilan `_buildStatusDetailRow` diperbarui tanpa parameter ikon dan label diubah menjadi "Perkiraan Selesai":
   ```dart
   _buildStatusDetailRow(
     "Jenis Pengobatan",
     _getTreatmentType(treatmentTypeId),
   ),
   ...
   Row(
     crossAxisAlignment: CrossAxisAlignment.start,
     children: [
       Expanded(
         child: _buildStatusDetailRow(
           "Tanggal Mulai",
           startDateFormatted,
         ),
       ),
       const SizedBox(width: 16),
       Expanded(
         child: _buildStatusDetailRow(
           "Perkiraan Selesai",
           endDateFormatted,
         ),
       ),
     ],
   ),
   ...
   Row(
     crossAxisAlignment: CrossAxisAlignment.start,
     children: [
       Expanded(
         child: _buildStatusDetailRow(
           "Durasi Pengobatan",
           duration,
         ),
       ),
       const SizedBox(width: 16),
       Expanded(
         child: _buildStatusDetailRow(
           "Waktu Minum Obat",
           formattedMedicationTime,
         ),
       ),
     ],
   ),
   ```

2. **Helper Widget `_buildStatusDetailRow`:**
   Disederhanakan untuk menampilkan label dan nilai rata kiri tanpa ikon dan tanpa padding indentasi:
   ```dart
   Widget _buildStatusDetailRow(String label, String value) {
     return Column(
       crossAxisAlignment: CrossAxisAlignment.start,
       children: [
         Text(
           label,
           style: GoogleFonts.plusJakartaSans(
             color: Colors.grey.shade500,
             fontSize: 12,
             fontWeight: FontWeight.w500,
           ),
           maxLines: 1,
           overflow: TextOverflow.ellipsis,
         ),
         const SizedBox(height: 4),
         Text(
           value,
           style: GoogleFonts.plusJakartaSans(
             fontWeight: FontWeight.bold,
             color: Colors.grey.shade800,
             fontSize: 14,
           ),
         ),
       ],
     );
   }
   ```

3. **Skeleton Shimmer `_buildTreatmentInfoCardSkeleton`:**
   Placeholder loading shimmer diselaraskan dengan card aktual tanpa placeholder lingkaran ikon dan tanpa padding offset 22px.

---

## 6. KONFIRMASI INTEGRITAS SISTEM & BACKEND

- **API Laravel:** Tidak ada perubahan pada endpoint `/api/v1/treatments` maupun format payload JSON respons.
- **Database:** Tidak ada perubahan kolom, migrasi, atau field schema.
- **Komponen Global & Role Lain:**
  - `_buildStatusDetailRow` merupakan private helper method lokal di dalam `_TreatmentPageState` (Role Pasien), sehingga tidak ada dampak samping pada Role Petugas, Kader, atau Admin.
  - Ikon pada App Bar, TabBar, tombol aksi ("Tandai Sudah Minum Obat"), dan Bottom Navigation Bar tetap dipertahankan seutuhnya.

---

## 7. HASIL PENGUJIAN DAN ANALISIS STATIS

Pengujian komprehensif dijalankan menggunakan Flutter Test Suite dan Static Analyzer:

### A. Pengujian Widget (`test/patient_treatment_info_card_refinement_test.dart`)
1. **Verifikasi Keberadaan Label & Nilai:**
   - Memastikan teks "Jenis Pengobatan" & nilai "TB Laten" tampil.
   - Memastikan teks "Tanggal Mulai" & nilai "18 November 2026" tampil.
   - Memastikan teks "Perkiraan Selesai" & nilai "18 Mei 2027" tampil.
   - Memastikan teks lama "Tanggal Selesai" **tidak ditemukan** (`findsNothing`).
   - Memastikan teks "Durasi Pengobatan" & nilai "6 Bulan" tampil.
   - Memastikan teks "Waktu Minum Obat" & nilai "08:00 WIB" tampil.
2. **Verifikasi Penghapusan 5 Ikon:**
   - `Icons.medical_services_rounded` -> 0 (`findsNothing`)
   - `Icons.calendar_today_rounded` -> 0 (`findsNothing`)
   - `Icons.event_available_rounded` -> 0 (`findsNothing`)
   - `Icons.timelapse_rounded` -> 0 (`findsNothing`)
   - `Icons.access_time_rounded` -> 0 (`findsNothing`)
3. **Uji Responsivitas Layar Kecil:**
   - Diuji pada resolusi 320x600 dan 360x640: Tidak ada overflow render flex (`tester.takeException() == null`).

### B. Hasil Eksekusi Test:
```bash
$ flutter test test/patient_treatment_info_card_refinement_test.dart test/patient_treatment_tab_and_medication_test.dart
00:01 +2: All tests passed! (patient_treatment_info_card_refinement_test.dart)
00:01 +3: All tests passed! (patient_treatment_tab_and_medication_test.dart)
```
**Total: 5/5 tests passed (100% green).**

### C. Analisis Statis Kode (`flutter analyze`):
```bash
$ flutter analyze
Analyzing tb-care-mobile...
No issues found! (ran in 13.1s)
```
**Hasil:** 0 error, 0 warning, 0 lint issue.

---

## 8. KESIMPULAN

Card **Informasi Pengobatan** Role Pasien kini memiliki tampilan yang jauh lebih bersih, modern, dan minimalis:
- Seluruh ikon dekoratif kecil telah dihapus tanpa meninggalkan ruang kosong bekas ikon.
- Teks label dan nilai data tersusun rapi dengan perataan kiri (*flush left*).
- Label "Tanggal Selesai" telah berhasil diperbarui menjadi "Perkiraan Selesai".
- Seluruh data, fungsionalitas tombol, jadwal alarm, dan riwayat pengobatan tetap berjalan dengan akurasi 100%.
