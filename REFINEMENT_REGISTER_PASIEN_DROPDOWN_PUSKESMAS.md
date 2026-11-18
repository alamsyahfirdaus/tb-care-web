# DOKUMENTASI REFINEMENT REGISTER ROLE PASIEN TB CARE
## Penyelarasan Alignment Indikator Langkah & Penambahan Informasi Wilayah Dropdown Puskesmas

Dokumen ini mencatat seluruh analisis, arsitektur, implementasi teknis, dan verifikasi pengujian terkait penyempurnaan UI/UX halaman **Buat Akun Pasien (Register Pasien)** pada aplikasi TB Care.

---

## 1. KONDISI AWAL TAMPILAN

Berdasarkan analisis source code aktual dan screenshot referensi:
1. **Indikator Langkah Pendaftaran:**
   - Komponen indikator langkah menggunakan margin asimetris (`fromLTRB(22, 12, 18, 8)`), tidak sejajar dengan padding card formulir di bawahnya (`24px`).
   - Teks "Data Pasien" dan "Alamat Pasien" dibungkus dalam `Flexible(child: Text(...))` tanpa pembagian flex yang proporsional di dalam `Row`. Akibatnya, pada perangkat fisik (misal Redmi Note 12 / layar 393px), langkah kedua ("Alamat Pasien") terdorong ke tengah-kanan dan menyisakan ruang kosong lebar di sisi kanan card.
2. **Dropdown Pencarian Puskesmas:**
   - Pilihan dropdown hanya menampilkan nama Puskesmas dalam satu baris (misal: "Bantar kalong", "Bojongasih", "Bojonggambir").
   - Belum terdapat informasi wilayah administratif (Kecamatan dan Kabupaten/Kota), sehingga pasien kesulitan membedakan fasilitas kesehatan yang memiliki kemiripan nama atau memastikan apakah Puskesmas tersebut berada di wilayah domisili mereka.

---

## 2. PERBAIKAN ALIGNMENT INDIKATOR LANGKAH

Indikator dua langkah telah disempurnakan dengan pembagian flex seimbang (*justified & balanced*):
- **Data Pasien (Langkah 1):** Berada di sisi kiri (`Expanded(flex: 3, child: Row(...))`) dengan perataan teks ke kiri.
- **Alamat Pasien (Langkah 2):** Berada di sisi kanan (`Expanded(flex: 3, child: Row(mainAxisAlignment: MainAxisAlignment.end, ...))`) dengan perataan elemen ke kanan.
- **Garis Penghubung:** Berada tepat di tengah (`Expanded(flex: 2, child: Container(...))`) dengan margin horizontal `8px` yang konsisten di kedua sisi.
- **Keselarasan Vertikal:** Seluruh baris anak menggunakan `crossAxisAlignment: CrossAxisAlignment.center` sehingga nomor lingkaran dan teks sejajar vertikal secara sempurna.
- **Pencegahan Teks Terpotong & Overflow:** Teks label menggunakan `Flexible` dengan `maxLines: 1` dan `overflow: TextOverflow.ellipsis` agar tetap proporsional dan tidak terjadi overflow pada berbagai resolusi layar (termasuk layar kecil 320x600 dan 360x640).
- **Margin Card Sejajar:** Margin card indikator diselaraskan menjadi `fromLTRB(24, 12, 24, 8)` dengan padding `horizontal: 14, vertical: 10`, sejajar tepat dengan card formulir di bawahnya.

### Ilustrasi Hasil Layout
```text
┌────────────────────────────────────────────────────────┐
│                                                        │
│  ① Data Pasien       ──────────────       ② Alamat Pasien│
│                                                        │
└────────────────────────────────────────────────────────┘
```

---

## 3. PERUBAHAN DROPDOWN PUSKESMAS

Dropdown pencarian fasilitas kesehatan Puskesmas (`buildPuskesmasAutocomplete`) telah ditingkatkan:
- **Tinggi Kontainer Pilihan:** `maxHeight` dinaikkan menjadi `280px` agar daftar pilihan dengan dua baris informasi dapat di-scroll dengan leluasa.
- **Item Container & Ripple Effect:** Menggunakan `InkWell` dengan padding vertikal `12px` dan horizontal `16px` serta pemisah `Divider(height: 1, thickness: 1, color: kBorderColor)`.
- **Status Terpilih:** Menampilkan aksen warna lembut (`kPrimaryColor.withValues(alpha: 0.05)`) ketika Puskesmas sedang terpilih.

---

## 4. PENAMBAHAN INFORMASI KECAMATAN DAN KABUPATEN/KOTA

Setiap opsi dropdown Puskesmas kini menyajikan hierarki visual dua baris:

### Format Standar:
- **Baris Pertama (Nama Puskesmas):**
  - Font Size: `14px`
  - Font Weight: `FontWeight.w600` (Semi-Bold)
  - Warna: `kTextColor` (atau `kPrimaryColor` jika terpilih)
  - `maxLines: 2, overflow: TextOverflow.ellipsis`
- **Baris Kedua (Kecamatan - Kabupaten/Kota):**
  - Font Size: `12px`
  - Font Weight: `FontWeight.normal`
  - Warna: `kSubtitleColor` (Abu-abu kebiruan `#64748B`)
  - Jarak Vertikal: `4px` di bawah nama Puskesmas
  - `maxLines: 2, overflow: TextOverflow.ellipsis`

### Contoh Tampilan:
```text
┌──────────────────────────────────────┐
│ Puskesmas Bantarkalong               │
│ Bantarkalong - Kabupaten Tasikmalaya │
├──────────────────────────────────────┤
│ Puskesmas Bojongasih                 │
│ Bojongasih - Kabupaten Tasikmalaya   │
├──────────────────────────────────────┤
│ Puskesmas Kawalu                     │
│ Kawalu - Kota Tasikmalaya            │
└──────────────────────────────────────┘
```

### Kemampuan Pencarian Multi-Parameter:
Pengguna dapat mencari tidak hanya berdasarkan nama Puskesmas, tetapi juga dapat mengetik nama Kecamatan maupun Kabupaten/Kota (contoh: mengetik `"Tasik"` atau `"Kawalu"` akan otomatis memfilter Puskesmas terkait).

### Integritas Pilihan:
- Teks yang masuk ke input field tetap hanya **Nama Puskesmas**.
- Nilai yang dikirimkan ke state dan API registrasi tetap murni **ID Puskesmas** (`selectedPuskesmasId`).

---

## 5. SUMBER DATA WILAYAH

Data wilayah diambil langsung dari data master relasional database melalui API:
- Endpoint: `GET /api/v1/puskesmas`
- Relasi Eloquent: `Puskesmas::with('subdistrict.district.province')`
- Output JSON API:
  - `id`: ID fasilitas kesehatan
  - `name`: Nama Puskesmas master
  - `subdistrict_id`: ID Kecamatan
  - `subdistrict_name`: Nama Kecamatan
  - `district_id`: ID Kabupaten/Kota
  - `district_name`: Nama Kabupaten/Kota
  - `location`: Gabungan `"Nama Kecamatan - Nama Kabupaten/Kota"`
  - `address`: Alamat spesifik fasyankes

Tidak ada nama wilayah yang di-hardcode.

---

## 6. FILE FLUTTER YANG DIMODIFIKASI & DITAMBAHKAN

1. [**`tb-care-mobile/lib/register.dart`**](file:///D:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/register.dart):
   - Refactor `_buildStepIndicator()` menjadi model flex `3:2:3` yang seimbang dan rata kiri-kanan.
   - Refactor `buildPuskesmasAutocomplete()` untuk mendukung opsi dua baris (Nama & Lokasi) dan pencarian multi-keyword wilayah.
2. [**`tb-care-mobile/test/register_patient_test.dart`**](file:///D:/My%20Projects/AplikasiTBCare/tb-care-mobile/test/register_patient_test.dart):
   - Penyesuaian pemisah teks subtitle territory (` - `) dan verifikasi anti-overflow pada resolusi 360x640.
3. [**`tb-care-mobile/test/register_patient_refinement_test.dart`**](file:///D:/My%20Projects/AplikasiTBCare/tb-care-mobile/test/register_patient_refinement_test.dart) *(File Baru)*:
   - Pengujian widget dedicated untuk layout indikator seimbang, dropdown 2-baris, pencarian multi-wilayah, dan validasi transisi langkah.

---

## 7. FILE LARAVEL YANG DIMODIFIKASI

1. [**`tb-care-web/app/Http/Controllers/Api/PuskesmasController.php`**](file:///D:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Controllers/Api/PuskesmasController.php):
   - Menambahkan field `subdistrict_id` dan `district_id` pada respons API `GET /api/puskesmas` untuk melengkapi payload data master wilayah fasyankes.
2. [**`tb-care-web/tests/Feature/PatientRegistrationApiTest.php`**](file:///D:/My%20Projects/AplikasiTBCare/tb-care-web/tests/Feature/PatientRegistrationApiTest.php):
   - Menambahkan unit test `test_puskesmas_endpoint_provides_territory_and_location_data()` untuk memverifikasi struktur payload Puskesmas lengkap.

---

## 8. HASIL FLUTTER ANALYZE

Dijalankan melalui `dart analyze lib/register.dart test/register_patient_test.dart test/register_patient_refinement_test.dart`:

```text
Analyzing register.dart, register_patient_test.dart, register_patient_refinement_test.dart...
No issues found!
```

---

## 9. HASIL FLUTTER TEST

Dijalankan melalui `flutter test test/register_patient_test.dart test/register_patient_refinement_test.dart`:

```text
00:11 +11: All tests passed!

Rincian:
- register_patient_refinement_test.dart:
  ✓ Indikator langkah memiliki posisi seimbang, rata kiri & kanan
  ✓ Indikator responsif dan tidak overflow pada layar kecil (320x600 & 360x640)
  ✓ Menampilkan Nama Puskesmas (baris 1) dan Kecamatan - Kabupaten/Kota (baris 2)
  ✓ Pencarian Puskesmas berfungsi berdasarkan nama maupun wilayah
  ✓ Pemilihan Puskesmas memungkinkan transisi ke Langkah 2 (Alamat Pasien)
- register_patient_test.dart:
  ✓ RegisterPage renders Step 1 with patient fields and no officer tabs
  ✓ Validation errors for invalid NIK and phone
  ✓ Step 2 displays inverted territory flow: Desa/Kelurahan searchable, parent fields readonly
  ✓ Selecting Desa/Kelurahan automatically fills Kecamatan, Kabupaten/Kota, and Provinsi
  ✓ Step 2 button layout has no overflow on small screen (360x640)
  ✓ RT and RW fields validate required, numbers-only, and max 3 digits
```

---

## 10. HASIL PENGUJIAN BACKEND (LARAVEL)

Dijalankan melalui `php artisan test --filter=PatientRegistration`:

```text
PASS  Tests\Feature\PatientRegistrationApiTest
✓ cascading territory endpoints
✓ patient registration validation
✓ patient registration rt rw must be numeric
✓ patient registration full flow
✓ puskesmas endpoint provides territory and location data

PASS  Tests\Feature\PatientRegistrationTreatmentTest
✓ registration automatically creates treatment
✓ treatment field values strictly match specifications
✓ start date follows past input date
✓ transaction rolls back user and patient when treatment creation fails
✓ one registration creates exactly one treatment
✓ end to end registration treatment and login flow

Tests:  11 passed (45 assertions)
Time:   2.01s
```

---

## 11. KESIMPULAN

Penyempurnaan UI/UX halaman Register Pasien telah selesai secara tuntas:
1. Indikator langkah pendaftaran kini proporsional, rata kiri dan kanan dengan pembagian ruang yang seimbang dan garis penghubung presisi di tengah.
2. Dropdown pencarian Puskesmas menyajikan informasi hierarkis 2-baris (Nama Puskesmas & Lokasi Kecamatan - Kabupaten/Kota) yang memudahkan pasien mengenali fasyankes rujukan mereka.
3. Seluruh alur validasi, pemilihan ID Puskesmas, dan transaksi pendaftaran pasien tetap berjalan tanpa perubahan logika bisnis maupun gangguan pada role lain.
