# DOKUMENTASI REFINEMENT DROPDOWN DESA/KELURAHAN REGISTER PASIEN TB CARE
## Penyederhanaan Visual Pilihan Desa/Kelurahan Langkah 2 (Alamat Pasien)

Dokumen ini mencatat analisis, detail perubahan teknis, arsitektur antarmuka, dan hasil verifikasi pengujian terkait penyempurnaan UI dropdown Desa/Kelurahan pada halaman **Buat Akun Pasien – Langkah 2: Alamat Pasien** aplikasi TB Care.

---

## 1. KONDISI AWAL TAMPILAN

Berdasarkan screenshot referensi aktual dan inspeksi source code:
- Pada langkah kedua formulir registrasi pasien (**Alamat Pasien**), komponen pencarian wilayah menggunakan dropdown autocomplete `buildSearchableVillageAutocomplete`.
- Setiap item pilihan Desa/Kelurahan sebelumnya menampilkan ikon lokasi (`Icons.location_on_outlined`) di dalam kotak rounded (`Container`) dengan latar belakang biru muda di sisi kiri teks.
- Tampilan tersebut menyebabkan:
  1. Baris item terasa padat dan menyita ruang horizontal (lebar layar) yang sebenarnya dapat dimaksimalkan untuk nama desa dan wilayah administratif yang panjang.
  2. Inkonsistensi gaya visual dengan dropdown Puskesmas pada Langkah 1 yang sudah dibersihkan dari ikon dekoratif dan mengadopsi tampilan minimalis dua baris rata kiri.

---

## 2. PERUBAHAN UI ITEM DROPDOWN DESA/KELURAHAN

Sesuai arahan perbaikan, elemen ikon lokasi pada item dropdown Desa/Kelurahan telah dihilangkan secara menyeluruh:

### A. Penghapusan Ikon Lokasi Leading
- Elemen `leading: Container(...)` yang membungkus `Icons.location_on_outlined` dihapus dari `ListTile` pada `buildSearchableVillageAutocomplete`.
- Tidak ada ruang kosong atau sisa indentasi yang tertinggal di sisi kiri item.

### B. Perataan Teks Rata Kiri (*Flush Left Alignment*)
- **Nama Desa/Kelurahan (Baris 1):** Rata kiri dengan padding `horizontal: 16px` dari tepi container dropdown. Menggunakan font semi-bold (`FontWeight.w600` atau `bold` jika terpilih) berukuran `14px`.
- **Keterangan Wilayah (Baris 2):** Rata kiri tepat sejajar dengan nama desa di atasnya. Menampilkan format `Kecamatan - Kabupaten/Kota` dengan font `12px` berwarna abu-abu netral (`kSubtitleColor`).
- Jarak antar item dipisahkan oleh divider garis tipis (`Divider(height: 1, color: kBorderColor)`).

### C. Status Terpilih (Selected State)
- Jika suatu item Desa/Kelurahan sedang dipilih:
  - Warna teks nama desa berubah menjadi biru utama (`kPrimaryColor`).
  - Latar belakang item diberi warna highlight lembut (`kPrimaryColor.withOpacity(0.04)`).
  - Tanda centang hijau/biru (`Icons.check_circle_rounded`) tetap ditampilkan pada sisi kanan (*trailing*) dengan ukuran `20px`.

### Ilustrasi Perbandingan Layout:

#### Sebelum Perubahan:
```text
┌────────────────────────────────────────────────────────┐
│ [📍] Argasari                                          │
│      Cihideung - Kota Tasikmalaya                      │
├────────────────────────────────────────────────────────┤
│ [📍] Awipari                                           │
│      Cibeureum - Kota Tasikmalaya                      │
├────────────────────────────────────────────────────────┤
│ [📍] Bantarsari                                        │
│      Bungursari - Kota Tasikmalaya                     │
└────────────────────────────────────────────────────────┘
```

#### Sesudah Perubahan (Minimalis, Rata Kiri & Bersih):
```text
┌────────────────────────────────────────────────────────┐
│ Argasari                                               │
│ Cihideung - Kota Tasikmalaya                           │
├────────────────────────────────────────────────────────┤
│ Awipari                                                │
│ Cibeureum - Kota Tasikmalaya                           │
├────────────────────────────────────────────────────────┤
│ Bantarsari                                             │
│ Bungursari - Kota Tasikmalaya                          │
└────────────────────────────────────────────────────────┘
```

---

## 3. KOMPONEN YANG TIDAK BERUBAH

Seluruh fitur inti, keamanan formulir, dan navigasi tetap dipertahankan secara utuh tanpa modifikasi yang tidak diinginkan:
1. **Field Input Pencarian:**
   - Ikon rumah/gedung (`Icons.home_work_outlined`) pada sisi kiri (*prefixIcon*) input field tetap dipertahankan.
   - Ikon panah dropdown (`Icons.arrow_drop_down_rounded`) dan tombol hapus pilihan (`Icons.close_rounded`) pada sisi kanan (*suffixIcon*) tetap berfungsi normal.
2. **Indikator Langkah (Step Indicator):**
   - Indikator langkah pendaftaran (Step 1: Data Pasien dan Step 2: Alamat Pasien) dengan pembagian flex seimbang 3:2:3 tetap utuh.
3. **Logika Autocomplete & Filter Wilayah:**
   - Algoritma pencarian teks real-time yang memfilter berdasarkan nama desa, kecamatan, kabupaten/kota, atau string alamat lengkap tetap aktif.
4. **Otomatisasi Pengisian Wilayah Induk:**
   - Pemilihan Desa/Kelurahan tetap secara otomatis mengisi ketiga field read-only di bawahnya: **Kecamatan**, **Kabupaten/Kota**, dan **Provinsi** lengkap dengan ikon gembok (`Icons.lock_outline_rounded`).
5. **Integritas Data & Backend:**
   - ID desa (`selectedVillageId`) tetap disimpan dan dikirimkan secara tepat ke API Laravel backend (`POST /api/v1/auth/register-pasien`).
   - Tidak ada perubahan pada skema database, controller API, maupun validasi form.
6. **Konsistensi Antar Halaman:**
   - Karena `buildSearchableVillageAutocomplete` di `lib/register.dart` merupakan komponen reusable yang juga digunakan oleh `lib/edit_profile.dart` (Edit Profil Pasien), halaman Edit Profil otomatis ikut mendapatkan tampilan bersih yang selaras tanpa duplikasi kode.

---

## 4. PERUBAHAN KODE SUMBER

### File: `tb-care-mobile/lib/register.dart`
Lokasi: Fungsi `buildSearchableVillageAutocomplete` (Baris 1559–1610)

```dart
// Sebelum:
return ListTile(
  contentPadding: const EdgeInsets.symmetric(
    horizontal: 16,
    vertical: 8,
  ),
  leading: Container(
    padding: const EdgeInsets.all(8),
    decoration: BoxDecoration(
      color: kPrimaryColor.withOpacity(0.08),
      borderRadius: BorderRadius.circular(10),
    ),
    child: const Icon(
      Icons.location_on_outlined,
      size: 18,
      color: kPrimaryColor,
    ),
  ),
  title: Text(item["name"]?.toString() ?? "", ...),
  subtitle: parentText.isNotEmpty ? ... : null,
  trailing: isSelected ? const Icon(Icons.check_circle_rounded, ...) : null,
  ...
);

// Sesudah (Ikon leading dihilangkan, teks rata kiri sejajar):
return ListTile(
  contentPadding: const EdgeInsets.symmetric(
    horizontal: 16,
    vertical: 8,
  ),
  title: Text(
    item["name"]?.toString() ?? "",
    style: TextStyle(
      fontSize: 14,
      color: isSelected ? kPrimaryColor : kTextColor,
      fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
    ),
  ),
  subtitle:
      parentText.isNotEmpty
          ? Padding(
            padding: const EdgeInsets.only(top: 2.0),
            child: Text(
              parentText,
              style: const TextStyle(
                fontSize: 12,
                color: kSubtitleColor,
              ),
            ),
          )
          : null,
  trailing:
      isSelected
          ? const Icon(
            Icons.check_circle_rounded,
            color: kPrimaryColor,
            size: 20,
          )
          : null,
  tileColor:
      isSelected
          // ignore: deprecated_member_use
          ? kPrimaryColor.withOpacity(0.04)
          : null,
  onTap: () {
    onSelectedOption(item);
  },
);
```

---

## 5. HASIL VERIFIKASI DAN PENGUJIAN

Pengujian dilakukan secara menyeluruh menggunakan Flutter Test Suite dan Static Analyzer:

### A. Pengujian Widget (`register_patient_refinement_test.dart`)
Menambahkan test group khusus: `Perubahan 3 — Dropdown Desa/Kelurahan Tanpa Ikon Lokasi (Langkah 2: Alamat Pasien)`:
1. **Verifikasi Tidak Ada Ikon Lokasi:**
   - Membuka dropdown Desa/Kelurahan dengan opsi Argasari, Awipari, dan Bantarsari.
   - Memastikan `find.byIcon(Icons.location_on_outlined)` bernilai **0 (findsNothing)**.
   - Memastikan prefix icon `Icons.home_work_outlined` tetap ada pada input field.
2. **Verifikasi Perataan Teks & Pemilihan:**
   - Memilih salah satu opsi (Argasari) dan memverifikasi field input terisi `"Argasari"`.
   - Memverifikasi parent fields (Kecamatan: Cihideung, Kab: Kota Tasikmalaya, Prov: Jawa Barat) terisi otomatis secara akurat.
3. **Verifikasi Indikator Centang & Highlight:**
   - Memastikan opsi yang sedang terpilih menampilkan ikon centang `Icons.check_circle_rounded`.

### B. Hasil Eksekusi Test:
```bash
$ flutter test test/register_patient_refinement_test.dart
00:05 +7: All tests passed!

$ flutter test test/register_patient_test.dart
00:09 +6: All tests passed!
```
**Total: 13/13 unit & widget tests passed (100% green).**

### C. Analisis Statis Kode (`flutter analyze`):
```bash
$ flutter analyze
Analyzing tb-care-mobile...
No issues found! (ran in 51.4s)
```
**Hasil:** 0 error, 0 warning, 0 lint issue.

---

## 6. KESIMPULAN

Penyempurnaan UI dropdown Desa/Kelurahan pada halaman registrasi pasien TB Care telah selesai dilaksanakan dengan hasil yang bersih, rapi, dan konsisten:
- Tampilan daftar opsi kini minimalis tanpa ikon dekoratif yang memadati ruang.
- Teks nama desa dan hierarki wilayah rata kiri sejajar.
- Konsistensi visual tercapai antara dropdown Puskesmas (Langkah 1) dan dropdown Desa/Kelurahan (Langkah 2).
- Seluruh fungsionalitas pencarian wilayah, pengisian otomatis, dan validasi registrasi pasien tetap berjalan optimal.
