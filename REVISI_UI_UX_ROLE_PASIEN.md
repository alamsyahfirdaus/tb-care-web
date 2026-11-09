# LAPORAN REVISI STRUKTUR NAVIGASI & PENYEMPURNAAN UI/UX ROLE PASIEN (PATIENT)
## APLIKASI TB CARE (FLUTTER MOBILE & LARAVEL BACKEND)

Tanggal: 9 November 2026  
Status: **SELESAI & TERVERIFIKASI PENUH (100% PASS)**  
Lingkup Pekerjaan: **Khusus Role Pasien (Patient)** tanpa menimbulkan dampak regresi pada Role Kader, Petugas (PJTB/Faskes), dan Super Admin.

---

## 1. RINGKASAN PERUBAHAN UI/UX

Berdasarkan hasil analisis audit dan 5 screenshot referensi aktual, telah dilakukan perombakan struktur navigasi dan penyempurnaan UI/UX yang komprehensif khusus untuk **Role Pasien**:

1. **Restrukturisasi Bottom Navigation Pasien menjadi 4 Menu Utama:**
   - **Tab 0: Beranda** (`Icons.home_outlined` / `Icons.home_rounded`) – Dashboard pemantauan pengobatan, salam sapaan dinamis, pengingat minum obat, dan jadwal kontrol terdekat.
   - **Tab 1: Pengobatan** (`Icons.medication_outlined` / `Icons.medication_rounded`) – Informasi detail program pengobatan, jadwal pengingat, dan riwayat.
   - **Tab 2: Edukasi** (`Icons.menu_book_outlined` / `Icons.menu_book_rounded`) – Halaman materi edukasi kesehatan TB khusus pasien yang bersifat **READ-ONLY** (tanpa tombol aksi tambah/edit/hapus dan tanpa tab draft).
   - **Tab 3: Profil** (`Icons.person_outline_rounded` / `Icons.person_rounded`) – Informasi identitas pasien, fasilitas kesehatan, dan pengaturan akun.

2. **Integrasi Menu Edukasi Read-Only:**
   - Menggunakan kembali (*reusing*) implementasi materi edukasi terpusat (`EducationPage`).
   - Secara otomatis mendeteksi role pasien (`user_type_id == 2`), menyembunyikan Floating Action Button (FAB) pembuatan materi, menyembunyikan opsi mutasi (edit/delete/toggle publish), dan menyembunyikan tab draft. Pasien hanya dapat membaca, memfilter materi terpublikasi, serta menyimpan/membuka materi favorit.
   - Tombol *"Lihat Semua"* pada section Edukasi di Beranda dihubungkan langsung untuk berpindah tab ke menu Edukasi (Tab index 2).

3. **Standardisasi App Bar & Sinkronisasi Notifikasi/Pesan:**
   - Seluruh halaman root pasien (**Beranda**, **Pengobatan**, **Edukasi**, **Profil**) kini menggunakan `TBAppBar` yang seragam.
   - Urutan ikon aksi di sebelah kanan App Bar telah distandarkan:
     1. **Lonceng (🔔 Notifikasi)** di sebelah kiri.
     2. **Pesan / Chat (💬 Konsultasi)** di sebelah kanan (sebelah kanan lonceng).
   - Kedua ikon terhubung secara reaktif dengan `NotificationCenterService.unreadCountNotifier` dan `ChatService.unreadCountNotifier`. Badge counter otomatis tersinkronisasi dan berkurang secara realtime saat pesan atau notifikasi dibaca.

4. **Penyederhanaan Tampilan Beranda (Dashboard Pasien):**
   - **Card Status Pengobatan**: Menghilangkan ikon play `▶` di samping teks "Berjalan", menyisakan badge status teks yang bersih dan rapi.
   - **Section Headers**: Menghilangkan wadah ikon dekoratif pada judul bagian:
     - Section *"Pengingat Pengobatan"*: Menghilangkan ikon jam/alarm.
     - Section *"Kontrol Berikutnya"*: Menghilangkan ikon rumah sakit.
     - Section *"Materi Edukasi Terbaru"*: Menghilangkan ikon buku.

5. **Penyempurnaan Card Kontrol Berikutnya:**
   - Judul diubah dari *"Kunjungan Berikutnya"* menjadi **"Kontrol Berikutnya"**.
   - Menghilangkan ikon kalender kecil di samping tanggal.
   - Menghilangkan baris teks lokasi Puskesmas/faskes.
   - Format tampilan: Tanggal di sisi kiri (contoh: `Minggu, 8 November 2026`) dan Jam di sisi kanan (contoh: `19:30 WIB`).
   - Query backend dan filter frontend disempurnakan: HANYA menampilkan **1 jadwal kontrol masa mendatang** yang valid (`visit_date > today OR (visit_date == today AND visit_time >= current_time)` serta mengecualikan status `Batal`, `Dibatalkan`, dan `Selesai`).
   - Apabila belum ada atau seluruh jadwal telah berlalu, otomatis menampilkan empty state: **"Belum Ada Jadwal Kontrol Mendatang"**.

6. **Penyederhanaan Halaman Pengobatan (`treatment.dart`):**
   - **Card Informasi Pengobatan**: Menghilangkan badge redundan status "Berjalan" di pojok kanan atas.
   - **Card Jadwal Minum Obat**: Menghilangkan ikon jam dan badge "Pengingat Aktif" di samping judul, mempertahankan teks judul, waktu pengingat harian, tombol "Ubah Waktu Minum Obat", dan tombol "Uji Alarm & Pengingat".
   - **Daftar Menu Aksi**: Menghilangkan card *"Daftar Obat"* (karena data obat dan resep sudah terintegrasi lengkap pada menu *"Riwayat Pengobatan"*). Tiga menu utama yang dipertahankan:
     1. Riwayat Minum Obat
     2. Riwayat Pengobatan
     3. Anggota Serumah

---

## 2. PEMETAAN REVISI BERDASARKAN SCREENSHOT REFERENSI

| No | Screenshot Referensi | Kondisi Sebelumnya (Masalah Visual) | Perubahan & Solusi yang Diimplementasikan |
|---|---|---|---|
| 1 | **Screenshot 1** (Beranda Bagian Atas) | • App Bar menampilkan Chat terlebih dahulu baru Lonceng.<br>• Status Pengobatan memiliki ikon play `▶` di badge "Berjalan".<br>• Section Pengingat & Kunjungan memiliki ikon dekoratif.<br>• Jadwal kunjungan menampilkan waktu lampau (19:30 WIB padahal jam 23:44) serta baris lokasi faskes Tamansari.<br>• Bottom Navigation hanya 3 menu. | • App Bar distandarkan: Lonceng 🔔 lalu Chat 💬.<br>• Ikon play di samping "Berjalan" dihilangkan.<br>• Wadah ikon dekoratif section header dihilangkan.<br>• Filter backend & frontend memastikan jadwal lampau tidak ditampilkan; baris Puskesmas & ikon kalender dihilangkan.<br>• Bottom nav menjadi 4 menu (Beranda, Pengobatan, Edukasi, Profil). |
| 2 | **Screenshot 2** (Pengobatan Bagian Atas) | • App Bar Pengobatan hanya menampilkan ikon Chat (tanpa Lonceng notifikasi).<br>• Card Informasi Pengobatan memiliki badge "Berjalan" redundan.<br>• Card Jadwal Minum Obat memiliki ikon jam dan badge "Pengingat Aktif". | • App Bar Pengobatan menggunakan `TBAppBar` dengan urutan Lonceng 🔔 lalu Chat 💬 secara lengkap.<br>• Badge redundan "Berjalan" pada Informasi Pengobatan dihapus.<br>• Ikon jam & badge "Pengingat Aktif" pada header Jadwal Minum Obat dihapus. |
| 3 | **Screenshot 3** (Profil Pasien) | • Tampilan Profil sudah memiliki urutan ikon yang benar (Lonceng lalu Chat).<br>• Namun Bottom Navigation masih 3 menu. | • Menjadikan susunan App Bar halaman Profil sebagai standar acuan untuk Beranda, Pengobatan, dan Edukasi.<br>• Bottom navigation diselaraskan menjadi 4 menu. |
| 4 | **Screenshot 4** (Beranda Bagian Bawah) | • Section "Materi Edukasi Terbaru" memiliki ikon buku.<br>• Tombol "Lihat Semua" melakukan push route baru, tidak memanfaatkan tab navigasi. | • Ikon buku pada judul "Materi Edukasi Terbaru" dihilangkan.<br>• Tombol "Lihat Semua" langsung mengalihkan tab aktif ke menu Edukasi (Tab 2) tanpa reload. |
| 5 | **Screenshot 5** (Pengobatan Bagian Bawah) | • Terdapat card aksi "Daftar Obat" yang terpisah padahal informasinya duplikasi dengan "Riwayat Pengobatan". | • Card "Daftar Obat" dihilangkan dari daftar menu aksi dan skeleton loader. Tersisa 3 menu utama: Riwayat Minum Obat, Riwayat Pengobatan, dan Anggota Serumah. |

---

## 3. DAFTAR FILE YANG DIUBAH

### A. Flutter Mobile (`tb-care-mobile`)
1. **`lib/main/pasien/home.dart`**
   - Menambahkan import `EducationPage`, `ChatService`, dan `NotificationCenterService`.
   - Mengubah `IndexedStack` dan `_buildBottomNav` menjadi 4 menu (`Beranda`, `Pengobatan`, `Edukasi`, `Profil`).
   - Mengubah `_buildAppBar` menggunakan `TBAppBar` (Lonceng lalu Chat).
   - Menyederhanakan badge pada `_buildTreatmentCard` (tanpa ikon play).
   - Menghilangkan ikon dekoratif pada header `_buildMedicationReminderCard`, `_buildNextVisitCard`, dan `_buildLatestEducationSection`.
   - Mengubah judul menjadi "Kontrol Berikutnya", menghilangkan ikon kalender, menghilangkan baris lokasi Puskesmas, dan menampilkan format tanggal di kiri serta jam di kanan.
   - Menambahkan empty state `"Belum Ada Jadwal Kontrol Mendatang"`.
   - Menghubungkan tombol "Lihat Semua" ke tab index 2 (`Edukasi`).
   - Menghapus method `_showNotificationSheet` dan unused imports.
2. **`lib/main/pasien/treatment.dart`**
   - Memperbarui `appBar` menjadi `TBAppBar(title: 'Pengobatan', showBackButton: false, showNotificationButton: true, showChatButton: true)`.
   - Menghapus badge status "Berjalan" dari header `_buildMainJourneyCard`.
   - Menghapus ikon jam dan badge "Pengingat Aktif" dari header `_buildMedicationScheduleCard`.
   - Menghapus `_buildMedicationSection()` ("Daftar Obat") dari `_buildHistorySection`.
   - Menghapus blok skeleton loader untuk "Daftar Obat".
   - Menambahkan sinkronisasi unread count `NotificationCenterService` dan `ChatService` pada `_refreshTreatmentPage`.
3. **`lib/main/pasien/education.dart`**
   - Menyetel eksplisit `showBackButton: false` pada `TBAppBar` agar tidak menampilkan tombol kembali saat menjadi root tab navigasi.
4. **`lib/profile.dart`**
   - Menyetel eksplisit `showBackButton: false` pada seluruh variasi `TBAppBar`.
5. **`lib/models/patient_home_model.dart`**
   - Menambahkan helper `fullDateTime` dan `isFutureVisit` pada class `VisitInfo`.
   - Memastikan pemfilteran jadwal kunjungan pasien hanya memasukkan jadwal yang statusnya bukan Batal/Selesai dan waktunya berada di masa mendatang.
6. **`test/home_page_widget_test.dart`**
   - Menyesuaikan pengujian unit/widget untuk mencakup 4 menu bottom nav pasien, header "Kontrol Berikutnya", dan validasi tanggal masa mendatang.

### B. Laravel Backend (`tb-care-web`)
1. **`app/Http/Controllers/Api/PatientController.php`**
   - Menyempurnakan query `$visitsQuery` pada endpoint `home()`:
     - Menggunakan zona waktu `Asia/Jakarta`.
     - Mengecualikan status kunjungan `['Batal', 'Dibatalkan', 'Selesai']`.
     - Memfilter tanggal `visit_date > today` ATAU `(visit_date == today AND (visit_time IS NULL OR visit_time >= current_time))`.
     - Mengembalikan single `$nextVisitData` yang akurat hanya jika masih berstatus masa mendatang.

---

## 4. HASIL PENGUJIAN FUNGSIONAL & REGRESI

### A. Pengujian Flutter Mobile
- **Static Analysis (`flutter analyze`):**
  ```text
  Analyzing tb-care-mobile...
  No issues found! (ran in 6.9s)
  ```
  *Hasil: 0 warning, 0 error, kode 100% bersih.*
- **Unit & Widget Test Suite (`flutter test`):**
  ```text
  01:26 +244: All tests passed!
  ```
  *Hasil: Seluruh 244 test suites (termasuk Role Kader, PJTB/Petugas, Super Admin, dan Pasien) LULUS 100%.*

### B. Pengujian Laravel Backend
- **Keamanan & Otorisasi Pasien (`PatientAuthorizationSecurityTest`):** 9/9 PASS (100%)
- **Data Dashboard Pasien (`PatientHomeApiTest`):** 3/3 PASS (100%)
- **Pengobatan & Resep Pasien (`MedicationApiTest`):** 7/7 PASS (100%)
- **Pendaftaran & Pembuatan Treatment Pasien (`PatientRegistrationTreatmentTest`):** 6/6 PASS (100%)
- **Kontak Serumah Pasien (`CloseContactApiTest`):** 8/8 PASS (100%)
- **Manajemen Akun Pasien & RBAC (`DeactivatedUserAccessTest`, `RbacDataScopeTest`):** 25/25 PASS (100%)

---

## 5. KESIMPULAN KESIAPAN FITUR ROLE PASIEN

1. **Konsistensi Visual & Interaksi:**
   - Navigasi 4 menu berjalan mulus melalui `IndexedStack` tanpa reload state yang tidak perlu.
   - App Bar pada seluruh halaman pasien memiliki ritme visual yang identik: Judul di kiri, Lonceng di kanan, dan Chat di sebelah kanannya.
   - Badge counter realtime tersinkronisasi di semua tab ketika notifikasi atau pesan konsultasi dibuka.
2. **Kerapian Antarmuka Pasien:**
   - Elemen visual yang tidak perlu (ikon play pada badge status berjalan, ikon alarm, ikon rumah sakit, ikon buku pada section header, badge pengingat aktif, dan card duplikat daftar obat) telah berhasil dihilangkan.
   - Informasi Kontrol Berikutnya tampil ringkas, bersih, dan hanya menampilkan jadwal yang relevan di masa mendatang.
3. **Integritas Multi-Role Terjamin:**
   - Seluruh perubahan navigasi, App Bar, dan card diisolasi penuh pada alur Role Pasien.
   - Hak akses dan antarmuka Role Kader, Petugas Faskes (PJTB), dan Admin tetap utuh, terbukti dengan kelulusan 100% pada 244 test suite Flutter dan seluruh test otorisasi backend Laravel.
