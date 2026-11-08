# LAPORAN PENYEMPURNAAN CARD DASHBOARD KADER
## Pasien Belum Minum Obat & Jadwal Kontrol Hari Ini

---

## 1. PENDAHULUAN

Dashboard Kader memiliki dua bagian daftar operasional utama hari ini:
1. **Pasien Belum Minum Obat**: Menampilkan pasien binaan yang belum mengonfirmasi konsumsi obat harian.
2. **Jadwal Kontrol Hari Ini**: Menampilkan pasien binaan yang memiliki agenda kunjungan kontrol ke Puskesmas pada hari ini.

Sebelumnya, kedua card ini memuat elemen yang redundan (keterangan berulang, badge status yang tidak informatif, ikon waktu berlebih, serta nama puskesmas yang sudah diketahui). Selain itu, tombol aksi pada card sebelumnya bertuliskan `Lihat Pasien` yang mengharuskan kader berpindah halaman detail terlebih dahulu hanya untuk mengirimkan pengingat.

---

## 2. DETAIL PERUBAHAN PADA CARD "PASIEN BELUM MINUM OBAT"

### A. Penghapusan Keterangan Redundan (Perubahan 2)
- **Kondisi Lama**: Terdapat banner teks berwarna oranye bertuliskan `Belum konfirmasi minum obat`. Padahal seluruh pasien yang masuk dalam daftar ini secara eksplisit berada di bawah heading section "Pasien Belum Minum Obat".
- **Kondisi Baru**: Teks tersebut dihapus secara menyeluruh sehingga tinggi card berkurang drastis dan tampilan menjadi lebih bersih.

### B. Penggantian Informasi Desa Menjadi NIK (Perubahan 4)
- **Kondisi Lama**: Di bawah nama pasien tertulis nama desa (misal `Desa Setiamulya, Tamansari`).
- **Kondisi Baru**: Menampilkan identitas tunggal pasien yang valid: `NIK: 3278050101880013`. Kader dapat dengan cepat mencocokkan pasien tanpa kebingungan jika ada nama yang mirip.

### C. Pembersihan Ikon yang Tidak Diperlukan (Perubahan 5)
- **Kondisi Lama**: Kotak jadwal minum obat memuat ikon jam kecil `Icons.access_time` di depan teks.
- **Kondisi Baru**: Ikon dihapus; format informasi dibuat langsung dan elegan: `Jadwal Minum Obat: 04:30 WIB`.

### D. Transformasi Tombol Aksi Langsung "Ingatkan Minum Obat" (Perubahan 3)
- **Kondisi Lama**: Tombol `Lihat Pasien` (berpindah ke halaman detail pasien).
- **Kondisi Baru**: Tombol `Ingatkan Minum Obat` bertipe `OutlinedButton` dengan ikon `Icons.notifications_active_outlined`.
- **Alur Kerja**: Saat diketuk, tombol langsung memanggil `KaderReminderService.showReminderBottomSheet(...)` dengan tipe pengingat `medication` yang terisi template pesan pengingat minum obat siap kirim.
- **Akses Detail**: Kader tetap dapat membuka halaman Detail Data Pasien kapan saja dengan mengetuk area header informasi pasien (nama dan avatar).

---

## 3. DETAIL PERUBAHAN PADA CARD "JADWAL KONTROL HARI INI"

### A. Penggantian Informasi Desa Menjadi NIK (Perubahan 4 & Backend API)
- Card menampilkan `NIK: <nomor_nik>` di bawah nama pasien.
- **Laravel Backend**: Pada berkas `app/Http/Controllers/Api/KaderController.php`, array `$todayControlSchedules` kini menyertakan field `'nik' => $p->nik ?? '-'` dan `'patient_nik' => $p->nik ?? '-'` langsung pada level root objek, menjamin NIK tampil akurat tanpa dependensi relasi bersarang.

### B. Penghapusan Badge Status "Terjadwal" (Perubahan 6)
- **Kondisi Lama**: Terdapat chip status `Terjadwal` di pojok kanan atas card.
- **Kondisi Baru**: Badge tersebut dihapus karena seluruh item dalam section ini adalah jadwal aktif hari ini.

### C. Penyederhanaan Keterangan dan Penghapusan Elemen Redundan (Perubahan 8)
- **Kondisi Lama**: Menampilkan ikon jam, jam kontrol, dan nama Puskesmas (misal `Puskesmas Tamansari`). Karena Kader dan Pasien berada di wilayah kerja Puskesmas yang sama, penyebutan nama puskesmas bersifat berulang dan tidak diperlukan.
- **Kondisi Baru**: Menampilkan keterangan ringkas dan jelas: `Waktu Kontrol: 09:00 WIB` tanpa ikon dekoratif yang memakan tempat.

### D. Transformasi Tombol Aksi Langsung "Ingatkan Kontrol" (Perubahan 7 & 10)
- **Kondisi Lama**: Tombol `Lihat Pasien`.
- **Kondisi Baru**: Tombol `Ingatkan Kontrol` dengan ikon `Icons.calendar_month_outlined`.
- **Alur Kerja**: Saat diketuk, langsung membuka bottom sheet pengingat kunjungan dengan tipe `visit` dan template pesan otomatis kontrol faskes.
- **Akses Detail**: Dapat membuka detail pasien melalui ketukan pada header pasien.

---

## 4. RINGKASAN FILE YANG DIUBAH

1. `tb-care-mobile/lib/main/kader/home.dart`:
   - Penyesuaian `_buildUnconfirmedPatientCard()`
   - Penyesuaian `_buildControlScheduleCard()`
   - Integrasi `KaderReminderService.showReminderBottomSheet()`
2. `tb-care-web/app/Http/Controllers/Api/KaderController.php`:
   - Penambahan field NIK pada query koleksi `$todayControlSchedules`.
