# DOKUMENTASI FINAL REFINEMENT CARD JADWAL KONTROL — ROLE PASIEN TB CARE

Dokumen ini mencatat penyempurnaan UI/UX tahap akhir pada kartu (*card*) jadwal kontrol khusus **Role Pasien** di aplikasi Flutter Mobile TB Care (`tb-care-mobile`).

---

## 1. KONDISI SEBELUM PERUBAHAN

Sebelum penyempurnaan dilakukan, card jadwal kontrol menampilkan informasi secara vertikal dalam tiga baris:

```text
┌──────────────────────────────────────────────┐
│ Senin, 09 November 2026                     │
│ 23:30 WIB                                    │
│ Puskesmas Tamansari, Tamansari, Kota Tasik...│
└──────────────────────────────────────────────┘
```

**Kelemahan Tampilan Lama:**
- Waktu kontrol berada di baris tersendiri di bawah tanggal, sehingga card menjadi tinggi dan memakan ruang vertikal.
- Menampilkan teks lokasi Puskesmas lengkap (*Nama Puskesmas, Kecamatan, Kabupaten/Kota*) pada setiap card membuat tampilan card terasa padat dan berulang.
- Pasien kesulitan menangkap informasi esensial (kapan tanggal dan jam kontrol) dalam sekali lihat (*at a glance*).

---

## 2. PERUBAHAN POSISI TANGGAL DAN WAKTU (HORIZONTAL LAYOUT)

Posisi waktu kontrol dipindahkan ke sebelah kanan tanggal dalam satu baris horizontal:

```text
┌──────────────────────────────────────────────┐
│ Senin, 09 November 2026          23:30 WIB  │
└──────────────────────────────────────────────┘
```

### Detail Implementasi:
- Menggunakan `Row` dengan `mainAxisAlignment: MainAxisAlignment.spaceBetween` dan `crossAxisAlignment: CrossAxisAlignment.center`.
- **Tanggal (Kiri):**
  - Dibungkus dengan `Expanded` untuk mencegah `RenderFlex overflow`.
  - Format hari dan tanggal: `EEEE, dd MMMM yyyy` (contoh: *Senin, 09 November 2026*).
  - Menggunakan tipografi `GoogleFonts.plusJakartaSans` dengan ukuran `14px`, `FontWeight.w600` (semi-bold), dan warna gelap elegan (`Color(0xFF0F172A)`).
  - Diberikan atribut `maxLines: 1` dan `overflow: TextOverflow.ellipsis` agar aman pada layar perangkat Android berukuran kecil.
- **Waktu (Kanan):**
  - Rata kanan (`textAlign: TextAlign.right`).
  - Format waktu 24 jam dengan akhiran zona: `HH:mm WIB` (contoh: *23:30 WIB*).
  - Menggunakan warna biru utama TB Care (`AppColors.primary`), ukuran `13px`, dan `FontWeight.w600` agar kontras dan mudah dikenali pasien.

---

## 3. PENGHAPUSAN INFORMASI PUSKESMAS DAN LOKASI DARI CARD

- Teks nama Puskesmas, Kecamatan, dan Kabupaten/Kota **dihapus seutuhnya dari tampilan card**.
- Ruang vertikal bekas teks lokasi dihilangkan. Padding card disesuaikan menjadi `EdgeInsets.symmetric(horizontal: 16, vertical: 15)` sehingga card menjadi ramping, ringkas, dan modern.
- **Informasi Lokasi Tetap Tersedia:** Data Puskesmas dan alamat lengkap tetap dapat dilihat oleh pasien ketika menekan/mengetuk card jadwal tersebut melalui modal dialog detail (*`Detail Jadwal Kontrol`*).

---

## 4. KONSISTENSI PENERAPAN

Perubahan struktur card ini diterapkan secara seragam pada seluruh bagian halaman:
1. **Seksi Jadwal Mendatang:** Seluruh card jadwal mendatang tampil ringkas dengan format horizontal (tanggal kiri, waktu kanan).
2. **Seksi Riwayat Jadwal Kontrol:** Seluruh card jadwal lampau/selesai tampil dengan desain horizontal yang konsisten.
3. **Card Pengingat Kontrol (Bagian Atas):** Tetap dipertahankan dengan switch on/off dan filter chip interval (*30 Menit, 1 Jam, 2 Jam, 1 Hari, Tepat Waktu*).

---

## 5. FILE YANG DIMODIFIKASI

### Proyek Flutter Mobile (`tb-care-mobile`):
- **`lib/main/pasien/visit_schedule_page.dart`**:
  - Mengubah metode `_buildVisitItemCard()` dari layout vertikal `Column` menjadi `Row` horizontal (*space-between*).
  - Menghilangkan rendering nama Puskesmas dan teks alamat lokasi dari card.
  - Mempertahankan pengiriman parameter lokasi ke dialog `_showVisitDetailDialog()`.
- **`test/patient_visit_schedule_refinement_test.dart`**:
  - Memperbarui widget test untuk memverifikasi bahwa card hanya menampilkan tanggal dan waktu, Puskesmas tidak muncul di card, dan Puskesmas tetap muncul saat card diklik/tapped pada modal dialog.

---

## 6. KONFIRMASI INTEGRITAS BACKEND & KEAMANAN DATA

Sesuai instruksi:
- **TIDAK ADA perubahan pada Laravel Backend, Database, maupun API Schema:**
  - Database tabel `treatment_visits` dan relasi `puskesmas` tetap utuh.
  - Endpoint API (`/api/patient/visits`, `/api/treatment-visits`, `/api/treatments/{id}/visits`) tetap mengirimkan data faskes & lokasi lengkap.
- **Role Lain Tidak Terpengaruh:**
  - Fitur Role Kader, Role Petugas, dan Web Admin tetap berjalan normal tanpa regresi.

---

## 7. HASIL PENGUJIAN & VERIFIKASI

### A. Flutter Analyze
```bash
flutter analyze
```
**Hasil:**
```text
Analyzing tb-care-mobile...
No issues found! (ran in 7.0s)
```

### B. Flutter Widget & Unit Testing
```bash
flutter test test/patient_visit_schedule_refinement_test.dart
```
**Hasil:**
```text
00:00 +0: loading D:/My Projects/AplikasiTBCare/tb-care-mobile/test/patient_visit_schedule_refinement_test.dart
00:00 +0: Patient VisitSchedulePage Redesign & Card Refinement Tests Displays Jadwal Kontrol title, Pengingat Kontrol, and empty state when no visits
00:01 +1: Patient VisitSchedulePage Redesign & Card Refinement Tests Shows Jadwal Mendatang and Riwayat with horizontal date-time and NO Puskesmas on cards
00:01 +2: All tests passed!
```

### C. Regression Testing Fitur Terkait
```bash
flutter test test/home_page_widget_test.dart test/kader_reminders_and_rbac_test.dart
```
**Hasil:**
```text
All tests passed! (11 passed)
```

---

## 8. HASIL AKHIR TAMPILAN

```text
Jadwal Kontrol

[Pengingat Kontrol]
[Aktif (1 jam sebelumnya)]
[30 Menit] [1 Jam] [2 Jam] [1 Hari] [Tepat Waktu]

Jadwal Mendatang

┌──────────────────────────────────────────────┐
│ Senin, 09 November 2026          23:30 WIB  │
└──────────────────────────────────────────────┘

Riwayat Jadwal Kontrol

┌──────────────────────────────────────────────┐
│ Senin, 09 November 2026          21:30 WIB  │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ Minggu, 08 November 2026         19:30 WIB  │
└──────────────────────────────────────────────┘
```
