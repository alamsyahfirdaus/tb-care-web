# LAPORAN INTEGRASI PENGINGAT DAN ALARM PASIEN

**Tanggal Implementasi:** 8 November 2026  
**Tim Pengembang:** Backend, Mobile, & Systems Security Engineer  
**Status:** Selesai, Terintegrasi, Lolos Pengujian FCM & Database

---

## 1. Alur Kerja Pengingat dari Kader ke Pasien

Kader dapat mengirim pengingat personal langsung kepada pasien binaannya dari:
1. Tombol `Ingatkan Pasien` pada card di Daftar Pasien.
2. Tombol `Ingatkan Kontrol` pada card Jadwal Kontrol di Halaman Detail Pasien.

### Komponen Pengiriman Terpadu:
Ketika kader mengirim pengingat melalui endpoint `POST /api/kader/reminders/send`:
1. **Database Notification**: Disimpan ke tabel `system_notifications` agar muncul di pusat notifikasi aplikasi pasien.
2. **Push Notification (Firebase Cloud Messaging - FCM)**: Mengirimkan pesan high-priority ke token perangkat pasien.
3. **Android Loud Alarm Payload**: Payload FCM menyertakan parameter `channel_id: "tb_care_medication_alarm"`, `sound: "alarm_sound"`, dan flag `is_alarm: true` untuk memicu nada dering alarm keras pada perangkat pasien.
4. **Chat In-App Mirroring**: Pesan otomatis direfleksikan ke percakapan privat antara Kader dan Pasien sehingga memiliki jejak digital yang tidak mudah terlewat.
5. **Pencatatan Audit Trail**: Disimpan di tabel `patient_reminders` dengan status transmisi (`sent`, `no_token`, atau `failed`).

---

## 2. Penyederhanaan Dialog Kirim Pengingat

File: `tb-care-mobile/lib/services/kader_reminder_service.dart`

### A. Pilihan Jenis Pengingat
- Tombol teks bersih: **Minum Obat** dan **Kontrol** (istilah diperbarui dari sebelumnya `Kunjungan`).
- Tanpa icon checklist atau dekorasi berlebih.
- Warna solid toska/hijau untuk pilihan aktif dan warna netral abu-abu untuk pilihan tidak aktif.

### B. Penanganan Kondisi Belum Ada Jadwal Kontrol
Ketika jenis pengingat `Kontrol` dipilih namun pasien belum memiliki jadwal kontrol aktif di Puskesmas:
- Container informasi berwarna merah lembut menampilkan teks:
  `"Jadwal kontrol ke Puskesmas belum tersedia untuk pasien ini."`
- Icon merah dihilangkan agar tampilan lebih rapi dan bersahabat.
- Tombol `Kirim Pengingat Sekarang` secara otomatis didisabled untuk mencegah pengiriman informasi tanpa jadwal pasti.

### C. Template Pesan Standar Berorientasi Pasien
- Template Minum Obat:
  ```text
  Assalamualaikum. Bapak/Ibu [Nama Pasien],
  Pengingat jadwal minum obat TB hari ini pukul [HH:mm].
  Mohon segera minum obat sesuai jadwal demi kesembuhan Bapak/Ibu.
  ```
- Template Kontrol:
  ```text
  Assalamualaikum. Bapak/Ibu [Nama Pasien],
  Pengingat jadwal kontrol ke Puskesmas:
  Tanggal: [Tanggal Lengkap]
  Waktu: [Waktu] WIB
  Tempat: [Nama Puskesmas]
  Mohon hadir tepat waktu dan membawa buku pengobatan TB.
  ```

---

## 3. Rate-Limiting dan Proteksi Anti-Spam

Untuk mencegah penyalahgunaan atau pengiriman berulang yang mengganggu pasien:
- Laravel backend menerapkan cooldown 5 menit per kombinasi pasien dan jenis pengingat (`Cache::has("kader_reminder_cooldown_{$kaderId}_{$patientId}_{$type}")`).
- Jika kader mencoba mengirim ulang dalam rentang 5 menit, endpoint mengembalikan HTTP 429 dengan kode `RATE_LIMITED` dan pesan edukatif.

---

## 4. Hasil Verifikasi Pengujian

- `test/kader_reminders_and_rbac_test.dart`:
  - `sendReminder` sukses mengirim payload medication & visit.
  - Penanganan HTTP 429 rate limit.
  - Dialog modal menonaktifkan tombol kirim saat jadwal kontrol kosong.
  - Pilihan tipe pengingat menggunakan teks `Minum Obat` dan `Kontrol`.
