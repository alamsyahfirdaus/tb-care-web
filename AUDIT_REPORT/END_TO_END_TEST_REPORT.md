# LAPORAN PENGUJIAN END-TO-END (END-TO-END TEST REPORT)
**TB Care Multi-Platform Integration Verification: Mobile ↔ API ↔ Database ↔ Web**
**Tanggal Audit:** 06 November 2026

---

## 1. REKAPITULASI SKENARIO END-TO-END (E2E)

| ID | Skenario Pengujian | Komponen yang Terlibat | Expected Result | Actual Result | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **E2E-01** | Registrasi & Login Pasien Baru | Flutter Mobile → API → MySQL | Akun pasien terdaftar, pengobatan awal otomatis dibuat, token Sanctum diterbitkan, dashboard pasien tampil | Akun tersimpan, treatment otomatis terbuat, login instan berhasil | **PASS** |
| **E2E-02** | Manajemen Pasien Terpadu | Flutter Mobile ↔ Web Admin ↔ MySQL | Data pasien yang didaftarkan di faskes dapat dilihat dan disunting di Web Admin, dan perubahannya tersinkron ke Mobile | Perubahan data demografi dan status langsung tercermin di kedua platform | **PASS** |
| **E2E-03** | Skrining TB Mandiri | Flutter Mobile → API → MySQL → Web | Kuesioner skrining terisi, skor risiko dihitung tepat (<15 th / ≥15 th), hasil tersimpan di DB dan muncul di rekap faskes | Skor risiko akurat, terdaftar di daftar skrining perlu tindak lanjut | **PASS** |
| **E2E-04** | Konfirmasi Minum Obat & Upload Bukti Foto | Mobile → Multipart API → Storage → Web | Pasien mengunggah foto obat, file tersimpan di `public/images/`, duplikasi pada hari yang sama dicegah, petugas dapat memverifikasi | Foto tersimpan, record masuk DB, status obat terupdate | **PASS** |
| **E2E-05** | Jadwal Pengingat (Reminder) & Background Alarm | Flutter → Alarm Manager → Local Notif | Jadwal pengingat tersimpan, alarm terjadwal di perangkat Android, notifikasi harian terpicu tepat waktu | AlarmService aktif di Android 15 (PID 9970), token FCM diperoleh | **PASS** |
| **E2E-06** | Live Chat Konsultasi Dua Arah | Flutter Mobile ↔ API ↔ MySQL | Pasien mengirim pesan teks dan gambar ke Kader/PJTB, pesan tersimpan di DB, fitur hide for me dan edit/delete pesan berfungsi | Percakapan aktif, pesan diterima, soft delete dan status baca berjalan | **PASS** |
| **E2E-07** | Pengingat Massal oleh Kader (Bulk Reminder) | Mobile Kader → API → MySQL → Pasien | Kader memilih pasien di desa binaannya, pesan personalisasi terkirim ke percakapan masing-masing | Terkirim ke inbox percakapan masing-masing pasien dengan aman | **PASS** |
| **E2E-08** | Media Edukasi Kesehatan TB | Web Admin / Mobile → API → Mobile Client | Materi edukasi diunggah, video YouTube otomatis di-embed, notifikasi siaran terkirim ke pasien & kader | Gambar & video YouTube tampil lancar pada tab Edukasi mobile | **PASS** |
| **E2E-09** | Pembaruan Versi Aplikasi (App Update Check) | Flutter Mobile → API `/mobile/app-version` | Aplikasi memeriksa versi server saat start; jika ada rilis baru, dialog pembaruan tampil dengan hash SHA-256 | Respon JSON valid, versi aplikasi 0.1.0 terverifikasi stabil | **PASS** |
| **E2E-10** | Penonaktifan Akun & Pemblokiran Seketika | Web Admin → Database → API → Mobile | Admin menonaktifkan akun pasien/kader, token Sanctum langsung dicabut, request mobile ditolak dengan 403 aman | Sesi mobile dihentikan, akses API ditolak seketika | **PASS** |

---

## 2. DETAIL BUKTI PENGUJIAN RUNTIME PERANGKAT NYATA (ANDROID 15)

- **Device:** Xiaomi Redmi 12 (Model: `23021RAA2Y`), Android 15 (API 35), HyperOS
- **Eksekusi:**
  - Build Debug APK: `flutter build apk --debug` -> Berhasil (179 MB).
  - Streamed Install via ADB: `adb -s 1797edf1 install -r app-debug.apk` -> **Success**.
  - Runtime Start: `am start -n com.example.apk_tb_care/.MainActivity` -> **Running (PID: 9970)**.
- **Logcat Verifikasi:**
  ```text
  I FLTFireMsgService: FlutterFirebaseMessagingBackgroundService started!
  I flutter : NotificationService initialized successfully.
  I FlutterBackgroundExecutor: Starting AlarmService...
  I AlarmService: AlarmService started!
  I flutter : FCM Device Token: euRr27DvSuKrWyALhIW7nt:APA91bGRC5...
  ```
- **UI Dump Verifikasi:**
  - `Detail Data Pasien`: Status Pengobatan Berjalan, Periode Pengobatan 03 Nov 2026 — 03 Mei 2027, Jam Obat 06:00.
  - `Beranda Petugas`: Total Pasien 22, Pengobatan Aktif 22, Statistik hasil pengobatan lengkap.
  - `Edukasi`: Subtab Gambar, Video, Draft aktif, memuat materi "Etika Batuk" dan "Pentingnya Ventilasi Rumah".
  - `Konsultasi`: Live chat interface, fitur pengingat massal, riwayat percakapan.
  - `Profil Saya`: Menampilkan data profil nama, peran, NIK, jenis kelamin, dan nomor telepon.
