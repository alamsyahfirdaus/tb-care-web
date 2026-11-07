# LAPORAN PENYEMPURNAAN TEMPLATE PENGINGAT & PESAN EDITABLE
**Aplikasi:** TB Care Mobile & TB Care Web  
**Komponen:** Layanan Pengingat Kader (`KaderReminderService`) & API Controller (`KaderController`)  
**File Sumber:**  
- `tb-care-mobile/lib/services/kader_reminder_service.dart`  
- `tb-care-mobile/lib/main/chat/bulk_reminder_page.dart`  
- `tb-care-web/app/Http/Controllers/Api/KaderController.php`  
**Status:** Selesai, Teruji 100% Lulus  

---

## 1. Latar Belakang & Masalah
Sebelumnya, dialog modal pengingat pasien (*bottom sheet*) bersifat statis (*read-only*). Kader tidak dapat menyesuaikan redaksi pesan atau menambahkan catatan khusus. Selain itu, kalimat pembuka masih menggunakan kata sapaan kasual seperti *"Halo, [Nama Pasien]"* yang kurang mencerminkan standar etika dan kesopanan komunikasi kesehatan resmi di Indonesia.

---

## 2. Rincian Perubahan yang Diimplementasikan

### A. Standarisasi Greeting Pembuka
Seluruh pesan pengingat di aplikasi TB Care kini diawali dengan salam resmi yang sopan dan santun:
```text
"Assalamualaikum. Bapak/Ibu [Nama Pasien],"
```
Penggunaan kata sapaan kasual (*"Halo, [Nama]"*) telah dihapus secara menyeluruh di modul pengingat pasien individu maupun pengingat massal (*bulk reminder*).

### B. Template Standar Minum Obat yang Baru
Format default otomatis untuk pengingat jadwal minum obat:
```text
Assalamualaikum. Bapak/Ibu [Nama Pasien],

Pengingat jadwal minum obat TB hari ini pukul [Jam Minum Obat].

Mohon segera minum obat sesuai jadwal dan lakukan konfirmasi minum obat melalui aplikasi TB Care.

Terima kasih.
```
*Catatan:* Parameter `[Jam Minum Obat]` diformat rapi dalam pola `HH:mm` (contoh: `08:00`), memotong detik dari database jika ada.

### C. Template Standar Kunjungan Puskesmas yang Baru
Format default otomatis untuk pengingat jadwal kontrol atau kunjungan:
```text
Assalamualaikum. Bapak/Ibu [Nama Pasien],

Pengingat jadwal kunjungan atau kontrol pengobatan TB dengan rincian sebagai berikut:

Tanggal: [Tanggal Kunjungan]
Waktu: [Waktu Kunjungan]
Tempat: [Nama Puskesmas]

Mohon hadir sesuai jadwal yang telah ditentukan untuk memastikan pengobatan berjalan dengan baik.

Terima kasih.
```
*Catatan:* Parameter `[Tanggal Kunjungan]` diformat lengkap bahasa Indonesia (contoh: `15 November 2026`), `[Waktu Kunjungan]` dengan satuan WIB (contoh: `08:00 WIB`), dan `[Nama Puskesmas]` merujuk pada faskes pengampu pasien.

### D. Fitur Pesan Editable & Tombol Reset Template
1. **Multi-line Text Field:**
   - Disediakan `TextFormField` 4 hingga 6 baris yang nyaman untuk mengetik.
   - Dilengkapi `FocusNode` dan `TextEditingController` dengan siklus hidup (*lifecycle*) `StatefulWidget` yang terisolasi dan bersih, mencegah kebocoran *focus scope* pada Flutter test binding.
2. **Tombol "Reset Template":**
   - Tombol teks dengan icon refresh di sudut kanan atas area pengetikan.
   - Memungkinkan kader mengembalikan pesan editan ke template standar secara instan dengan satu ketukan.
3. **Validasi Input:**
   - Panjang pesan dibatasi hingga 1000 karakter dengan indikator panjang karakter yang responsif.
   - Validasi ketat mencegah pengiriman pesan kosong atau pesan yang hanya berisi karakter spasi/whitespace.
   - Tombol pengiriman dinonaktifkan secara otomatis jika teks tidak valid atau saat pengiriman sedang berlangsung.

### E. Integrasi Pengiriman Multi-Channel di Backend
Pesan yang sudah diedit oleh kader dikirimkan ke backend Laravel melalui endpoint `POST /api/kader/reminders/send`. Backend menjamin pesan editan tersebut sampai ke pasien melalui 4 saluran terpadu:
1. **Push Notification (Firebase Cloud Messaging):** Dikirimkan ke token perangkat aktif milik pasien dengan isi pesan editan kader.
2. **Pusat Notifikasi Dalam Aplikasi (In-App Notification Center):** Dicatat dalam tabel `notifications` milik user pasien sehingga pasien dapat membuka dan membaca kembali pesan tersebut di aplikasi kapan saja.
3. **Cermin Pesan Obrolan (Live Chat Mirror):** Direkam sebagai pesan riwayat obrolan (`Message`) antara kader dan pasien.
4. **Log Riwayat Pengingat (`PatientReminder`):** Dicatat ke dalam tabel audit log pengingat kader beserta timestamp, tipe, dan status pengiriman.

---

## 3. Verifikasi & Pengujian
- **Pengujian Mobile:** `tb-care-mobile/test/kader_reminders_and_rbac_test.dart`
  - Memverifikasi rendering awal template pesan baru.
  - Memverifikasi keberadaan tombol `Reset Template`.
  - Memverifikasi validasi input pesan kosong.
  - Memverifikasi pergantian dinamis template antara Minum Obat dan Kunjungan.
- **Pengujian Backend:** `tb-care-web/tests/Unit/KaderAuthorizationAndReminderTest.php`
  - Memverifikasi pengiriman payload pengingat dengan pesan kustom.
  - Memverifikasi penolakan rate-limited (cooldown 5 menit).
- **Hasil:** Seluruh pengujian lolos 100%.
