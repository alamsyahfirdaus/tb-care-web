# LAPORAN PENYEMPURNAAN FILTER DAFTAR PASIEN KADER
## Tiga Kategori Status Kepatuhan Harian

---

## 1. LATAR BELAKANG DAN MASALAH

Pada halaman **Daftar Pasien** (Tab Pasien pada Role Kader), antarmuka sebelumnya hanya menyajikan 2 filter:
1. `Semua`
2. `Perlu Pengingat (Belum Minum Obat)`

Keterbatasan ini menyulitkan Kader ketika ingin melakukan verifikasi terhadap pasien yang **sudah** minum obat pada hari ini, atau ketika ingin membandingkan rasio pasien yang patuh versus belum patuh tanpa harus membaca daftar satu per satu. Selain itu, chip filter sebelumnya rentan terhadap pemotongan teks (*text truncation*) jika jumlah filter bertambah tanpa scroll horizontal.

---

## 2. SPESIFIKASI TIGA KATEGORI FILTER

Kini filter Daftar Pasien ditingkatkan menjadi 3 kategori eksplisit:

| Label Filter | Nilai Internal | Definisi & Kriteria Penyaringan |
|---|---|---|
| **Semua** | `'all'` | Menampilkan seluruh pasien TB yang berada di bawah wilayah kerja binaan Kader (tanpa filter status minum obat). |
| **Sudah Minum Obat** | `'taken'` | Menampilkan pasien yang sudah mencatat konsumsi obat hari ini (`today_medication_taken == true`). |
| **Belum Minum Obat** | `'not_taken'` | Menampilkan pasien yang belum mencatat atau belum mengonfirmasi konsumsi obat hari ini (`today_medication_taken == false`). |

---

## 3. IMPLEMENTASI UI & LOGIKA TEKNIS

### A. Komponen Horizontal Scrollable Filter
Berkas: `tb-care-mobile/lib/main/kader/patient.dart`

```dart
Widget _buildFilterChips() {
  return SingleChildScrollView(
    scrollDirection: Axis.horizontal,
    physics: const BouncingScrollPhysics(),
    padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
    child: Row(
      children: [
        _buildFilterItem('all', 'Semua'),
        const SizedBox(width: 8),
        _buildFilterItem('taken', 'Sudah Minum Obat'),
        const SizedBox(width: 8),
        _buildFilterItem('not_taken', 'Belum Minum Obat'),
      ],
    ),
  );
}
```

### B. Penyaringan Data Lokal Cepat (Instant Filtering)
Saat filter diketuk, daftar pasien disaring secara reaktif tanpa perlu *reload* jaringan ulang yang memperlambat kinerja:

```dart
List<dynamic> get _filteredPatients {
  return _patients.where((p) {
    // 1. Filter Pencarian Teks (Nama atau NIK)
    final matchesQuery = _searchQuery.isEmpty ||
        (p['name']?.toString().toLowerCase().contains(_searchQuery.toLowerCase()) ?? false) ||
        (p['nik']?.toString().contains(_searchQuery) ?? false);

    if (!matchesQuery) return false;

    // 2. Filter Status Kepatuhan 3 Kategori
    if (_selectedFilter == 'taken') {
      return p['today_medication_taken'] == true;
    } else if (_selectedFilter == 'not_taken') {
      return p['today_medication_taken'] == false;
    }

    return true; // 'all'
  }).toList();
}
```

### C. Alur Navigasi Terintegrasi dari Dashboard
Ketika Kader mengetuk kartu statistik di Dashboard:
- Mengetuk kartu **Belum Minum Obat** (angka oranye) akan mengarahkan Kader ke Tab Pasien dengan `initialFilter: 'not_taken'`.
- Mengetuk kartu **Sudah Minum** (angka hijau) akan mengarahkan Kader ke Tab Pasien dengan `initialFilter: 'taken'`.
- Mengetuk kartu **Total Pasien** mengarahkan ke filter `'all'`.
- Melakukan gesture *Pull-to-Refresh* pada daftar pasien memuat ulang data terbaru dan mengembalikan filter ke `'all'`.

---

## 4. KESIMPULAN

Penerapan 3 filter (`Semua`, `Sudah Minum Obat`, `Belum Minum Obat`) memberikan kejelasan alur kerja monitoring bagi Kader. Tidak ada risiko overflow pada resolusi layar berapa pun karena pembungkus `SingleChildScrollView` horizontal, serta respon UI yang instan meningkatkan efisiensi kerja lapangan Kader.
