# DOKUMENTASI REDESIGN EMPTY STATE HALAMAN KONSULTASI — SELURUH ROLE TB CARE

Dokumen ini mencatat penyempurnaan UI/UX pada halaman Konsultasi aplikasi TB Care, khususnya tampilan *Empty State* ketika pengguna belum memiliki percakapan aktif. Perubahan ini berlaku secara menyeluruh dan terpadu untuk semua peran pengguna (**Pasien**, **Kader**, **Petugas**, dan **Admin**).

---

## 1. KONDISI UI SEBELUM PERUBAHAN

Sebelum redesign dilakukan, halaman Konsultasi saat belum memiliki percakapan menampilkan:
- **App Bar:** Judul "Konsultasi" dengan tombol kembali dan menu titik tiga (more vert).
- **Search Bar:** Input pencarian *"Cari percakapan..."* dengan background abu-abu membulat.
- **Area Kosong (Empty State):**
  - Lingkaran berukuran 100x100 dengan ikon chat di tengah.
  - Teks judul sederhana: `"Belum ada percakapan"` (huruf kecil).
  - Tidak ada teks deskripsi pendukung atau penjelasan mengenai fungsi konsultasi.
  - Tombol aksi: Hanya bertuliskan `"Mulai Percakapan Baru"` tanpa ikon visual pendukung.
  - Memiliki banyak ruang kosong (*dead space*) vertikal yang tidak termanfaatkan dengan baik.

---

## 2. DESAIN EMPTY STATE SETELAH PERUBAHAN

### A. Ilustrasi Visual yang Lebih Menarik
- Lingkaran berdiameter `96px` dengan warna background biru muda elegan (`Color(0xFFE0F2FE)`) dan outline border halus (`Color(0xFFBAE6FD)`, `1.5px`).
- Ikon chat di tengah menggunakan `Icons.chat_bubble_outline_rounded` berukuran `44px` dengan warna biru primer TB Care (`AppColors.primary`).

### B. Tipografi Judul & Deskripsi Pendukung
- **Judul Utama:**
  ```text
  Belum Ada Percakapan
  ```
  - Menggunakan font `GoogleFonts.plusJakartaSans`, `18px`, `FontWeight.bold`, warna gelap kontras (`Color(0xFF0F172A)`).
- **Deskripsi Pendukung:**
  ```text
  Mulai konsultasi untuk berkomunikasi dan mendapatkan informasi terkait pengobatan TB Care.
  ```
  - Menggunakan font `GoogleFonts.plusJakartaSans`, `13.5px`, warna abu-abu kebiruan (`Color(0xFF64748B)`), *line height* `1.45`, *text-align* tengah (`TextAlign.center`), dan dibatasi padding horizontal agar proporsional dalam 2–3 baris.

---

## 3. PENAMBAHAN IKON PADA TOMBOL AKSI

Tombol utama diubah menjadi tombol yang lebih tegas, mudah diidentifikasi, dan ramah sentuhan:
- Menggunakan widget `ElevatedButton.icon`.
- **Ikon Tombol:** `Icons.add_comment_rounded` berukuran `19px` berwarna putih (`Colors.white`), diposisikan di sebelah kiri label teks.
- **Label Teks:** `"Mulai Percakapan Baru"` dengan font `GoogleFonts.plusJakartaSans`, `14px`, `FontWeight.w600`, berwarna putih.
- **Styling:**
  - Background: Biru utama TB Care (`AppColors.primary`).
  - Sudut: Membulat penuh / *pill shape* (`BorderRadius.circular(24)`).
  - Elevasi: `elevation: 2` dengan bayangan lembut (`shadowColor: AppColors.primary.withValues(alpha: 0.35)`).
  - Padding: `EdgeInsets.symmetric(vertical: 14, horizontal: 24)`.
  - Fungsi navigasi: Tetap memanggil `_navigateToSelectRecipient` untuk memilih penerima pesan sesuai aturan RBAC masing-masing role.

---

## 4. PENANGANAN VARIATIF STATUS KONSULTASI (EMPTY STATE STATES)

Tampilan disesuaikan dengan konteks pengguna:

| Kondisi | Judul | Deskripsi | Aksi |
|---|---|---|---|
| **Belum Ada Percakapan** | `Belum Ada Percakapan` | *"Mulai konsultasi untuk berkomunikasi dan mendapatkan informasi terkait pengobatan TB Care."* | Tombol icon: `+ Mulai Percakapan Baru` |
| **Pencarian Tidak Ditemukan** | `Percakapan Tidak Ditemukan` | *"Coba gunakan kata kunci lain untuk mencari percakapan."* | Ikon `search_off_rounded` tanpa tombol baru |
| **Gagal Memuat (Error)** | `Gagal Memuat Percakapan` | *"Silakan periksa koneksi internet dan coba kembali."* | Tombol icon: `Coba Lagi` |
| **Sedang Memuat (Loading)** | - | *CircularProgressIndicator* terpusat | - |
| **Sudah Ada Percakapan** | - | Daftar percakapan bergaya WhatsApp dengan FAB *"Mulai Percakapan Baru"* | Tap percakapan untuk live chat |

---

## 5. PENERAPAN PADA SELURUH ROLE

Komponen `ConversationListPage` merupakan halaman konsultasi terpusat yang digunakan oleh seluruh peran pengguna TB Care:

1. **Role Pasien:**
   - Diakses melalui tombol ikon pesan konsultasi pada App Bar TB Care (`tb_chat_icon.dart` / `tb_app_bar.dart`).
   - Penerima konsultasi diarahkan kepada Kader binaan atau Petugas/PJTB Puskesmas.
2. **Role Kader:**
   - Diakses melalui menu Bottom Navigation tab *"Konsultasi"* (`kader/home.dart`) maupun ikon pesan App Bar.
   - Penerima konsultasi mencakup Pasien binaan di wilayah kerjanya dan Petugas faskes.
3. **Role Petugas Kesehatan:**
   - Diakses melalui ikon pesan App Bar (`petugas/home.dart`, dengan `isStaff: true`).
   - Menyertakan tombol broadcast pengingat minum obat massal (`Icons.campaign`) pada App Bar.
4. **Role Admin:**
   - Mengikuti hak akses supervisor faskes pada aplikasi mobile.

---

## 6. DAFTAR FILE YANG DIMODIFIKASI

### Proyek Flutter Mobile (`tb-care-mobile`):
1. **`lib/main/chat/conversation_list_page.dart`**:
   - Menambahkan import `GoogleFonts`.
   - Memperbarui `_buildEmptyState()` dengan ilustrasi baru, judul Title Case, deskripsi pendukung, dan `ElevatedButton.icon(Icons.add_comment_rounded)`.
   - Memperbarui `_buildSearchNotFoundState()` dengan icon dan teks baku.
   - Memperbarui error state dengan ikon dan teks profesional.
2. **`test/conversation_empty_state_redesign_test.dart`**:
   - File pengujian baru yang memvalidasi Empty State, ilustrasi, teks, tombol dengan icon, konsistensi antarrole (Pasien vs Staff), Search Not Found, dan Error State.
3. **`test/conversation_bulk_delete_test.dart`**:
   - Memperbarui ekspektasi string judul Empty State menjadi `Belum Ada Percakapan`.
4. **`test/conversation_hide_for_me_test.dart`**:
   - Memperbarui ekspektasi string judul Empty State menjadi `Belum Ada Percakapan`.
5. **`test/kader_dashboard_and_navigation_test.dart`**:
   - Memperbarui ekspektasi string judul Empty State saat navigasi kader ke Konsultasi.
6. **`test/kader_pengingat_dan_konsultasi_refinement_test.dart`**:
   - Memperbarui ekspektasi judul, deskripsi pendukung, dan verifikasi ikon `add_comment_rounded` pada tombol aksi.

---

## 7. HASIL PENGUJIAN & VERIFIKASI

### A. Flutter Analyze
```bash
flutter analyze
```
**Hasil:**
```text
Analyzing tb-care-mobile...
No issues found! (ran in 7.5s)
```

### B. Flutter Widget & Unit Testing
1. **Empty State Redesign Test:**
   ```bash
   flutter test test/conversation_empty_state_redesign_test.dart
   ```
   **Hasil:** `4/4 passed (100%)`
2. **Conversation Suite Tests (Bulk Delete & Hide For Me):**
   ```bash
   flutter test test/conversation_bulk_delete_test.dart test/conversation_hide_for_me_test.dart
   ```
   **Hasil:** `7/7 passed (100%)`
3. **Kader & Patient Navigation Tests:**
   ```bash
   flutter test test/kader_dashboard_and_navigation_test.dart test/kader_pengingat_dan_konsultasi_refinement_test.dart test/patient_visit_schedule_refinement_test.dart
   ```
   **Hasil:** `26/26 passed (100%)`

### C. Backend Integrity & Zero Regression
- Tidak ada modifikasi pada backend Laravel (`ChatController`, `MessageController`, database migrations, maupun routes).
- Seluruh hak akses chat, RBAC, dan websocket/FCM notifications tetap utuh dan berfungsi normal.
