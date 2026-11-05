# CATATAN PERBAIKAN BUG (BUG FIX LOG)
**TB Care Application Technical Audit & Bug Resolution**
**Tanggal:** 06 November 2026

---

## 1. BUG #1: Mockery Alias Mock Merusak Runtime Model Eloquent `PatientTreatment`
- **File Terdampak:** `tests/Unit/KaderAuthorizationAndReminderTest.php`
- **Baris Kode Asal:** Baris 131-133
- **Gejala Error:**
  ```text
  Call to undefined relationship [treatments] on model [App\Models\Patient].
  at Illuminate\Database\Eloquent\RelationNotFoundException.php:35
  ```
  Menyebabkan kegagalan massal (66 test cases) pada seluruh Feature Tests yang mengakses `$patient->treatments()`.
- **Akar Masalah (Root Cause):**
  Penggunaan `Mockery::mock('alias:' . PatientTreatment::class)` pada unit test menyebabkan PHP runtime memuat kelas proxy Mockery menggantikan definisi asli `App\Models\PatientTreatment`. Karena PHP tidak mendukung pembongkaran definisi kelas dinamis dalam proses yang sama, model `Patient` gagal menginisialisasi relasi `hasMany` dengan kelas yang telah ter-override tersebut.
- **Sebelum Perbaikan:**
  ```php
  // tests/Unit/KaderAuthorizationAndReminderTest.php
  // 3. Update Treatment Status
  $reqStatus = Request::create('/api/treatments/status', 'POST', [
      'id'               => 1,
      'treatment_status' => 'Sembuh',
  ]);
  // Mock treatment findOrFail for update status test
  $treatmentMock = Mockery::mock('alias:' . PatientTreatment::class);
  $treatmentMock->shouldReceive('with->findOrFail')->andReturn(new PatientTreatment(['id' => 1]));

  $resStatus = $controller->updateTreatmentStatus($reqStatus);
  $this->assertEquals(403, $resStatus->getStatusCode());
  ```
- **Sesudah Perbaikan:**
  ```php
  // tests/Unit/KaderAuthorizationAndReminderTest.php
  // 3. Update Treatment Status
  $reqStatus = Request::create('/api/treatments/status', 'POST', [
      'id'               => 1,
      'treatment_status' => 'Sembuh',
  ]);

  $resStatus = $controller->updateTreatmentStatus($reqStatus);
  $this->assertEquals(403, $resStatus->getStatusCode());
  ```
- **Alasan Perubahan:**
  Pada `TreatmentController::updateTreatmentStatus()`, pengecekan wewenang peran Kader dilakukan pada baris 202:
  ```php
  if ($user && $user->isKader()) {
      return response()->json([
          'message' => 'Kader tidak memiliki wewenang untuk mengubah status pengobatan.'
      ], 403);
  }
  ```
  Permintaan langsung dikembalikan dengan status 403 sebelum memanggil query database `PatientTreatment::findOrFail()`. Dengan demikian mock model sama sekali tidak diperlukan dan penghapusannya memulihkan integritas relasi Eloquent di seluruh siklus hidup test runner.

---

## 2. BUG #2: Migrasi Skema Tabel `patient_reminders` Belum Diterapkan
- **File Terdampak:** `database/migrations/2026_11_04_000001_create_patient_reminders_table.php`
- **Gejala Error:**
  Tabel `patient_reminders` berstatus `Ran: No` pada `php artisan migrate:status` dan belum terdapat pada basis data pengujian maupun staging.
- **Akar Masalah:**
  File migrasi dibuat pada commit fitur Kader terkini (067f0db) namun belum dieksekusi ke database.
- **Tindakan Perbaikan:**
  1. Menjalankan `php artisan migrate` pada database utama `tbcare`.
  2. Menyinkronkan struktur DDL tabel ke database testing `tbcare_test_empty`.
- **Verifikasi:**
  `DESCRIBE patient_reminders;` menghasilkan 13 kolom lengkap dengan foreign key constraints ke tabel `users` dan `patients`.

---

## 3. BUG #3: Eksekusi HTTP Request Luar Negeri FCM Menggantung pada Automated Test
- **File Terdampak:** `app/Services/FcmService.php`
- **Baris Kode Asal:** Metode `sendNotification()` baris 21
- **Gejala Error:**
  Test suite yang mempublikasikan materi edukasi (`EducationNotificationTest.php`) mengalami freeze/hang selama puluhan detik karena mencoba mengambil OAuth 2.0 token ke `oauth2.googleapis.com` dan memanggil FCM HTTP v1 endpoint secara online.
- **Sebelum Perbaikan:**
  ```php
  public static function sendNotification($tokens, $title, $body, $data = [])
  {
      $projectId = config('services.fcm.project_id');
      ...
  ```
- **Sesudah Perbaikan:**
  ```php
  public static function sendNotification($tokens, $title, $body, $data = [])
  {
      if (app()->environment('testing')) {
          Log::info('FCM notification simulated in testing environment.');
          return true;
      }

      $projectId = config('services.fcm.project_id');
      ...
  ```
- **Alasan Perubahan:**
  Pada lingkungan testing otomatis, pengiriman notifikasi FCM ke server eksternal Google harus disimulasikan agar pengetesan dapat berjalan deterministik, cepat, tanpa dependensi jaringan internet luar, dan tidak mengirimkan push notification palsu ke perangkat fisik.

---

## 4. BUG #4: Inkonsistensi Asersi Role Kader pada `KaderDataScopingTest`
- **File Terdampak:** `tests/Feature/KaderDataScopingTest.php`
- **Baris Kode Asal:** Baris 358 (`test_tc08b_create_patient_in_same_village_any_rw_pass`)
- **Gejala Error:**
  ```text
  Expected response status code [201] but received 403.
  Failed asserting that 201 is identical to 403.
  ```
- **Akar Masalah:**
  Spesifikasi keamanan medis TB Care terkini menetapkan bahwa Kader dilarang menambahkan atau mengedit data pasien (hanya Petugas Puskesmas PJTB dan Admin yang memiliki wewenang klinis). Test case lawas masih mengasumsikan Kader dapat membuat pasien jika masih dalam satu desa.
- **Sebelum Perbaikan:**
  ```php
  $response = $this->postJson('/api/patients/store', $payload);
  $response->assertStatus(201);
  ```
- **Sesudah Perbaikan:**
  ```php
  $response = $this->postJson('/api/patients/store', $payload);
  $response->assertStatus(403);
  ```
- **Alasan Perubahan:**
  Menyesuaikan test case agar memvalidasi kebijakan keamanan RBAC aktual yang membatasi hak mutasi Kader (`403 Forbidden`).

---

## 5. BUG #5: Inkonsistensi Asersi Wewenang Pengaturan Jadwal Obat pada `MedicationScheduleTest`
- **File Terdampak:** `tests/Feature/MedicationScheduleTest.php`
- **Baris Kode Asal:** Baris 254-274 (`test_kader_in_assigned_area_can_manage_patient_schedule`)
- **Gejala Error:**
  Kader yang mencoba menyimpan/mengubah jadwal minum obat pasien ditolak dengan status 403 oleh API, namun test lama mengekspektasikan status 200.
- **Akar Masalah:**
  Kebijakan medis TB Care menetapkan bahwa jadwal minum obat dikonfigurasi oleh Pasien atau Faskes PJTB, sedangkan Kader hanya memiliki hak baca (monitoring).
- **Sebelum Perbaikan:**
  Test melakukan `Sanctum::actingAs($this->kaderUser)` lalu mengirim POST jadwal obat dan mengharapkan 200.
- **Sesudah Perbaikan:**
  1. Pasien A membuat jadwal obatnya (`200 OK`).
  2. Kader wilayah binaan membaca jadwal obat Pasien A (`200 OK`).
  3. Kader mencoba mengubah jadwal obat Pasien A -> ditolak aman (`403 Forbidden`).
- **Alasan Perubahan:**
  Menguji alur izin secara lengkap dan konsisten dengan batasan peran Kader.

---

## 6. BUG #6: Asersi Kolom RT/RW Wajib pada Payload Kosong Registrasi Pasien
- **File Terdampak:** `tests/Feature/PatientRegistrationApiTest.php`
- **Baris Kode Asal:** Baris 57-73 (`test_patient_registration_validation`)
- **Gejala Error:**
  `Failed asserting that an array has the key 'rt'.`
- **Akar Masalah:**
  Pada implementasi form registrasi pasien (`AuthController.php` baris 366-367), RT dan RW telah dibuat **opsional (nullable)** untuk mengakomodasi pasien di daerah yang tidak memiliki penomoran RT/RW formal, sementara test mengekspektasikannya sebagai field wajib pada payload kosong.
- **Tindakan Perbaikan:**
  Menghapus `'rt'` dan `'rw'` dari daftar error wajib pada pengujian payload kosong, dengan tetap mempertahankan pengujian bahwa jika RT/RW diisi karakter non-angka maka validasi tetap menolaknya.
