# AUDIT MENDALAM KEGAGALAN PENGIRIMAN PENGINGAT (ROLE KADER)
## Tracing End-to-End: Flutter Mobile, Laravel Backend, Database, In-App Notification, dan FCM

---

## 1. LATAR BELAKANG DAN GEJALA MASALAH

Pada implementasi monitoring kepatuhan dan jadwal kontrol Role Kader TB Care, ditemukan dua keluhan utama:

1. **Muncul SnackBar Gagal Kirim Pengingat**:
   Pada Dashboard Kader, Daftar Pasien, dan Detail Pasien, dialog pengingat berhasil dibuka dengan pesan template yang rapi. Namun saat tombol **Kirim Pengingat Sekarang** ditekan, aplikasi menampilkan SnackBar merah yang menginformasikan bahwa pengiriman gagal dan dialog tidak otomatis tertutup.
2. **Peringatan Palsu Jadwal Kontrol Belum Tersedia**:
   Pada halaman Detail Data Pasien, pasien (misalnya *Alamsyah Firdaus*) jelas memiliki jadwal kontrol (`Minggu, 8 November 2026 Jam 15.30 WIB`). Namun ketika Kader menekan tombol `Ingatkan Kontrol`, muncul kotak merah dengan pesan:
   `Jadwal kontrol ke Puskesmas belum tersedia untuk pasien ini.`
   sehingga tombol kirim dinonaktifkan.

---

## 2. METODOLOGI AUDIT DAN PENELUSURAN KODE

Tim melakukan penelusuran menyeluruh (*flow tracing*) dari lapisan Flutter UI hingga lapisan basis data Laravel dan transmisi jaringan.

```text
[Flutter UI]
  └─ KaderReminderService.showReminderBottomSheet()
       ├─ Data parsing: patient_id, next_visit, medication_time
       └─ KaderReminderService.sendReminder()
            │
            ▼ (HTTP POST /api/kader/reminders/send)
[Laravel Backend]
  └─ KaderController@sendReminder
       ├─ authorizeKaderAccess()
       ├─ Validator::make()
       ├─ IDOR Scoping: $patient->isAccessibleBy($user)
       ├─ Cooldown / Rate Limiting Check
       ├─ Transmisi FCM: FcmService::sendNotification()
       ├─ Mirror ke Live Chat: Message::create()
       ├─ Simpan log riwayat: PatientReminder::create()
       ├─ Simpan in-app notification: $patientUser->notifications()->create()
       └─ Format JSON Response & Status Code
```

---

## 3. IDENTIFIKASI AKAR MASALAH (ROOT CAUSES)

### Akar Masalah 1: Status Respons Backend Terdistorsi oleh Kegagalan Kanal FCM
- **Temuan di Kode**:
  Pada berkas `app/Http/Controllers/Api/KaderController.php` (baris 470–580):
  ```php
  $sent = FcmService::sendNotification([$fcmToken], $title, $body, $fcmPayload);
  if ($sent) {
      $status = 'sent';
  } else {
      $status = 'failed';
      $errorReason = 'Layanan FCM gagal memproses token perangkat pasien.';
  }
  ...
  return response()->json([
      'success' => ($status === 'sent' || $status === 'no_token'),
      ...
  ], 200);
  ```
- **Analisis Dampak**:
  Ketika pasien memiliki token FCM di database tetapi server tidak memiliki berkas kredensial Service Account Firebase (`fcm.credentials`), atau token kedaluwarsa, `FcmService` mengembalikan `false`. Akibatnya `$status = 'failed'` dan backend mengirimkan `'success' => false` dengan pesan `"Gagal mengirim push notification ke perangkat pasien."`.
  Padahal:
  1. Catatan riwayat pengingat **telah berhasil disimpan** ke tabel `patient_reminders`.
  2. Notifikasi in-app untuk pasien **telah berhasil disimpan** ke tabel `notifications`.
  3. Pesan percakapan **telah berhasil disinkronkan** ke Live Chat.
- **Akibat di Flutter**:
  Flutter memeriksa `result['success'] == true`. Karena menerima `false`, Flutter menampilkan SnackBar merah kegagalan dan tidak menutup dialog.

---

### Akar Masalah 2: Pemutusan Relasi Jadwal Kontrol pada Detail Pasien
- **Temuan di Kode**:
  Pada `lib/main/kader/patient_detail.dart`:
  - `_buildNextVisitCard()` mengambil data kunjungan dari helper `_resolveNextUpcomingVisit()` yang mencari ke `_treatments[...]['visits']` atau state `_nextVisit`.
  - Namun ketika tombol `Ingatkan Kontrol` ditekan, fungsi hanya memanggil:
    ```dart
    KaderReminderService.showReminderBottomSheet(
      context,
      patient: _patientDetail, // <-- Tidak memiliki field 'next_visit' di root!
      defaultType: 'visit',
      ...
    );
    ```
- **Analisis Dampak**:
  Objek `_patientDetail` berasal dari endpoint `/api/patients/{id}/show`. Data `visits` di database Laravel tersimpan di dalam relasi `treatments.visits`, bukan di root `_patientDetail`.
  Karena `KaderReminderService` hanya memeriksa `patient['next_visit']` atau `patient['treatments']['next_visit_date']` (yang tidak ada di skema Laravel), variabel `hasValidVisit` bernilai `false`.
  Hal ini memicu teks error: `Jadwal kontrol ke Puskesmas belum tersedia untuk pasien ini.` dan menonaktifkan tombol kirim.

---

### Akar Masalah 3: Benturan ID Pasien vs ID Jadwal pada Dashboard Kader
- **Temuan di Kode**:
  Pada `today_control_schedules` Dashboard Kader:
  - Elemen list memiliki `'id' => $v->id` (ID Kunjungan/TreatmentVisit).
  - Dan `'patient_id' => $p->id` (ID Pasien sesungguhnya).
  Pada `KaderReminderService.dart`:
  ```dart
  final patientId = patient['id'] ?? patient['patient_id'];
  ```
- **Analisis Dampak**:
  Ketika reminder dikirim dari kartu Jadwal Kontrol di Dashboard, `patient['id']` (ID kunjungan) terambil sebagai `patientId`. Request POST mengirimkan ID kunjungan ke parameter `patient_id`. Validasi backend `exists:patients,id` atau validasi IDOR wilayah langsung menolak request dengan HTTP 404/403.

---

### Akar Masalah 4: Aturan Cooldown (Rate Limiting) Menghalangi Pengingat Kontrol
- **Temuan di Kode**:
  Terdapat batasan 5 menit untuk jenis pengingat yang sama:
  ```php
  PatientReminder::where('type', $request->type)->where('created_at', '>=', Carbon::now()->subMinutes(5))->first();
  ```
- **Analisis Dampak**:
  Kader tidak dapat mengirim pengingat kontrol berulang kali jika sebelumnya sudah pernah mengingatkan, atau ketika ingin mengirimkan pesan tambahan. Padahal aturan bisnis pengingat kontrol mengizinkan pengiriman fleksibel kapan saja selama jadwal masih valid.

---

## 4. KESIMPULAN AUDIT

Masalah bukan semata-mata pada teks SnackBar atau Flutter UI, melainkan ketidakselarasan penanganan status FCM pada backend, pemutusan parsing relasi data kunjungan pada Flutter, dan benturan penamaan `id` vs `patient_id`. Perbaikan menyeluruh dilakukan pada kedua sisi (Laravel & Flutter).
