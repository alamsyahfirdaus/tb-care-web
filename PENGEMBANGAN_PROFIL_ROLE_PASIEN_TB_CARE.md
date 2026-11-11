# DOKUMENTASI PENGEMBANGAN DAN PENYEMPURNAAN PROFIL ROLE PASIEN TB CARE

Dokumen ini mendokumentasikan secara komprehensif seluruh analisis, arsitektur, implementasi, mitigasi risiko, dan hasil pengujian terkait pembaruan fitur **Profil dan Edit Profil khusus Role Pasien** pada aplikasi TB Care.

---

## 1. RINGKASAN EKSEKUTIF

Pembaruan ini bertujuan untuk meningkatkan kelengkapan data klinis dan demografis pasien serta menyederhanakan alur interaksi pengguna (*user experience*) pada proses pembaruan data profil:
1. **Penambahan 4 Data Fisik & Pekerjaan Pasien:** Menambahkan field *Tinggi Badan (cm)*, *Berat Badan (kg)*, *Golongan Darah (A, B, AB, O)*, dan *Pekerjaan* pada profil pasien.
2. **Arsitektur Formulir Tunggal (*Single-Save Button*):** Menyatukan seluruh input profil pasien—termasuk informasi pribadi, data fisik/pekerjaan, alamat domisili, dan pilihan Puskesmas rujukan—ke dalam satu formulir dengan satu tombol simpan utama di bagian bawah: **"Simpan Perubahan"**.
3. **Penyelarasan Pemilihan Puskesmas:** Menghilangkan tombol simpan mandiri/parsial pada section Puskesmas. Interaksi tombol "Ubah Puskesmas" hanya membuka komponen pencarian/pemilihan Puskesmas dan memperbarui status state lokal. Penyimpanan ke server hanya dieksekusi saat tombol utama formulir ditekan.
4. **Keamanan Transaksi & Isolasi Role:** Seluruh pembaruan di backend Laravel dibungkus dalam `DB::beginTransaction()` dan `DB::commit()` / `DB::rollBack()`. Perubahan ini dikondisikan secara ketat khusus untuk `role === 'patient'`, sehingga role Kader, Petugas Fasyankes, PJTB Puskesmas, dan Super Admin tetap terlindungi tanpa mengalami perubahan perilaku.

---

## 2. LATAR BELAKANG PERUBAHAN

Pada implementasi sebelumnya:
- Profil pasien belum menampilkan data antropometri (tinggi & berat badan), golongan darah, dan pekerjaan. Padahal data ini sangat krusial bagi tenaga kesehatan untuk evaluasi dosis obat OAT (Obat Anti Tuberkulosis) berdasarkan berat badan pasien serta riwayat klinis.
- Terdapat fragmentasi alur simpan pada formulir Edit Profil, di mana pemilihan fasilitas kesehatan (Puskesmas) memiliki alur tombol tersendiri yang membingungkan pasien dan berisiko menimbulkan *inconsistent state* jika pasien mengubah alamat dan Puskesmas secara bersamaan.
- Pengguna membutuhkan alur yang bersih, intuitif, dan tidak ambigu: isi semua data yang ingin diubah, lalu simpan sekali untuk seluruh data.

---

## 3. STRUKTUR DATABASE & SKEMA

Tabel `patients` pada database MySQL TB Care telah memiliki kolom `height`, `weight`, `blood_type`, dan `occupation` sejak migrasi awal, namun `height` dan `weight` sebelumnya bertipe integer standar yang kurang fleksibel untuk input angka desimal (misalnya berat badan 54.5 kg).

Telah dibuat migrasi Laravel baru untuk menyempurnakan tipe kolom dan indeks:
- **File Migrasi:** `database/migrations/2026_11_11_000001_update_patient_physical_attributes_in_patients_table.php`
- **Rincian Perubahan Skema:**
  ```php
  Schema::table('patients', function (Blueprint $table) {
      $table->decimal('height', 5, 2)->nullable()->change();
      $table->decimal('weight', 5, 2)->nullable()->change();
      $table->string('blood_type', 5)->nullable()->change();
      $table->string('occupation', 150)->nullable()->change();
  });
  ```
- **Karakteristik Skema:**
  - `height`: Desimal 5 digit total dengan 2 digit presisi di belakang koma (misal: `172.50` cm). Bersifat *nullable*.
  - `weight`: Desimal 5 digit total dengan 2 digit presisi di belakang koma (misal: `65.40` kg). Bersifat *nullable*.
  - `blood_type`: String/varchar panjang 5, nilai valid: `'A'`, `'B'`, `'AB'`, `'O'`. Bersifat *nullable*.
  - `occupation`: String/varchar panjang 150. Bersifat *nullable*.

Model `app/Models/Patient.php` ditambahkan konfigurasi type-casting:
```php
protected $casts = [
    // ...
    'height' => 'float',
    'weight' => 'float',
];
```

---

## 4. PERUBAHAN API & PAYLOAD

Endpoint API profil yang digunakan adalah:
- **`GET /api/v1/profile`**: Mengambil detail profil pengguna saat ini.
- **`PUT /api/v1/profile`**: Memperbarui profil pengguna saat ini.

### A. Endpoint `GET /api/v1/profile`
Pada method `ProfileController::show()`, controller mengecek relasi pasien dan mengembalikan field tambahan dalam object `data`:
```json
{
  "status": "success",
  "data": {
    "id": 12,
    "name": "Alamsyah Firdaus",
    "nik": "3278053107980010",
    "email": "pasien@example.com",
    "phone": "0897654321",
    "gender": "male",
    "birth_date": "1998-07-31",
    "birth_place": "Tasikmalaya",
    "role": "patient",
    "height": 170.0,
    "weight": 65.5,
    "blood_type": "O",
    "occupation": "Wiraswasta",
    "puskesmas_id": 3,
    "puskesmas": {
      "id": 3,
      "name": "Puskesmas Tamansari",
      "district": "Tamansari",
      "city": "Kota Tasikmalaya"
    }
  }
}
```

### B. Endpoint `PUT /api/v1/profile`
Menerima payload multipart / JSON yang mencakup data pengguna dan data fisik pasien secara atomik:
```json
{
  "name": "Alamsyah Firdaus",
  "phone": "0897654321",
  "gender": "male",
  "birth_date": "1998-07-31",
  "birth_place": "Tasikmalaya",
  "height": "170",
  "weight": "65.5",
  "blood_type": "O",
  "occupation": "Wiraswasta",
  "puskesmas_id": 3,
  "village_id": 14,
  "rt": "001",
  "rw": "001",
  "address": "Jl. Setiawangi No. 12"
}
```

---

## 5. ARSITEKTUR FORM EDIT PROFIL & SINGLE-SAVE FLOW

Pada sisi antarmuka Flutter Mobile (`lib/edit_profile.dart`), seluruh alur input telah dikonsolidasikan:
1. **Section 1: Informasi Pribadi**
   - Nama Lengkap (read-only / dapat diedit sesuai hak akses).
   - NIK (read-only untuk pasien).
   - Username (read-only).
   - Email (opsional).
   - Nomor Telepon (wajib).
   - Jenis Kelamin (dropdown).
   - Tanggal Lahir (date picker).
   - Tempat Lahir (text input).
   - **Tinggi Badan (cm)** (number input, contoh placeholder `165`).
   - **Berat Badan (kg)** (decimal input, contoh placeholder `60.5`).
   - **Golongan Darah** (custom styled dropdown selector: A, B, AB, O).
   - **Pekerjaan** (text input, contoh `Karyawan Swasta`, `Wiraswasta`).
2. **Section 2: Fasilitas Pelayanan Kesehatan (Puskesmas)**
   - Menampilkan card Puskesmas rujukan saat ini beserta tombol "Ubah Puskesmas".
   - **TIDAK ADA tombol "Simpan Puskesmas" terpisah.**
3. **Section 3: Alamat Domisili**
   - Kecamatan & Kelurahan/Desa.
   - RT & RW (wajib 3 digit angka).
   - Alamat Jalan / Detail Rumah.
4. **Footer Tunggal:**
   - Tombol utama **"Simpan Perubahan"** terletak di bagian paling bawah halaman, memicu validasi komprehensif seluruh form dan mengirim satu panggilan API ke backend.

---

## 6. MEKANISME & LOGIKA PEMILIHAN PUSKESMAS

Untuk menjamin prinsip *"Satu Formulir, Satu Tombol Simpan"*:
1. Saat user menekan tombol **"Ubah Puskesmas"**:
   - State `_showPuskesmasSearch` diubah menjadi `true`.
   - Card informasi Puskesmas berganti menjadi komponen *Live Search & Dropdown Picker*.
   - Tidak ada pengiriman data HTTP ke backend pada tahap ini.
2. Jika user menekan tombol **"Batal"**:
   - State `_showPuskesmasSearch` dikembalikan menjadi `false`.
   - ID Puskesmas dikembalikan ke nilai awal atau pilihan dibatalkan.
3. Saat user memilih salah satu Puskesmas dari hasil pencarian:
   - State lokal `_selectedPuskesmasId` dan `_selectedPuskesmasData` diperbarui.
   - Komponen dropdown ditutup dan menampilkan ringkasan Puskesmas yang baru dipilih pada card.
   - Data Puskesmas yang baru tersebut **TIDAK langsung dikirim ke backend**, melainkan disimpan dalam state lokal controller dan baru dikirim saat user menekan tombol **"Simpan Perubahan"** di bagian bawah halaman.

---

## 7. TAMPILAN HALAMAN PROFIL PASIEN

Pada halaman profil utama (`lib/profile.dart`), penambahan informasi dilakukan pada card **"Informasi Pribadi"** khusus jika `isPatient == true`:
- Urutan tampilan:
  1. Nama Lengkap
  2. NIK
  3. Jenis Kelamin
  4. Tempat Lahir
  5. Tanggal Lahir
  6. Nomor Telepon
  7. **Tinggi Badan** &rarr; Ditampilkan dengan format `[Nilai] cm` (misal: `170 cm`). Jika kosong, otomatis menampilkan `Belum diisi`.
  8. **Berat Badan** &rarr; Ditampilkan dengan format `[Nilai] kg` (misal: `65.5 kg` atau `60 kg`). Jika kosong, otomatis menampilkan `Belum diisi`.
  9. **Golongan Darah** &rarr; Ditampilkan dengan teks golongan darah (misal: `O`). Jika kosong, otomatis menampilkan `Belum diisi`.
  10. **Pekerjaan** &rarr; Ditampilkan nama pekerjaan (misal: `Wiraswasta`). Jika kosong, otomatis menampilkan `Belum diisi`.
- Konsistensi Ikon:
  - Tinggi Badan: `Icons.height_rounded`
  - Berat Badan: `Icons.monitor_weight_outlined`
  - Golongan Darah: `Icons.bloodtype_outlined`
  - Pekerjaan: `Icons.work_outline_rounded`

---

## 8. ATURAN VALIDASI FRONTEND & BACKEND

### A. Validasi Frontend (Flutter)
- **Tinggi Badan:** Input angka, dicek jika diisi harus berada dalam rentang wajar (misal $30 \le \text{tinggi} \le 250$ cm).
- **Berat Badan:** Input angka/desimal dengan titik/koma, dicek jika diisi harus berada dalam rentang wajar (misal $2 \le \text{berat} \le 300$ kg).
- **Golongan Darah:** Memilih salah satu dari enum statis: `['A', 'B', 'AB', 'O']`.
- **Pekerjaan:** Maksimal 150 karakter.

### B. Validasi Backend (Laravel `ProfileController`)
Aturan validasi didefinisikan secara tegas untuk role pasien:
```php
'height' => ['nullable', 'numeric', 'min:30', 'max:250'],
'weight' => ['nullable', 'numeric', 'min:2', 'max:300'],
'blood_type' => ['nullable', 'string', 'in:A,B,AB,O'],
'occupation' => ['nullable', 'string', 'max:150'],
```
Jika input melanggar batasan, backend mengembalikan status HTTP 422 (*Unprocessable Entity*) beserta rincian pesan kesalahan dalam format JSON terstandarisasi TB Care.

---

## 9. PENANGANAN KASUS KHUSUS (EDGE CASES)

1. **Nilai Kosong / Null:**
   - Baik di frontend maupun backend, keempat data baru bersifat opsional (*nullable*). Jika pasien belum mengisi data ini, sistem tidak akan mengalami crash atau menampilkan string `"null"`.
   - Di halaman Profil, fungsi helper `_formatHeight`, `_formatWeight`, `_formatBloodType`, dan `_formatOccupation` mengembalikan `null` jika data kosong, sehingga widget `_buildInfoItem` otomatis menampilkan placeholder abu-abu `"Belum diisi"`.
2. **Format Desimal:**
   - Pasien dapat menginput desimal menggunakan tanda titik (`.`) maupun koma (`,`). Helper `_formatNumberString` di mobile melakukan normalisasi agar desimal bulat (misal `60.00`) ditampilkan bersih sebagai `60`, sedangkan angka pecahan (misal `60.50`) ditampilkan sebagai `60.5`.
3. **Pencarian & Perubahan Puskesmas Tanpa Simpan:**
   - Jika pasien memilih Puskesmas baru namun kemudian menekan tombol kembali (*Back* / *Pop*) tanpa menekan "Simpan Perubahan", state backend tetap utuh dan data Puskesmas lama pasien tidak terganggu.

---

## 10. KEAMANAN & INTEGRITAS DATA

1. **Database Transaction Atomicity:**
   Pada `ProfileController::update()`, seluruh mutasi terhadap tabel `users` dan tabel `patients` dieksekusi di dalam satu blok transaksi database:
   ```php
   DB::beginTransaction();
   try {
       $user->save();
       if ($user->role === 'patient' && $user->patient) {
           $user->patient->save();
       }
       DB::commit();
   } catch (\Exception $e) {
       DB::rollBack();
       throw $e;
   }
   ```
   Hal ini menjamin bahwa kegagalan parsial (misal kesalahan format field pasien setelah update user) tidak akan meninggalkan kondisi inkonsistensi (*partial update*) di database.
2. **Role Scoping:**
   Field `height`, `weight`, `blood_type`, dan `occupation` hanya dibaca dan diupdate jika pengguna terautentikasi memiliki relasi `patient`. Pengguna dengan role non-pasien (`kader`, `officer`, `admin`) tidak terpengaruh oleh operasi field-field ini.

---

## 11. DAFTAR FILE YANG DIMODIFIKASI & FILE BARU

### A. Laravel Backend (`tb-care-web`)
1. `database/migrations/2026_11_11_000001_update_patient_physical_attributes_in_patients_table.php` *(File Baru)*:
   - Mengubah kolom `height` dan `weight` menjadi `decimal(5,2) nullable()`.
   - Menyelaraskan kolom `blood_type` dan `occupation` menjadi `nullable()`.
2. `app/Models/Patient.php` *(Dimodifikasi)*:
   - Menambahkan type cast `float` untuk `height` dan `weight`.
3. `app/Http/Controllers/Api/ProfileController.php` *(Dimodifikasi)*:
   - Menambahkan field `height`, `weight`, `blood_type`, dan `occupation` pada response method `show()`.
   - Menambahkan aturan validasi untuk keempat field pada method `update()`.
   - Membungkus proses penyimpanan `User` dan `Patient` ke dalam transaksi database (`DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()`).
   - Menyertakan data atribut baru pada response sukses `update()`.
4. `tests/Feature/PatientProfileRefinementTest.php` *(File Baru)*:
   - Automated Feature Test untuk pengujian API `GET` & `PUT /api/v1/profile`.

### B. Flutter Mobile (`tb-care-mobile`)
1. `lib/profile.dart` *(Dimodifikasi)*:
   - Menambahkan fungsi helper `_formatHeight`, `_formatWeight`, `_formatBloodType`, dan `_formatOccupation`.
   - Menambahkan 4 baris item info pada card Informasi Pribadi khusus role pasien.
2. `lib/edit_profile.dart` *(Dimodifikasi)*:
   - Menambahkan controller `_heightController`, `_weightController`, `_occupationController`, dan state `_bloodType`.
   - Mengisi nilai awal dari `widget.userData` pada `initState`.
   - Menambahkan input Tinggi Badan, Berat Badan, Dropdown Golongan Darah, dan Pekerjaan pada formulir Informasi Pribadi.
   - Mengirimkan field `height`, `weight`, `blood_type`, dan `occupation` dalam satu payload request multipart pada method `_saveProfile()`.
   - Memastikan tidak ada tombol simpan mandiri untuk Puskesmas.
3. `test/patient_profile_refinement_test.dart` *(File Baru)*:
   - Widget tests untuk verifikasi tampilan Profil, format satuan data, auto-fill Edit Profil, ketiadaan tombol simpan terpisah, dan perilaku tombol "Ubah Puskesmas".
4. `test/profile_page_card_test.dart` *(Dimodifikasi)*:
   - Menambahkan `await tester.ensureVisible(find.text('Edit Profil'))` untuk memastikan interaksi scroll stabil pada pengujian card profil berdimensi panjang.

---

## 12. HASIL PENGUJIAN BACKEND (LARAVEL)

Dijalankan menggunakan `php artisan test --filter=PatientProfileRefinementTest`:

```text
PASS  Tests\Feature\PatientProfileRefinementTest
✓ profile show returns new physical and occupational fields
✓ single request updates all fields including puskesmas and new fields
✓ validation rejects invalid blood type and out of range height weight

Tests:  3 passed (10 assertions)
Time:   0.95s
```

**Rincian Skenario:**
1. `profile show returns new physical and occupational fields`: Memverifikasi endpoint `GET /api/v1/profile` menyertakan key `height`, `weight`, `blood_type`, dan `occupation` dengan nilai yang sesuai.
2. `single request updates all fields including puskesmas and new fields`: Memverifikasi pengiriman satu request update atomic berhasil memperbarui data user, data alamat, ID Puskesmas, dan 4 data fisik pasien di database sekaligus.
3. `validation rejects invalid blood type and out of range height weight`: Memverifikasi validasi menolak golongan darah di luar A/B/AB/O (misal `'XYZ'`) dan tinggi/berat badan di luar batasan wajar, mengembalikan HTTP 422.

---

## 13. HASIL PENGUJIAN MOBILE (FLUTTER)

Dijalankan menggunakan `flutter test test/patient_profile_refinement_test.dart test/profile_page_card_test.dart test/edit_profile_test.dart`:

```text
00:08 +18: All tests passed!
```

**Rincian Skenario Pengujian:**
1. `Renders height, weight, blood type, and occupation with correct units and labels`: Memverifikasi halaman profil pasien merender label "Tinggi Badan", "Berat Badan", "Golongan Darah", dan "Pekerjaan" dengan satuan `"172.5 cm"`, `"64.5 kg"`, `"O"`, dan `"Wiraswasta"`.
2. `Displays Belum diisi when physical attributes are null or empty`: Memverifikasi jika atribut bernilai null atau string kosong, tampilan otomatis merender `"Belum diisi"`.
3. `Edit profile auto-fills existing physical attributes and has only ONE save button`: Memverifikasi data awal terisi ke dalam TextFormField controller, dropdown memilih opsi yang tepat, dan memastikan hanya terdapat tepat **1 tombol "Simpan Perubahan"** di seluruh pohon widget formulir.
4. `Tapping Ubah Puskesmas reveals picker without sending request, cancel restores view`: Memverifikasi menekan tombol "Ubah Puskesmas" hanya membuka komponen pencarian lokal tanpa mengirimkan request HTTP, dan tombol "Batal" mengembalikan tampilan card semula secara aman.
5. Seluruh suite regresi `edit_profile_test.dart` (14 test cases) dan `profile_page_card_test.dart` (1 test case) tetap berjalan 100% lulus.

---

## 14. VERIFIKASI KEAMANAN ROLE NON-PASIEN

- **Kader (kader):** Card Informasi Pribadi pada halaman Profil kader tidak memiliki informasi tinggi/berat badan/golongan darah, melainkan menampilkan data penugasan kader. Formulir edit profil kader tidak menyertakan input antropometri pasien.
- **Petugas Fasyankes (officer):** Tetap memiliki struktur profil petugas kesehatan dengan informasi NIP/SIP dan instansi Puskesmas bertugas.
- **PJTB Puskesmas & Admin:** Struktur profil dan hak akses manajemen akun pada web/mobile tetap berfungsi normal tanpa regresi.

---

## 15. KESIMPULAN & REKOMENDASI PENGEMBANGAN

1. **Hasil Akhir:** Fitur profil dan formulir edit profil untuk Role Pasien telah disempurnakan sesuai dengan seluruh persyaratan. Seluruh data fisik dan pekerjaan kini tersaji lengkap, terintegrasi dalam satu formulir yang bersih, dengan mekanisme pemilihan Puskesmas terpadu di bawah satu tombol simpan utama.
2. **Kualitas Kode:** Tidak ada error maupun warning pada analisa statis (`flutter analyze` dan PHP syntax lint). Seluruh unit dan feature tests lolos 100%.
3. **Rekomendasi Lanjutan:**
   - Pada fitur rekam medis atau konsultasi, data berat badan dan tinggi badan pasien ini dapat dimanfaatkan untuk kalkulasi otomatis Indeks Massa Tubuh (IMT / BMI) guna membantu dokter/petugas memantau status gizi pasien TB selama masa terapi.
