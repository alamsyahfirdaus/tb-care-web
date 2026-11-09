# REVIEW DAN EVALUASI CARD MATERI EDUKASI TERBARU
**Aplikasi TB Care — Audit Tampilan Beranda & Komparasi Antar-Role**

Dokumen ini disusun sebagai hasil pemeriksaan teknis, audit UI/UX, dan evaluasi mendalam terhadap widget **Card Materi Edukasi Terbaru** pada aplikasi mobile TB Care sesuai instruksi peninjauan visual dan arsitektural.

> **Catatan Penting Sesuai Instruksi:**  
> Review ini bersifat **evaluatif murni (Audit & Analisis)**. Sesuai instruksi *"JANGAN LANGSUNG MELAKUKAN PERUBAHAN PADA CARD MATERI EDUKASI TERBARU"*, **tidak ada kode widget, API, model, service, rute navigasi, maupun data edukasi yang diubah pada tahap ini**.

---

## 1. Identifikasi Widget & Lokasi Implementasi

Berdasarkan audit menyeluruh terhadap arsitektur Flutter Mobile TB Care (`tb-care-mobile`):

| Aspek | Temuan Lapangan |
|---|---|
| **Screenshot Referensi** | Merupakan tampilan Beranda Pasien (*Selamat Pagi, Alamsyah Firdaus*) dengan card Status Pengobatan, Pengingat Pengobatan, Kontrol Berikutnya, dan Materi Edukasi Terbaru. |
| **File Implementasi** | `lib/main/pasien/home.dart` (Fungsi `_buildLatestEducationSection` dan `_buildEducationCard`, baris 1128–1348). |
| **Halaman Beranda Kader** | `lib/main/kader/home.dart` difokuskan pada ringkasan pengawasan operasional (4 Card Metrik: Total Pasien, Pasien Belum Minum Obat, Sudah Minum Obat, Jadwal Kontrol Hari Ini; serta daftar aksi konfirmasi). Beranda Kader saat ini **tidak memiliki** card materi edukasi beranda. |
| **Model Data** | `EducationItem` dari `package:apk_tb_care/models/patient_home_model.dart` / `EducationService`. |
| **Navigasi Target** | `MateriDetailPage(materialId: item.id)` di `lib/main/pasien/materi_detail.dart`, serta tombol *Lihat Semua* mengarah ke tab Edukasi (`_selectedIndex = 2`). |

---

## 2. Pemeriksaan Mendalam 12 Aspek UI/UX

Berikut adalah hasil audit rinci terhadap 12 parameter yang diminta:

### 1. Struktur dan Layout Card
- **Implementasi Saat Ini:** Kontainer pembungkus section menggunakan `BoxDecoration` putih dengan border halus `0xFFE2E8F0` dan radius sudut 20pt. Di dalamnya terdapat header horizontal (Judul Section & Tombol "Lihat Semua") diikuti `ListView.separated` yang membatasi maksimal 3 materi terbaru (`take(3)`). Masing-masing card item memiliki padding 10pt dengan background abu-abu terang `0xFFF8FAFC`.
- **Evaluasi:** Struktur hirarki card terorganisasi dengan baik secara vertikal. Penggunaan kartu terpisah (`Container` bertingkat) memberikan batas visual yang jelas antara section edukasi dengan section pengingat/kontrol di atasnya.

### 2. Ukuran Thumbnail Materi
- **Implementasi Saat Ini:** Thumbnail menggunakan ukuran tetap `80x80` piksel (rasio 1:1 / bujur sangkar) dengan `ClipRRect(borderRadius: BorderRadius.circular(10))` dan `BoxFit.cover`.
- **Evaluasi:** Rasio 1:1 ini bekerja cukup rapi secara layout grid horizontal, namun memiliki kendala visual pada materi berbentuk **Poster/Infografis TB** (yang umumnya memiliki aspect ratio potret 4:5 atau 9:16) dan **Video** (rasio 16:9). Akibat `BoxFit.cover`, teks infografis di dalam poster sering terpotong di bagian atas dan bawah, sehingga pasien/kader tidak dapat membaca ringkasan infografis secara instan dari beranda.

### 3. Proporsi Gambar dan Teks
- **Implementasi Saat Ini:** Gambar mengambil lebar fixed 80px + margin 12px = 92px. Area teks di kanan dibungkus `Expanded` dan mengambil sisa lebar layar (sekitar 220–240px pada layar standar 360–390dp).
- **Evaluasi:** Proporsi pembagian kolom 1:2.5 ini seimbang untuk layout card ringkasan. Namun, karena tinggi thumbnail fixed 80px, area teks kanan dibatasi agar tidak meluap lebih tinggi dari thumbnail untuk menjaga estetika kartu.

### 4. Keterbacaan Judul Materi
- **Implementasi Saat Ini:** Font `Plus Jakarta Sans`, ukuran 13sp, ketebalan `FontWeight.w700`, warna gelap `0xFF1E293B`, `maxLines: 2`, `overflow: TextOverflow.ellipsis`, `height: 1.25`.
- **Evaluasi:** **Sangat Baik.** Kontras warna teks di atas latar belakang `#F8FAFC` memenuhi standar WCAG AA (>7:1). Pemilihan font Plus Jakarta Sans bold memberikan ketegasan visual yang memudahkan keterbacaan sekilas oleh pengguna.

### 5. Tampilan Kategori Materi (Badge Media)
- **Implementasi Saat Ini:** Menggunakan badge pill kecil dengan ikon + teks (misal: badge hijau untuk Poster/Gambar `0xFFD1FAE5`/`0xFF059669`, badge merah untuk Video `0xFFFEE2E2`/`0xFFDC2626`, dan badge biru muda untuk Artikel).
- **Evaluasi:** **Sangat Baik.** Penggunaan kode warna tematik dan ikon representatif sangat intuitif, membantu pengguna memahami format media (video, artikel, poster gambar) sebelum memutuskan membuka materi.

### 6. Informasi Tanggal Publikasi
- **Implementasi Saat Ini:** Menggunakan ikon kalender kecil 11px `Icons.calendar_today_rounded` warna `0xFF94A3B8` berdampingan dengan teks tanggal format `dd MMMM yyyy` (10sp, `FontWeight.w500`).
- **Evaluasi:** Penempatan tanggal di bagian bawah teks memberikan konteks waktu publikasi materi tanpa mengganggu fokus pada judul. Ukuran font 10sp cukup proporsional sebagai metadata sekunder.

### 7. Konsistensi Jarak Antarcard
- **Implementasi Saat Ini:** Menggunakan `ListView.separated` dengan `separatorBuilder: (_, __) => const SizedBox(height: 12)`.
- **Evaluasi:** Jarak vertikal 12pt antaritem card konsisten dan memberikan pemisah yang lega (breathing room) tanpa membuat halaman beranda menjadi terlalu panjang.

### 8. Konsistensi Warna dan Typography
- **Implementasi Saat Ini:** Menggunakan keluarga font tunggal (*Plus Jakarta Sans*) secara menyeluruh, selaras dengan seluruh komponen aplikasi TB Care. Palet warna menggunakan palet Slate & Neutral (`#0F172A`, `#1E293B`, `#64748B`, `#94A3B8`, `#E2E8F0`, `#F8FAFC`) dengan aksen warna primer.
- **Evaluasi:** **Sangat Konsisten.** Harmoni visual dan tipografi sudah seragam dengan design system TB Care.

### 9. Posisi Tombol "Lihat Semua"
- **Implementasi Saat Ini:** Ditempatkan di baris header card di sebelah kanan judul section. Tombol menggunakan `OutlinedButton` dengan padding compact (`horizontal: 10, vertical: 6`), sudut membulat 8pt, dan border tipis.
- **Evaluasi:** Posisi ini memenuhi konvensi baku UI mobile. Ukuran tap target-nya ringkas namun tetap mudah ditekan.

### 10. Navigasi Ketika Card Diklik
- **Implementasi Saat Ini:** Masing-masing card dibungkus `InkWell` dengan feedback splash membulat. Saat ditekan, sistem menjalankan `Navigator.push` menuju `MateriDetailPage(materialId: item.id)`. Tombol *Lihat Semua* mengalihkan tab aktif langsung ke tab Edukasi.
- **Evaluasi:** Alur navigasi sangat responsif, cepat, dan tidak menghilangkan state halaman beranda saat pengguna kembali (*back*).

### 11. Konsistensi dengan Halaman Daftar Materi Edukasi
- **Implementasi Saat Ini:** Informasi yang ditampilkan pada beranda (gambar, kategori, judul, deskripsi, tanggal) bersumber dari API yang sama (`EducationItem`), sehingga data selalu sinkron dengan tab Edukasi utama.
- **Evaluasi:** Konsistensi data 100% terjaga karena menggunakan model entitas dan service yang sama.

### 12. Responsivitas pada Berbagai Ukuran Layar
- **Implementasi Saat Ini:** Menggunakan kombinasi `Row` dengan `Expanded` untuk teks di sisi thumbnail.
- **Evaluasi:** Pada smartphone dengan lebar layar kecil (< 360dp) atau rasio font sistem Android yang diperbesar (*accessibility display scale*), teks judul 2 baris bersama badge dan deskripsi berpotensi menyebabkan konten teks melebihi tinggi thumbnail 80px, sehingga tampilan terlihat sedikit padat.

---

## 3. Temuan Kritis (Kelebihan & Kekurangan Visual)

### A. Bagian yang Sudah Baik
1. **Desain Modern & Bersih:** Card terlihat elegan dengan sudut membulat halus, border tipis, dan elevasi bayangan yang lembut.
2. **Badge Kategori Informatif:** Pengguna langsung mengetahui tipe konten (Video YouTube, Poster Gambar, atau Artikel Teks).
3. **Empty State Informatif:** Jika materi belum tersedia atau gagal dimuat, widget menampilkan card fallback yang rapi dengan ikon edukasi dan teks penjelas.
4. **Caching Thumbnail Efisien:** Menggunakan `CachedNetworkImage` sehingga thumbnail tidak terus-menerus didownload ulang dari server.

### B. Bagian yang Kurang Rapi & Redundan
1. **Duplikasi Judul pada Baris Deskripsi:**  
   Pada screenshot referensi terlihat jelas:
   - Judul: `Etika Batuk` &rarr; Deskripsi di bawahnya: `Etika Batuk`
   - Judul: `Pentingnya Ventilasi Rumah` &rarr; Deskripsi di bawahnya: `Pentingnya Ventilasi Rumah`  
   Hal ini terjadi karena pada data materi, field `description` diisi sama persis dengan `title`. Menampilkan teks yang identik dua kali secara bertumpuk membuat card terlihat redundan dan menghabiskan ruang vertikal.
2. **Thumbnail 1:1 Mengorbankan Konten Poster:**  
   Poster edukasi kesehatan umumnya dibuat dalam orientasi tegak (*portrait*). Pemotongan bujur sangkar 80x80 dengan `BoxFit.cover` memotong informasi penting pada grafis thumbnail.

---

## 4. Rekomendasi Penyempurnaan untuk Pengembangan Selanjutnya

Apabila di kemudian hari disetujui untuk dilakukan penyempurnaan UI/UX pada card edukasi ini, rekomendasi perbaikan teknis adalah sebagai berikut:

1. **Penyaringan Teks Redundan:**
   Tambahkan logika sederhana agar baris deskripsi hanya ditampilkan jika teks deskripsi **berbeda** dari judul materi:
   ```dart
   final isDescDuplicate = item.description?.trim().toLowerCase() == item.title.trim().toLowerCase();
   if (!isDescDuplicate && item.description != null && item.description!.trim().isNotEmpty) ...[
     // Tampilkan teks deskripsi
   ]
   ```
2. **Penyederhanaan Layout Baris:**
   Dengan menghilangkan deskripsi yang menduplikasi judul, layout card menjadi jauh lebih lapang (*clean*), sehingga judul materi dapat bernapas lebih lega dan tinggi card menjadi lebih proporsional.
3. **Alternatif Tampilan Carousel / Horizontal Scroll:**
   Jika ingin menonjolkan visual materi poster/video edukasi tanpa memakan banyak tinggi halaman beranda, layout kartu dapat dipertimbangkan berbentuk kartu horizontal scroll (carousel) berukuran 16:9 atau poster mini.

---
*Dokumen ini dibuat secara otomatis pada audit teknis TB Care.*
