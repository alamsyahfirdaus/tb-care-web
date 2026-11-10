# REDESIGN MENU PENGOBATAN ROLE PASIEN (TB CARE)

## 1. Ringkasan Eksekutif

Menu **Pengobatan** pada aplikasi mobile TB Care khusus **Role Pasien** telah diredesain secara menyeluruh untuk menyatukan informasi perjalanan pengobatan dan catatan kepatuhan minum obat dalam satu kesatuan navigasi yang ergonomis, intuitif, dan responsif.

Sebelum perbaikan, fitur **Riwayat Minum Obat** diakses melalui navigasi bertumpuk (*push navigation*) dari tombol menu pada halaman pengobatan. Desain ini membingungkan pasien karena mengharuskan pasien keluar-masuk halaman untuk melihat kepatuhan minum obat mereka. Selain itu, badge status pada riwayat minum obat memiliki dekorasi ikon kecil yang padat dan status verifikasi tidak mencerminkan fakta verifikasi sebenarnya di lapangan.

Melalui redesain ini:
1. Halaman **Pengobatan** mengadopsi navigasi **2 Tab Utama**:
   - **Tab 1: Informasi Pengobatan**
   - **Tab 2: Riwayat Minum Obat**
2. Fitur Riwayat Minum Obat disematkan langsung (*embedded*) di dalam Tab 2 tanpa membuka halaman baru.
3. Card menu navigasi redundan "Riwayat Minum Obat" di Tab 1 telah dihilangkan.
4. Badge status riwayat minum obat disederhanakan menjadi **pill badge teks-saja (tanpa ikon)** dengan tipografi dan warna yang bersih (*clean & modern*).
5. Seluruh perubahan hanya berlaku untuk Role Pasien tanpa mengubah struktur menu dan hak akses Role Kader, Petugas, maupun Admin.

---

## 2. Struktur Navigasi Baru

```
Halaman Pengobatan (Role Pasien)
│
├── App Bar: TBAppBar
│   ├── Judul: "Pengobatan"
│   └── TabBar (2 Tab):
│       ├── [1] Informasi Pengobatan
│       └── [2] Riwayat Minum Obat
│
└── Body: TabBarView
    │
    ├── Tab 1: Informasi Pengobatan
    │   ├── Card Utama Status Pengobatan (Kategori, Hari ke-x, Tanggal Mulai & Selesai)
    │   ├── Tombol Aksi: "Tandai Sudah Minum Obat" / "Perbarui Bukti"
    │   ├── Card Jadwal Pengingat Minum Obat ("Ubah Waktu" & "Uji Alarm")
    │   └── Menu Tindakan Lainnya:
    │       ├── Riwayat Pengobatan (Daftar episode pengobatan & obat yang diresepkan)
    │       └── Anggota Serumah (Daftar kontak serumah)
    │
    └── Tab 2: Riwayat Minum Obat (Embedded)
        ├── Card Ringkasan Kepatuhan (Terjadwal, Sudah Minum, Terlewat, Kepatuhan %)
        ├── Header Riwayat Kronologis
        └── Daftar Card Minum Obat Harian (Terbaru ke Terlama):
            ├── Badge Status Teks Saja ([Sudah Minum], [Terverifikasi], [Terlambat], [Belum Konfirmasi], [Terlewat])
            ├── Tanggal dan Jadwal Dosis
            ├── Bukti Foto (jika ada) & Catatan Pasien
            └── Catatan Verifikasi Kader (jika ada)
```

---

## 3. Detail Perubahan Komponen UI/UX

### A. TabBar dan TabBarView pada `TreatmentPage`
- **File**: `tb-care-mobile/lib/main/pasien/treatment.dart`
- Menambahkan `SingleTickerProviderStateMixin` dan `TabController` dengan panjang 2.
- `TBAppBar` mendukung properti `bottom: TabBar(...)` dengan styling:
  - Font: Plus Jakarta Sans, Semi-Bold (14px).
  - Indikator: Garis putih tegas (3px) dengan sudut melengkung.
  - Warna label terpilih: Putih pekat (`#FFFFFF`).
  - Warna label tidak terpilih: Putih transparan (`#FFFFFF` opacity 0.75).
- Menghubungkan Tab 2 langsung ke widget `MedicationHistoryPage` dengan parameter:
  - `patientId`: ID pasien aktif.
  - `patientTreatmentId`: ID episode pengobatan aktif (`_currentTreatment?['id']`).
  - `embedInTab: true` (merender konten tanpa Scaffold & AppBar tambahan).

### B. Penghapusan Card Redundan di Tab 1
- Pada bagian `_buildHistorySection()`, item navigasi:
  ```dart
  // DIHAPUS:
  _buildActionCard(
    icon: Icons.access_time_rounded,
    title: 'Riwayat Minum Obat',
    subtitle: 'Lihat catatan bukti minum obat',
    onTap: () => Navigator.push(...),
  )
  ```
  telah dihilangkan. Tab 1 kini hanya memuat:
  - `Riwayat Pengobatan`
  - `Anggota Serumah`

### C. Redesain Badge Status Riwayat Minum Obat
- **File**: `tb-care-mobile/lib/main/pasien/history.dart`
- Ikon kecil (seperti centang, silang, jam, tanda seru) di dalam pill badge status dihilangkan seluruhnya.
- Menggunakan komponen `_buildStatusBadge({required String label, required Color backgroundColor, required Color borderColor, required Color textColor})`:
  - Padding: horizontal 10px, vertical 4px.
  - Border Radius: 8px.
  - Border Width: 1px.
  - Tipografi: Plus Jakarta Sans, 11px, font weight w600.
- Skema warna status:
  - **Sudah Minum**: Background `#ECFDF5`, Border `#A7F3D0`, Teks `#065F46` (Hijau Segar).
  - **Terverifikasi**: Background `#ECFDF5`, Border `#A7F3D0`, Teks `#065F46` (Hijau Segar).
  - **Terlambat**: Background `#FEF2F2`, Border `#FCA5A5`, Teks `#B91C1C` (Merah Halus).
  - **Belum Konfirmasi**: Background `#FFFBEB`, Border `#FDE68A`, Teks `#B45309` (Kuning Amber).
  - **Terlewat**: Background `#FEF2F2`, Border `#FECACA`, Teks `#DC2626` (Merah).
  - **Belum Jatuh Tempo**: Background `#F1F5F9`, Border `#CBD5E1`, Teks `#475569` (Abu-abu Slate).

---

## 4. File Source Code yang Dimodifikasi

| No | File Path | Modifikasi Utama |
|---|---|---|
| 1 | `tb-care-mobile/lib/main/pasien/treatment.dart` | Penambahan 2 tab pada App Bar, penyematan TabBarView, dan penghapusan card riwayat minum obat dari Tab 1. |
| 2 | `tb-care-mobile/lib/main/pasien/history.dart` | Parameter `embedInTab` dan `patientTreatmentId`, refaktorisasi badge status teks-saja tanpa icon, card `Belum Konfirmasi`. |
| 3 | `tb-care-mobile/test/patient_treatment_tab_and_medication_test.dart` | Unit & widget tests verifikasi 2 tab, absennya card redundan di Tab 1, dan model obat. |

---

## 5. Hasil Pengujian

1. **Flutter Analyze**:
   ```text
   Analyzing tb-care-mobile...
   No issues found! (ran in 5.9s)
   ```
2. **Flutter Widget Test**:
   ```text
   00:01 +3: All tests passed!
   - TreatmentPage contains 2 Tabs: Informasi Pengobatan and Riwayat Minum Obat (PASS)
   - TreatmentPage Tab 1 does not contain Riwayat Minum Obat action card (PASS)
   - PatientMedication model parses correctly with dosage instructions (PASS)
   ```
3. **Pemeriksaan Kompatibilitas Role**:
   - Role Kader, Petugas, dan Admin tidak terpengaruh karena `TreatmentPage` ini eksklusif untuk Role Pasien (`/pasien/treatment`).
   - Pada `MedicationHistoryPage`, saat dibuka mandiri oleh Petugas atau Admin melalui rute riwayat eksternal (`isStaff: true`), `embedInTab: false` tetap memunculkan AppBar dan Scaffold standar secara sempurna.
