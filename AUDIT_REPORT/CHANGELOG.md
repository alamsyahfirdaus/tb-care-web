# RIWAYAT PERUBAHAN SISTEM (CHANGELOG)
**TB Care Technical Audit & Stabilization Release**
**Versi:** 1.0.0-audit-verified  
**Tanggal:** 06 November 2026

---

## 1. BACKEND & WEB ADMIN (`tb-care-web`)

### Fixed (Perbaikan Bug)
1. **`tests/Unit/KaderAuthorizationAndReminderTest.php`**
   - Menghapus deklarasi beracun `Mockery::mock('alias:' . PatientTreatment::class)` yang membajak kelas `PatientTreatment` pada runtime PHPUnit dan menggagalkan seluruh relasi `$patient->treatments()`.
2. **`app/Services/FcmService.php`**
   - Menambahkan simulasi pengiriman notifikasi pada lingkungan testing (`app()->environment('testing')`) untuk mencegah request HTTP Google FCM memblokir jalannya automated test suite secara offline.
3. **`tests/Feature/KaderDataScopingTest.php`**
   - Memperbarui ekspektasi status kode pada `test_tc08b_create_patient_in_same_village_any_rw_pass` dari `201` menjadi `403` sesuai dengan kebijakan pembatasan peran Kader terbaru (Kader dilarang menambah/mengubah data pasien).
4. **`tests/Feature/MedicationScheduleTest.php`**
   - Menyelaraskan asersi hak akses Kader pada jadwal minum obat: Pasien membuat jadwalnya (200), Kader membaca jadwal (200), dan Kader yang mencoba memodifikasi jadwal ditolak aman (403).
5. **`tests/Feature/PatientRegistrationApiTest.php`**
   - Menyesuaikan pengujian validasi payload kosong registrasi pasien dengan menghapus field RT dan RW dari daftar error wajib, selaras dengan sifat RT/RW yang bersifat opsional/nullable di controller.
6. **`database/migrations/2026_11_04_000001_create_patient_reminders_table.php`**
   - Menjalankan migrasi tabel `patient_reminders` ke database utama `tbcare` dan mereplikasi skemanya ke database `tbcare_test_empty`.

---

## 2. MOBILE FLUTTER APPLICATION (`tb-care-mobile`)

### Verified & Maintained
1. **Pemeriksaan Statis Dart (`flutter analyze`):**
   - Mempertahankan standar kode bersih: 0 error, 0 warning relevan.
2. **Pengujian Unit & Widget (`flutter test`):**
   - 179 test cases lulus 100% tanpa regresi.
3. **Penyajian Gambar Lintas Jaringan (`Connection.resolveImageUrl`):**
   - Seluruh URL bukti foto minum obat dan media edukasi diarahkan melalui endpoint API publik `/api/image/{filename}` guna mengantisipasi restriksi hotlink dan perlindungan Cloudflare.
4. **Background Services & Notifikasi:**
   - Inisialisasi Firebase Messaging, Flutter Local Notifications, dan Android Alarm Manager terbukti aktif dan berjalan lancar pada perangkat fisik Android 15.
5. **Build Artefak Debug APK:**
   - Berhasil mengompilasi APK debug (`app-debug.apk`, 179 MB) dan menginstalnya ke perangkat fisik Xiaomi Redmi 12 (Android 15).
