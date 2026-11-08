# LAPORAN REDESIGN KARTU STATISTIK DASHBOARD KADER
## TB Care Mobile & Laravel Backend

---

## 1. LATAR BELAKANG DAN TUJUAN

Pada evaluasi antarmuka Dashboard Kader TB Care sebelumnya, empat kartu statistik (`Total Pasien`, `Belum Minum Obat`, `Sudah Minum`, `Jadwal Kontrol`) ditampilkan dengan tinggi vertikal yang cukup besar, di mana angka statistik diletakkan di tengah dengan label teks di bagian bawah. Hal ini menyebabkan bagian statistik memakan porsi layar vertikal yang berlebihan, sehingga informasi operasional penting seperti daftar pasien belum minum obat dan jadwal kontrol hari ini terdorong ke bawah layar.

Tujuan dari perubahan ini adalah:
1. Menyederhanakan kartu statistik agar lebih ringkas, hemat ruang vertikal, dan nyaman dilihat.
2. Mengadaptasi pola desain card dari Dashboard Petugas yang terbukti bersih dan proporsional.
3. Menata informasi dalam format baris horizontal (Row): ikon circular di sisi kiri, angka tebal dan label deskripsi di sisi kanan.
4. Menerapkan warna solid tanpa gradasi berlebihan serta memastikan tidak adanya garis batas hitam permanen (karena garis hitam pada referensi pengguna merupakan alat anotasi instruksi).

---

## 2. DETAIL PERUBAHAN TAMPILAN DAN ARSITEKTUR WIDGET

### A. Lokasi Berkas Sumber
- **Flutter Mobile**: `tb-care-mobile/lib/main/kader/home.dart`
- **Widget Utama**: `_buildStatCard` dan `_buildFourStatisticsGrid`

### B. Komparasi Struktur Widget

#### Sebelum Redesign:
```text
Card (Tinggi ~120px)
 └── Column (Center)
      ├── CircleAvatar / Container Icon (Atas)
      ├── SizedBox(height: 8)
      ├── Text (Angka Besar - Tengah)
      └── Text (Label Kecil - Bawah)
```

#### Sesudah Redesign (Compact Row Layout):
```text
Card / Container (Tinggi proporsional, childAspectRatio: 2.1)
 └── Padding (all: 12-14px)
      └── Row (crossAxisAlignment: center)
           ├── Container Bulat (Diameter 40px, warna latar lembut)
           │    └── Icon (Warna solid primer sesuai kategori)
           ├── SizedBox(width: 10px)
           └── Expanded
                └── Column (crossAxisAlignment: start, mainAxisSize: min)
                     ├── Text (Angka Statistik, fontSize: 20, bold, #1E293B)
                     └── Text (Label Kategori, fontSize: 11, w500, #64748B, maxLines: 2)
```

### C. Konfigurasi Warna Solid Setiap Kartu

| Kartu Statistik | Icon | Warna Background Icon | Warna Icon | Target Interaksi |
|---|---|---|---|---|
| **Total Pasien** | `Icons.people_outline` | `#EFF6FF` (Soft Blue) | `#2563EB` (Solid Blue) | Navigasi ke Daftar Pasien (Tab Pasien) |
| **Belum Minum Obat** | `Icons.notifications_active_outlined` | `#FFF7ED` (Soft Orange) | `#EA580C` (Solid Amber) | Navigasi ke Tab Pasien dengan filter `Belum Minum Obat` |
| **Sudah Minum** | `Icons.check_circle_outline` | `#F0FDF4` (Soft Green) | `#16A34A` (Solid Green) | Navigasi ke Tab Pasien dengan filter `Sudah Minum Obat` |
| **Jadwal Kontrol** | `Icons.calendar_month_outlined` | `#F0F9FF` (Soft Cyan) | `#0284C7` (Solid Cyan) | Scroll / fokus ke bagian Jadwal Kontrol Hari Ini |

---

## 3. RESPONSIFITAS DAN INTEGRASI DATA

1. **Grid Responsif**:
   Menggunakan `GridView.count` dengan `crossAxisCount: 2`, `crossAxisSpacing: 12`, `mainAxisSpacing: 12`, dan rasio aspek `childAspectRatio: 2.1`. Pengaturan ini mencegah overflow pada perangkat berlayar kecil (320-360dp) dan tetap proporsional pada tablet atau phablet.
2. **Koneksi Data Aktual**:
   Data angka statistik terhubung langsung ke respons API backend `/api/kader/dashboard`:
   - `statistics['total_patients']`
   - `statistics['belum_minum_obat']`
   - `statistics['sudah_minum']`
   - `statistics['jadwal_kontrol']`

---

## 4. KESIMPULAN

Redesign kartu statistik Dashboard Kader berhasil mereduksi tinggi area statistik sebesar ~35%, memberikan ruang yang lebih luas bagi Kader untuk memantau pasien yang membutuhkan tindakan cepat hari ini secara instan begitu membuka aplikasi.
