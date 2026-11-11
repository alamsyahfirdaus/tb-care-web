# REFINEMENT TABBAR PENGOBATAN ROLE PASIEN (TB CARE)

## 1. Kondisi Sebelum Perubahan

Sebelum penyempurnaan:
- TabBar pada halaman **Pengobatan** Role Pasien ditempatkan langsung di properti `bottom` tanpa pembungkus permukaan putih (`Material(color: Colors.white)`), sehingga pada beberapa perangkat/tema memunculkan latar belakang abu-abu yang kurang menyatu dengan desain halaman dan card pengobatan.
- Warna teks tab belum mengadopsi skema warna biru primer aktif pada latar putih dengan tab tidak aktif berwarna abu-abu netral.
- Garis pembatas bawah bawaan (Material 3 divider) berpotensi memunculkan garis abu-abu tipis di bawah area tab.

---

## 2. Perubahan Warna Background TabBar

- **Latar Belakang Putih Penuh:** Seluruh area TabBar dibungkus menggunakan `Material(color: Colors.white, ...)` di dalam widget `PreferredSize(preferredSize: const Size.fromHeight(48), ...)`:
  ```dart
  bottom: PreferredSize(
    preferredSize: const Size.fromHeight(48),
    child: Material(
      color: Colors.white,
      child: TabBar(...),
    ),
  ),
  ```
- **Menghilangkan Warna Abu-abu:** Area tab kini berwarna putih bersih (`#FFFFFF`), menyatu dengan card dan desain halaman utama TB Care.
- **Warna App Bar Utama Terjaga:** Bagian atas AppBar tetap mempertahankan warna biru utama TB Care (`AppColors.primary`), judul "Pengobatan", serta tombol aksi notifikasi dan percakapan.

---

## 3. Perubahan Warna Teks Aktif dan Tidak Aktif

| Parameter | Sebelum | Sesudah |
|---|---|---|
| **Warna Teks Tab Aktif** | Putih pekat (`#FFFFFF`) | Biru Utama TB Care (`AppColors.primary` / `#3360B4`) |
| **FontWeight Tab Aktif** | Bold | Semi-Bold (`FontWeight.w600`) |
| **Warna Teks Tab Tidak Aktif** | Putih transparan (`0.7`) | Abu-abu kebiruan (`const Color(0xFF64748B)`) |
| **FontWeight Tab Tidak Aktif** | Semi-Bold | Normal (`FontWeight.normal`) |
| **Font Family** | Plus Jakarta Sans (13px) | Plus Jakarta Sans (13px) |

---

## 4. Perubahan Indikator Tab Terpilih

- **Warna Garis Indikator:** Menggunakan warna biru utama TB Care (`AppColors.primary`, `#3360B4`), sama persis dengan warna App Bar.
- **Ketebalan Indikator:** 3 dp (`indicatorWeight: 3`).
- **Ukuran Indikator:** Proporsional selebar tab (`indicatorSize: TabBarIndicatorSize.tab`).
- **Divider Color:** Ditetapkan ke `Colors.transparent` (`dividerColor: Colors.transparent`) untuk menghilangkan garis pembatas abu-abu bawaan.
- **Animasi:** Mengikuti perpindahan controller `_tabController` secara mulus saat tab ditekan atau digeser (*swipe*).

---

## 5. File Source Code yang Dimodifikasi

1. **`tb-care-mobile/lib/main/pasien/treatment.dart`**:
   - Membungkus `TabBar` dengan `PreferredSize` dan `Material(color: Colors.white)`.
   - Mengonfigurasi `indicatorColor`, `labelColor`, `unselectedLabelColor`, `labelStyle`, `unselectedLabelStyle`, `indicatorWeight`, `indicatorSize`, dan `dividerColor`.
2. **`tb-care-mobile/test/patient_treatment_tab_and_medication_test.dart`**:
   - Menambahkan pengujian spesifik untuk memastikan `TabBar` memiliki `indicatorColor == AppColors.primary`, `labelColor == AppColors.primary`, `unselectedLabelColor == const Color(0xFF64748B)`, `dividerColor == Colors.transparent`, dan background `Material` berwarna putih.

---

## 6. Hasil Pengujian dan Verifikasi

### A. Widget Test (`flutter test`)
```bash
flutter test test/patient_treatment_tab_and_medication_test.dart
```
**Output:**
```text
00:01 +3: All tests passed!
✓ TreatmentPage contains 2 Tabs: Informasi Pengobatan and Riwayat Minum Obat
✓ TabBar styling: white background, AppColors.primary active text & indicator, slate grey inactive text
✓ TreatmentPage Tab 1 does not contain Riwayat Minum Obat action card
✓ PatientMedication model parses correctly with dosage instructions
```

### B. Analisis Statis (`flutter analyze`)
```bash
flutter analyze
```
**Output:**
```text
Analyzing tb-care-mobile...
No issues found!
```

---

## 7. Checklist Verifikasi Batasan Sistem

- [x] Background kedua tab berwarna putih bersih (`Colors.white`).
- [x] Tab aktif berwarna biru sama dengan App Bar (`AppColors.primary`).
- [x] Teks tab aktif menggunakan font semi-bold (`FontWeight.w600`).
- [x] Indikator bawah tab aktif berwarna biru setebal 3 dp.
- [x] Tab tidak aktif berwarna abu-abu kebiruan (`#64748B`).
- [x] Garis divider abu-abu bawaan dihilangkan (`dividerColor: Colors.transparent`).
- [x] Navigasi dan perpindahan tab tetap berfungsi normal.
- [x] Konten Tab 1 (Informasi Pengobatan) dan Tab 2 (Riwayat Minum Obat) tidak berubah.
- [x] Tidak ada overflow pada layar.
- [x] Role Kader, Petugas, dan Admin tidak terdampak.
