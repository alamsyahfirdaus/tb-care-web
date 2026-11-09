# LAPORAN PERBAIKAN MENYELURUH ROLE PASIEN (PATIENT) APLIKASI TB CARE

## 1. RINGKASAN EKSEKUTIF PERBAIKAN

Sesuai instruksi audit dan perbaikan menyeluruh pada Role Pasien aplikasi TB Care, seluruh perbaikan teknis telah diimplementasikan secara langsung pada kode sumber backend Laravel (`tb-care-web`) dan mobile Flutter (`tb-care-mobile`). Tidak ada perubahan yang bersifat kosmetik atau sekadar catatan; setiap celah keamanan, logika bisnis yang menyimpang, bug paginasi, dan inkonsistensi telah diperbaiki dan diverifikasi melalui *automated test suite*.

---

## 2. DETAIL PERBAIKAN PADA LARAVEL BACKEND (`tb-care-web`)

### 2.1. Penutupan Celah Otorisasi / Broken Access Control pada Pengobatan Klinis
- **File**: `app/Http/Controllers/Api/TreatmentController.php`
- **Permasalahan**: Sebelumnya hanya Kader yang dilarang (`if ($user->isKader())`). Karena Pasien memiliki izin akses terhadap rekam wilayah miliknya sendiri (`isAccessibleBy` bernilai `true`), Pasien dapat memanggil API untuk mengubah diagnosis, memodifikasi resep obat, mengubah status pengobatan menjadi 'Sembuh', atau bahkan menghapus data pengobatan klinis.
- **Perbaikan yang Diterapkan**:
  1. Pada method `store()`: Menambahkan proteksi `if ($user && ($user->isKader() || $user->isPatient()))` yang mengembalikan HTTP 403 Forbidden jika dipanggil oleh Pasien.
  2. Pada method `destroy()`: Menambahkan proteksi `if ($user && ($user->isKader() || $user->isPatient()))` yang mengembalikan HTTP 403 Forbidden.
  3. Pada method `updateTreatmentStatus()`: Menambahkan proteksi `if ($user && ($user->isKader() || $user->isPatient()))` yang mengembalikan HTTP 403 Forbidden.

### 2.2. Penegakan Otorisasi pada Jadwal Kunjungan / Kontrol Pasien
- **File**: `app/Http/Controllers/Api/TreatmentVisitController.php`
- **Permasalahan**: Pasien dapat memanggil endpoint pembuatan, pengubahan, pembatalan, atau penghapusan jadwal kunjungan faskes yang semestinya dikendalikan oleh petugas puskesmas.
- **Perbaikan yang Diterapkan**:
  1. Pada method `store()`: Menambahkan proteksi `if ($user && ($user->isKader() || $user->isPatient()))` (403 Forbidden).
  2. Pada method `destroy()`: Menambahkan proteksi `if ($user && ($user->isKader() || $user->isPatient()))` (403 Forbidden).
  3. Pada method `cancelVisit()`: Menambahkan proteksi `if ($user && ($user->isKader() || $user->isPatient()))` (403 Forbidden).

### 2.3. Pencegahan Penghapusan & Manipulasi Master Data Pasien
- **File**: `app/Http/Controllers/Api/PatientController.php`
- **Permasalahan**: Pasien dapat menghapus data rekam medis pasien miliknya via `DELETE /api/patients/{id}` atau mengubah master data pasien via `PatientController@store`.
- **Perbaikan yang Diterapkan**:
  1. Pada method `destroy()`: Menambahkan pengecekan `if ($user && ($user->isKader() || $user->isPatient()))` mengembalikan 403 Forbidden.
  2. Pada method `store()`: Menambahkan pengecekan `if ($user && ($user->isKader() || $user->isPatient()))` mengembalikan 403 Forbidden. Pembaruan data profil pasien dialihkan secara tepat ke `ProfileController@update`.

### 2.4. Perbaikan Alur Verifikasi Bukti Minum Obat (Medication Verification)
- **File**: `app/Http/Controllers/Api/TreatmentController.php`
- **Permasalahan**:
  1. Saat pasien mengunggah bukti minum obat pada `submitMedicationProof()`, field `'is_verified'` diisi `true` secara hardcoded, sehingga langsung terverifikasi tanpa perlu diperiksa oleh petugas kesehatan.
  2. Endpoint `verifyMedicationProof()` tidak memvalidasi role pasien, sehingga pasien dapat memverifikasi bukti fotonya sendiri.
- **Perbaikan yang Diterapkan**:
  1. Pada `submitMedicationProof()`: Mengubah nilai record awal menjadi `'is_verified' => false`, `'status' => 'Diminum'`, dan `'confirmation_source' => 'patient'`. Record berstatus "Menunggu Verifikasi" hingga diverifikasi petugas.
  2. Pada `verifyMedicationProof()`: Menegakkan aturan otorisasi ketat bahwa hanya Petugas PJTB Puskesmas dan Super Admin yang berwenang memverifikasi bukti minum obat (`if (!$user || $user->isKader() || $user->isPatient()) return 403`). Saat diverifikasi, sistem mengupdate `is_verified = true`, `status = 'Terverifikasi'`, `verified_by = $user->id`, dan `verified_at = now()`.

### 2.5. Perbaikan Paginasi & Infinite Scroll Duplication pada Riwayat Obat
- **File**: `app/Http/Controllers/Api/TreatmentController.php`
- **Permasalahan**: Method `medicationHistory()` mengembalikan seluruh array riwayat sekaligus dan mengabaikan parameter `page` dan `per_page`. Pada Flutter mobile, saat pengguna melakukan scroll ke bawah, aplikasi meminta `page=2` dan menerima daftar yang sama persis, lalu melakukan `_records.addAll(newRecords)` yang menduplikasi seluruh daftar riwayat secara berulang.
- **Perbaikan yang Diterapkan**:
  1. Menambahkan dukungan paginasi server berbasis `page` dan `per_page` menggunakan slicing koleksi `$unifiedHistory`.
  2. Menambahkan filter dinamis untuk status: `verified`, `pending`, `late`, dan `missed`.
  3. Mengembalikan metadata paginasi: `total`, `current_page`, `last_page`, `per_page`, dan `is_taken_today`. Jika request tidak menyertakan parameter `page` (seperti panggilan dari monitoring web), sistem tetap mengembalikan array penuh demi backward compatibility.

### 2.6. Perbaikan URL Gambar Aman dari Pemblokiran Cloudflare WAF (HTTP 403)
- **File**: `app/Http/Controllers/Api/PatientController.php`
- **Permasalahan**: Method `home()` mengembalikan foto profil dan foto bukti minum obat hari ini menggunakan `asset('images/' . $filename)`. Pada domain live, Cloudflare memblokir akses langsung ke path fisik web root images dengan status 403 Forbidden.
- **Perbaikan yang Diterapkan**:
  Mengubah URL aset menjadi endpoint API yang terproteksi: `url('/api/image/' . $filename)`.

### 2.7. Standardisasi Zona Waktu (Asia/Jakarta)
- **File**: `app/Http/Controllers/Api/PatientController.php`
- **Permasalahan**: Evaluasi tanggal pada `home()` menggunakan `Carbon::today()` (mengikuti UTC server), sedangkan pencatatan pengobatan menggunakan `Carbon::today('Asia/Jakarta')`.
- **Perbaikan yang Diterapkan**: Menyelaraskan seluruh evaluasi tanggal (`$now`, `$visitsQuery`, dan `$todayRecord`) menggunakan `Carbon::today('Asia/Jakarta')`.

### 2.8. Penambahan Endpoint Riwayat Skrining Pasien
- **File**: `app/Http/Controllers/Api/ScreeningController.php` & `routes/api.php`
- **Permasalahan**: Tidak tersedia endpoint API untuk mengambil daftar riwayat skrining mandiri atau skrining kontak serumah yang pernah dilakukan pasien.
- **Perbaikan yang Diterapkan**:
  1. Menambahkan method `history(Request $request)` pada `ScreeningController` yang mengambil riwayat skrining berelasi dengan `user_id` atau `patient_id` pengguna terautentikasi lengkap dengan pagination.
  2. Mendaftarkan rute `Route::get('/history', [ScreeningController::class, 'history'])` pada `routes/api.php` sebelum rute wildcard `/{id}`.

### 2.9. Penambahan Model Helper pada PatientTreatment
- **File**: `app/Models/PatientTreatment.php`
- **Perbaikan**: Menambahkan method `isTakenToday(): bool` untuk mempermudah pengecekan apakah pasien telah mencatat minum obat pada hari ini di zona waktu `Asia/Jakarta`.

---

## 3. DETAIL PERBAIKAN PADA FLUTTER MOBILE (`tb-care-mobile`)

### 3.1. Perbaikan Navigasi Notifikasi Sesuai Role Pengguna
- **File**: `lib/main/notifications/notification_list_page.dart`
- **Permasalahan**: Ketukan pada notifikasi minum obat yang memiliki `patient_id` selalu membuka `KaderPatientDetailPage`. Jika pasien yang menekan notifikasi tersebut, aplikasi membuka halaman kader dan memicu error otorisasi 403 dari backend.
- **Perbaikan yang Diterapkan**:
  Membaca `user_type_id` dari `SharedPreferences`. Jika `user_type_id == 2` (Pasien), sistem mengarahkan navigasi ke `TreatmentPage(patientId: patientId)`. Jika petugas/kader, sistem tetap mengarahkan ke `KaderPatientDetailPage`.

### 3.2. Penanganan Alarm Pengingat Setelah Pengobatan Selesai / Sembuh
- **File**: `lib/alarm_service.dart`
- **Permasalahan**: Pengecekan pada `handleTreatment()` hanya mematikan alarm jika statusnya persis sama dengan `'Selesai'`. Status pengobatan lain seperti `'Sembuh'`, `'Pengobatan Lengkap'`, `'Gagal'`, dll. tidak mematikan alarm, sehingga alarm pengingat terus berdering meskipun pasien telah sembuh.
- **Perbaikan yang Diterapkan**:
  1. Mengubah kondisi pengecekan menjadi `if (status != 'Berjalan') { await stopAllMedicationAlarm(); return; }`. Seluruh status non-aktif kini otomatis menghentikan alarm pengingat minum obat.
  2. Membungkus pemanggilan `AndroidAlarmManager.cancel()` dan `Workmanager().cancelByTag()` di dalam blok `try-catch` agar tidak memicu unhandled native exception jika platform channel belum terpasang.

### 3.3. Pencegahan Duplikasi Item pada Riwayat Obat & URL Resolver
- **File**: `lib/main/pasien/history.dart`
- **Permasalahan**: Saat pengguna scroll ke bawah pada riwayat minum obat, list UI menduplikasi record sebelumnya jika backend merespons record yang sama. Selain itu, pengambilan foto bukti menggunakan hardcoded string concat `${Connection.BASE_URL}/image/$fileName`.
- **Perbaikan yang Diterapkan**:
  1. Menambahkan deduplikasi berbasis key komposit (`${rec['id']}_${rec['date'] ?? rec['submitted_at']}`) saat menambahkan record baru (`_records.add`) pada pagination `_fetchRecordData()`.
  2. Menggunakan `Connection.resolveImageUrl(fileName)` pada `fetchImage()` agar sanitasi nama file dan URL Cloudflare-safe berjalan konsisten.

### 3.4. Sinkronisasi Status Minum Obat Hari Ini
- **File**: `lib/main/pasien/treatment.dart`
- **Permasalahan**: Pengecekan `_alreadyUploadedToday` hanya membaca elemen pertama dari history (`history.first`), yang dapat berupa slot dosis terlewat atau belum jatuh tempo, sehingga indikator kartu pengobatan tidak sinkron.
- **Perbaikan yang Diterapkan**:
  1. Memanfaatkan flag langsung `data['is_taken_today']` yang dikembalikan dari endpoint API riwayat obat.
  2. Memperbaiki logika `_alreadyUploadedToday` untuk menelusuri seluruh item pada hari ini yang memiliki `status_key == 'taken'` atau `status == 'Sudah Minum'/'Diminum'/'Terverifikasi'`.
