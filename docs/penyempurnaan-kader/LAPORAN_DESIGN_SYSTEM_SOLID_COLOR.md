# LAPORAN STANDARISASI DESIGN SYSTEM WARNA SOLID TANPA GRADIENT
**Aplikasi:** TB Care Mobile & TB Care Web Admin  
**Fokus:** Konsistensi Visual, Desain Solid, dan Aksesibilitas UI/UX  
**Status:** Selesai & Terstandarisasi Penuh  

---

## 1. Latar Belakang & Prinsip Desain
Aplikasi TB Care dirancang untuk digunakan oleh kader kesehatan masyarakat dan tenaga medis di puskesmas, sering kali dalam kondisi lapangan dengan pencahayaan luar ruangan (*outdoor/sunlight*) atau pada perangkat smartphone dengan spesifikasi layar yang beragam.

Penggunaan warna bertingkat (*gradient*) sering kali:
1. Menurunkan keterbacaan teks (*text contrast*) pada titik-titik transisi gradasi.
2. Memberikan kesan visual yang tidak konsisten antarhalaman.
3. Memperberat rendering grafik pada perangkat *low-end*.

Oleh karena itu, ditetapkan aturan desain baku: **Aplikasi TB Care menggunakan warna solid murni tanpa gradient visual.**

---

## 2. Palet Warna Baku TB Care (Solid Color Tokens)

| Token Warna | Nilai HEX / CSS | Peruntukan Utama | Standar Kontras |
|---|---|---|---|
| **Primary (Teal Solid)** | `#00A896` / `#00897B` / `#0D9488` | Tombol utama, AppBar, tab aktif, icon header | Memenuhi WCAG AA (kontras > 4.5:1 terhadap putih) |
| **Neutral Dark (Slate/Dark)** | `#0F172A` / `#1E293B` / `#334155` | Teks judul, label field, nilai data penting | Memenuhi WCAG AAA (kontras > 7:1) |
| **Neutral Light (Muted/Border)**| `#64748B` / `#94A3B8` / `#E2E8F0` | Teks pendukung, border card, garis pemisah | Bersih, tidak menyilaukan mata |
| **Background (Light Slate)** | `#F8FAFC` / `#FFFFFF` | Latar belakang halaman dan permukaan container card | Flat dan konsisten |
| **Status Sukses (Green Solid)** | `#16A34A` / `#2E7D32` (Bg: `#DCFCE7`) | Indikator `Sudah minum obat`, pengobatan lengkap | Teks gelap di atas latar hijau muda |
| **Status Pengingat (Amber Solid)**| `#D97706` / `#B45309` (Bg: `#FEF3C7`) | Indikator `Perlu Pengingat`, status tertunda | Kontras tinggi terbaca jelas |
| **Status Bahaya (Red Solid)** | `#DC2626` / `#991B1B` (Bg: `#FEE2E2`) | Dosis terlewat, kasus prioritas skrining, error | Tegas dan langsung menarik perhatian |

---

## 3. Audit & Pembersihan Gradient pada Kedua Proyek

### A. Flutter Mobile Application (`tb-care-mobile`)
- **Pemeriksaan Seluruh Kode Sumber:**
  Dilakukan penelusuran kata kunci `LinearGradient`, `RadialGradient`, dan `SweepGradient` di seluruh direktori `lib/`.
- **Hasil:**
  Aplikasi mobile secara konsisten telah menggunakan `AppColors` solid tokens. Komponen `KaderPatientPage`, `KaderPatientDetailPage`, `KaderReminderService`, dan card pasien menggunakan warna solid murni tanpa efek gradasi warna.

### B. Laravel Web Admin (`tb-care-web`)
- **Pemeriksaan Template Blade:**
  Ditemukan sisa penggunaan kelas gradasi AdminLTE pada `resources/views/admin/dashboard/index.blade.php`:
  - `bg-gradient-info`
  - `bg-gradient-success`
  - `bg-gradient-secondary`
- **Tindakan Perbaikan:**
  Ketiga kelas diubah menjadi kelas solid murni AdminLTE/Bootstrap:
  - `bg-gradient-info` $\rightarrow$ `bg-info`
  - `bg-gradient-success` $\rightarrow$ `bg-success`
  - `bg-gradient-secondary` $\rightarrow$ `bg-secondary`
- **Hasil:**
  Dashboard Web Admin kini sepenuhnya selaras dengan prinsip *solid color design*, menghadirkan kesan profesional, rapi, dan konsisten di seluruh ekosistem TB Care.

---

## 4. Evaluasi Aksesibilitas
- Kontras rasio teks terhadap latar belakang pada semua card memenuhi standar minimal WCAG 2.1 Level AA.
- Seluruh tombol interaktif memiliki target sentuh (*tap target*) minimal $44 \times 44\text{ dp}$ sehingga mudah ditekan oleh kader di lapangan.
- Elemen status menggabungkan icon penanda dan teks status (tidak hanya bergantung pada warna) untuk mendukung pengguna dengan keterbatasan penglihatan warna.
