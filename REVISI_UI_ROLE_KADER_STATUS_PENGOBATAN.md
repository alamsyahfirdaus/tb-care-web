# DOKUMENTASI REVISI UI CARD STATUS PENGOBATAN
**Aplikasi TB Care — Audit Tampilan Beranda & Penyempurnaan Card Pengobatan**

---

## 1. Ringkasan Eksekutif & Identifikasi Komponen

Revisi antarmuka pengguna ini dilaksanakan berdasarkan acuan visual pada screenshot terbaru yang diberikan pengguna (`media_1794195049167_9fade8f7.png`).

### Hasil Audit Komponen & Pemetaan Role:
1. **Pemeriksaan Screenshot Referensi:**  
   Screenshot menampilkan header *"Selamat Pagi, Alamsyah Firdaus"* dengan card *"Status Pengobatan"*, *"Pengingat Pengobatan"*, *"Kontrol Berikutnya"*, serta 4 navigasi bawah (*Beranda*, *Pengobatan*, *Edukasi*, *Profil*).
2. **Penelusuran Source Code:**  
   - Card **Status Pengobatan** dengan format data `Hari ke-5`, `dari 181 hari`, `(3% selesai)` dan ikon kalender `5 November 2026 s.d. 5 Mei 2027` berada pada file:  
     `tb-care-mobile/lib/main/pasien/home.dart` (Fungsi `_buildTreatmentCard`, baris 577–745).
   - Pada halaman Beranda Kader (`tb-care-mobile/lib/main/kader/home.dart`), tampilannya berupa 4 card statistik agregat pemantauan (*Total Pasien, Belum Minum Obat, Sudah Minum Obat, Jadwal Kontrol Hari Ini*) serta section daftar aksi konfirmasi. Beranda kader **tidak menggunakan** card personal *"Status Pengobatan"*.
   - Pada halaman Daftar Pasien Kader (`tb-care-mobile/lib/main/kader/patient.dart`), tampilan durasi pengobatan sudah menggunakan format ringkas `Hari ke-$daysElapsed dari $totalDays hari` tanpa teks persentase.
3. **Keputusan Implementasi:**  
   Perubahan diterapkan secara presisi pada widget yang sesuai dengan screenshot acuan pengguna di `lib/main/pasien/home.dart`, dengan memastikan tidak ada dampak samping (*side effect*) pada Role Kader, Petugas, maupun Admin.

---

## 2. Rincian Perubahan yang Diterapkan

### Perubahan 1 — Penghapusan Persentase Progres Pengobatan
- **Kondisi Sebelum:**  
  Di bawah teks `dari $totalDays hari` terdapat baris teks persentase seperti `(3% selesai)` atau `(28% selesai)`.
- **Kondisi Sesudah:**  
  Teks persentase dan spasi pemisah dihapus sepenuhnya. Baris informasi kini hanya menampilkan:
  ```text
  Hari ke-5                         dari 181 hari
  ```
- **Ketentuan Teknis yang Dijaga:**  
  - Perhitungan durasi, tanggal mulai, dan tanggal selesai tidak diubah sama sekali.
  - Nilai progres persentase tetap digunakan untuk kalkulasi `LinearProgressIndicator` (progress bar garis tetap berfungsi normal).
  - Status pengobatan (badge *Berjalan*) tetap dipertahankan.

### Perubahan 2 — Penghapusan Ikon Kalender pada Periode Pengobatan
- **Kondisi Sebelum:**  
  Terdapat ikon kalender di sebelah kiri rentang tanggal pengobatan:  
  `[Icons.calendar_month_rounded] 5 November 2026 s.d. 5 Mei 2027`
- **Kondisi Sesudah:**  
  Ikon kalender dan jarak pemisah dihapus, menyisakan teks tanggal murni dengan alignment yang rapi:  
  `5 November 2026 s.d. 5 Mei 2027`
- **Ketentuan Teknis yang Dijaga:**  
  - Format tanggal (`dd MMMM yyyy s.d. dd MMMM yyyy`) tetap dipertahankan.
  - Warna font (`Colors.white.withValues(alpha: 0.85)`), ukuran font (12sp), dan ketebalan (`FontWeight.w500`) tetap sama persis.
  - Ikon kalender pada widget atau fitur lain (seperti pada card kunjungan berikutnya atau riwayat) tidak terpengaruh dan tetap utuh.

---

## 3. Komparasi Source Code (Diff)

**File:** `tb-care-mobile/lib/main/pasien/home.dart`

```diff
@@ -683,23 +683,14 @@
-              Column(
-                crossAxisAlignment: CrossAxisAlignment.end,
-                children: [
-                  Text(
-                    'dari $totalDays hari',
-                    style: GoogleFonts.plusJakartaSans(
-                      fontSize: 13,
-                      fontWeight: FontWeight.w600,
-                      color: Colors.white.withValues(alpha: 0.9),
-                    ),
-                  ),
-                  const SizedBox(height: 2),
-                  Text(
-                    '($percent% selesai)',
-                    style: GoogleFonts.plusJakartaSans(
-                      fontSize: 11,
-                      fontWeight: FontWeight.w600,
-                      color: Colors.white70,
-                    ),
-                  ),
-                ],
-              ),
+              Padding(
+                padding: const EdgeInsets.only(bottom: 2),
+                child: Text(
+                  'dari $totalDays hari',
+                  style: GoogleFonts.plusJakartaSans(
+                    fontSize: 13,
+                    fontWeight: FontWeight.w600,
+                    color: Colors.white.withValues(alpha: 0.9),
+                  ),
+                ),
+              ),
@@ -724,25 +715,13 @@
           // Periode Tanggal
           if (treatment.startDate != null)
-            Row(
-              children: [
-                const Icon(
-                  Icons.calendar_month_rounded,
-                  color: Colors.white70,
-                  size: 14,
-                ),
-                const SizedBox(width: 8),
-                Expanded(
-                  child: Text(
-                    _formatTreatmentPeriod(
-                      treatment.startDate,
-                      treatment.endDate,
-                    ),
-                    style: GoogleFonts.plusJakartaSans(
-                      color: Colors.white.withValues(alpha: 0.85),
-                      fontSize: 12,
-                      fontWeight: FontWeight.w500,
-                    ),
-                    maxLines: 1,
-                    overflow: TextOverflow.ellipsis,
-                  ),
-                ),
-              ],
-            ),
+            Text(
+              _formatTreatmentPeriod(
+                treatment.startDate,
+                treatment.endDate,
+              ),
+              style: GoogleFonts.plusJakartaSans(
+                color: Colors.white.withValues(alpha: 0.85),
+                fontSize: 12,
+                fontWeight: FontWeight.w500,
+              ),
+              maxLines: 1,
+              overflow: TextOverflow.ellipsis,
+            ),
```

---

## 4. Perlindungan Role Lain & Validasi Sistem

1. **Role Kader (`lib/main/kader/*`):**  
   Tidak ada baris kode pada direktori Kader yang diubah sehingga alur kerja, dashboard, kartu statistik, dan pemantauan pengobatan oleh kader tetap 100% utuh.
2. **Role Petugas & Admin:**  
   Backend API Laravel (`tb-care-web`) dan fungsionalitas admin tidak mengalami perubahan struktural sehingga integritas data tetap terjamin.
3. **Pengujian Widget (`test/home_page_widget_test.dart`):**  
   Pengujian disesuaikan untuk memvalidasi:
   - Teks `Hari ke-36` dan `dari 181 hari` tetap ditemukan.
   - Teks persentase `(20% selesai)` dan substring yang mengandung `selesai)` **tidak lagi ditemukan** (`findsNothing`).
   - Ikon kalender pada card pengobatan tidak ada (`findsNothing`).

---
*Dokumen ini merupakan catatan teknis resmi penyempurnaan UI TB Care.*
