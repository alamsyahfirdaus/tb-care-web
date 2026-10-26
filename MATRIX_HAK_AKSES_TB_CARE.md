# MATRIKS HAK AKSES SISTEM TB CARE (GRANULAR ACCESS MATRIX)
**TB Care Web & Mobile**

---

| Dokumen | Matriks Otorisasi Fitur dan Kewenangan CRUD Pengguna |
| :--- | :--- |
| **Aplikasi** | TB Care Web & Mobile |
| **Lokasi Proyek** | `D:\My Projects\AplikasiTBCare\tb-care-web` |
| **Versi Dokumen** | 1.0 (Produksi) |
| **Tanggal Terbit** | 24 Oktober 2026 |

---

## 1. DEFINISI ROLE & KANAL AKSES

| Simbol Peran | Nama Peran | Kanal Akses | Basis Kewenangan Teritorial |
| :---: | :--- | :---: | :--- |
| **ADM** | Super Administrator (`user_type_id = 1`) | Web Admin | Nasional / Global (Seluruh Sistem) |
| **PRV** | Dinkes Provinsi (`user_type_id = 3, officer_type_id = 1`) | Web Admin | Provinsi Penugasan (`district.province_id`) |
| **KAB** | Dinkes Kabupaten/Kota (`user_type_id = 3, officer_type_id = 2`) | Web Admin | Kabupaten/Kota Penugasan (`officers.district_id`) |
| **PKM** | PJTB Puskesmas (`user_type_id = 3, officer_type_id = 3`) | Web Admin | Puskesmas Penugasan (`officers.puskesmas_id`) |
| **KDR** | Kader Puskesmas (`user_type_id = 3, officer_type_id = 4`) | Mobile App | Desa/Kelurahan Binaan (`kader_areas.village_id`) |
| **PSN** | Pasien TB (`user_type_id = 2`) | Mobile App | Data Pribadi Akun Sendiri |

---

## 2. MATRIKS GRANULAR HAK AKSES PER MODUL

Keterangan Kode Tindakan:
- **L** : Lihat / Baca (*Read / Index / Show*)
- **T** : Tambah Baru (*Create / Store*)
- **U** : Ubah Data (*Update / Edit*)
- **H** : Hapus (*Delete / Destroy*)
- **V** : Verifikasi (*Verify / Approval*)
- **C** : Cetak Dokumen Resmi (*Print HTML / PDF*)
- **E** : Ekspor Data Tabular (*Export CSV / Excel*)

---

### A. Modul Operasional Klinis & Pasien

| No | Modul / Fitur | ADM (Super Admin) | PRV (Dinkes Prov) | KAB (Dinkes Kab/Kota) | PKM (PJTB Puskesmas) | KDR (Kader) | PSN (Pasien) |
| :-: | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| 1 | **Dashboard Operasional** | L (Global) | L (Cakupan Prov) | L (Cakupan Kab) | L (Cakupan Pkm) | L (Mobile App) | L (Mobile App) |
| 2 | **Data Pasien TB** | L, T, U, H, E | L, E (Prov) | L, E (Kab) | L, T, U, H, E (Pkm) | L (Desa Binaan) | L, U (Data Sendiri) |
| 3 | **Skrining TB** | L, U, H, C, E | L, C, E (Prov) | L, C, E (Kab) | L, U, H, C, E (Pkm) | L, T (Desa Binaan) | T, L (Riwayat Sendiri) |
| 4 | **Pemeriksaan Klinis (TCM/BTA)** | L, T, U, H, E | L, E (Prov) | L, E (Kab) | L, T, U, H, E (Pkm) | - | L (Hasil Sendiri) |
| 5 | **Pengobatan & Resep OAT** | L, T, U, H, E | L, E (Prov) | L, E (Kab) | L, T, U, H, E (Pkm) | L (Desa Binaan) | L (Jadwal Sendiri) |
| 6 | **Verifikasi Bukti Minum Obat (PMO)** | L, V | L (Monitoring) | L (Monitoring) | L, V (Puskesmas) | L (Pendampingan) | T (Kirim Bukti Foto) |
| 7 | **Kunjungan Kontrol Pasien** | L, T, U, H | L (Prov) | L (Kab) | L, T, U, H (Pkm) | L (Desa Binaan) | L (Jadwal Sendiri) |
| 8 | **Kontak Erat & TPT** | L, T, U, H, E | L, E (Prov) | L, E (Kab) | L, T, U, H, E (Pkm) | L, T (Investigasi) | L, T (Anggota Serumah) |

---

### B. Modul Fasilitas, Wilayah, & Edukasi

| No | Modul / Fitur | ADM (Super Admin) | PRV (Dinkes Prov) | KAB (Dinkes Kab/Kota) | PKM (PJTB Puskesmas) | KDR (Kader) | PSN (Pasien) |
| :-: | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| 9 | **Fasilitas Kesehatan (Puskesmas)** | L, T, U, H | L (Prov) | L (Kab) | L (Puskesmasnya) | - | - |
| 10 | **Wilayah Administratif (Master)** | L, T, U, H | - | - | - | - | - |
| 11 | **Wilayah Binaan Kader (Kader Areas)** | L, T, H | L (Prov) | L (Kab) | L, T, H (Pkm) | L (Wilayahnya) | - |
| 12 | **Materi Edukasi TB** | L, T, U, H | L, T, U (Prov) | L, T, U (Kab) | L, T, U (Pkm) | L (Baca di Mobile) | L (Baca di Mobile) |
| 13 | **Notifikasi & Konsultasi Medis** | L, T, U, H | L, T (Prov) | L, T (Kab) | L, T (Pkm) | L (Mobile) | L, T (Konsultasi Mobile) |

---

### C. Modul Laporan, Analitik, & Sistem

| No | Modul / Fitur | ADM (Super Admin) | PRV (Dinkes Prov) | KAB (Dinkes Kab/Kota) | PKM (PJTB Puskesmas) | KDR (Kader) | PSN (Pasien) |
| :-: | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| 14 | **Laporan Skrining** | L, C, E (Global) | L, C, E (Prov) | L, C, E (Kab) | L, C, E (Pkm) | - | - |
| 15 | **Laporan Pasien TB** | L, C, E (Global) | L, C, E (Prov) | L, C, E (Kab) | L, C, E (Pkm) | - | - |
| 16 | **Laporan Wilayah** | L, C, E (Global) | L, C, E (Prov) | L, C, E (Kab) | L, C, E (Pkm) | - | - |
| 17 | **Laporan Pengguna Sistem** | L, C, E (Global) | - (403) | - (403) | - (403) | - | - |
| 18 | **Analitik Epidemiologi** | L (Global) | L (Prov) | L (Kab) | L (Pkm) | - | - |
| 19 | **Manajemen Akun Pengguna (Users)** | L, T, U, H | - (403) | - (403) | - (403) | - | - |
| 20 | **Master Pertanyaan & Kategori** | L, T, U, H | - (403) | - (403) | - (403) | - | - |
| 21 | **Log Aktivitas (Audit Trail)** | L, H (Global) | L (Hanya Log Sendiri)| L (Hanya Log Sendiri)| L (Hanya Log Sendiri)| - | - |
| 22 | **Versi Aplikasi Mobile (APK)** | L, T, U, H | - (403) | - (403) | - (403) | - | - |
| 23 | **Profil Pengguna Sendiri** | L, U | L, U | L, U | L, U | L, U (Mobile) | L, U (Mobile) |
| 24 | **Pengaturan Sistem & Roles** | L, U | - (403) | - (403) | - (403) | - | - |

---

## 3. CATATAN KHUSUS IMPLEMENTASI KEAMANAN

1. **Akses Web Kader & Pasien:**
   - Ditolak oleh middleware `EnsureWebAdminRole`.
   - Mengakses URL `/dashboard` atau rute admin lainnya otomatis memutus session dan mengarahkan ke halaman `/login` dengan notifikasi resmi.
2. **Proteksi IDOR pada Aksi Ubah/Hapus Pasien:**
   - URL detail dan aksi pasien menggunakan encrypted ID (`/patients/{encrypted_id}`).
   - Controller memeriksa kebijakan `$patient->isAccessibleBy($user)`; jika bukan haknya, sistem melempar HTTP 403 (*Forbidden*).
3. **Verifikasi Minum Obat:**
   - Hanya PJTB Puskesmas pembina dan Super Admin yang memiliki tombol aksi untuk mengubah status verifikasi bukti foto minum obat (`is_verified = true`).
4. **Log Aktivitas:**
   - Petugas non-admin yang melihat widget Log Aktivitas di dashboard hanya disajikan aktivitas yang mereka lakukan sendiri (`where('user_id', $user->id)`), menjaga kerahasiaan operasional faskes lain.

---

*Matriks ini disahkan sebagai acuan formal hak akses seluruh pengguna sistem TB Care.*
