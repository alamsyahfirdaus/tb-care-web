# LAPORAN REDESIGN DASHBOARD KADER TB CARE

**Tanggal Implementasi:** 8 November 2026  
**Tim Pengembang:** Senior Flutter Developer & Senior Laravel Backend Developer  
**Status:** Selesai, Teruji 100%, Lolos Analisis Statis & Regresi

---

## 1. Ringkasan Eksekutif

Penyederhanaan Dashboard Kader dilakukan untuk menyajikan informasi inti yang paling dibutuhkan oleh Kader Kesehatan di lapangan tanpa menimbulkan beban kognitif berlebih (cognitive overload). Berdasarkan audit alur kerja kader TB di Puskesmas, dua informasi paling krusial yang memerlukan perhatian segera setiap hari adalah:
1. **Total Pasien**: Jumlah pasien pengobatan aktif (`Berjalan`) yang menjadi tanggung jawab binaan kader.
2. **Perlu Pengingat**: Jumlah pasien yang belum mengonfirmasi minum obat pada jadwal hari berjalan ATAU memiliki jadwal kontrol Puskesmas mendatang.

---

## 2. Perubahan Desain & Layout (Sebelum vs Sesudah)

### A. Kondisi Sebelum Redesign
Dashboard sebelumnya menampilkan 4 card metrik dalam grid 2x2:
- Total Pasien
- Pengobatan Aktif (Redundan dengan Total Pasien karena seluruh pasien binaan berstatus Berjalan)
- Perlu Pengingat
- Kunjungan Mendatang (Menimbulkan duplikasi fokus penanganan pengingat)

### B. Kondisi Sesudah Redesign
- Menampilkan tepat **2 card metrik** dalam 1 baris horizontal (`Row` dengan `Expanded`), hemat ruang, rapi, dan responsif.
- Menggunakan palet warna solid konsisten dengan identitas visual TB Care (tanpa gradien mencolok):
  - Card 1 (`Total Pasien`): Background solid putih, icon biru lembut, tipografi Plus Jakarta Sans tebal.
  - Card 2 (`Perlu Pengingat`): Background solid putih, icon amber/oranye lembut untuk menandakan urgensi tindak lanjut.
- Card `Pengobatan Aktif` dan `Kunjungan Mendatang` dihapus dari tampilan visual tanpa merusak kalkulasi database backend.

```text
┌───────────────────────────────────┐ ┌───────────────────────────────────┐
│ [Icon Pasien]                     │ │ [Icon Lonceng]                    │
│ Total Pasien                      │ │ Perlu Pengingat                   │
│ 7                                 │ │ 4                                 │
└───────────────────────────────────┘ └───────────────────────────────────┘
```

---

## 3. Logika Backend & Integrasi API

### A. Endpoint
`GET /api/kader/dashboard`

### B. Aturan Perhitungan Unik (Deduplikasi Pasien)
Satu pasien hanya dihitung **1 kali** dalam metrik `Perlu Pengingat` meskipun memerlukan pengingat minum obat sekaligus kontrol Puskesmas:
```php
$needsMedicationReminder = !$todayMedicationTaken;
$needsVisitReminder = $hasUpcomingVisit;
$needsReminder = $needsMedicationReminder || $needsVisitReminder;

if ($needsReminder) {
    $needRemindersCount++; // Pasien hanya dihitung tepat satu kali
}
```

### C. Respons JSON Terstandarisasi
```json
{
  "success": true,
  "message": "Dashboard kader berhasil diambil.",
  "statistics": {
    "total_patients": 7,
    "active_treatments": 7,
    "need_reminders": 4,
    "upcoming_visits": 0
  },
  "patients": [...]
}
```

---

## 4. Implementasi Flutter Mobile (`tb-care-mobile`)

File yang diperbarui: `lib/main/kader/home.dart`
- Struktur widget `_buildStatCard` dioptimalkan untuk performa tinggi, bebas overflow pada perangkat layar 4.7 inci hingga tablet.
- AppBar menggunakan `TBAppBar` dengan badge live synchronization untuk unread konsultasi chat (`showChatButton: true`).
- RefreshIndicator mendukung pull-to-refresh langsung untuk sinkronisasi instan angka dashboard setelah kader mengirim pengingat atau melakukan verifikasi.

---

## 5. Hasil Verifikasi Pengujian

| Komponen Uji | Target Verifikasi | Hasil |
|---|---|---|
| Card Grid | Tepat 2 card metrik dalam 1 baris | PASS |
| Deduplikasi | Pasien butuh 2 pengingat hanya dihitung 1x | PASS |
| Responsivitas | Tidak ada overflow di orientasi potret dan lanskap | PASS |
| Status Aktif | Hanya pasien berstatus Berjalan yang dihitung | PASS |
| Analisis Statis | `flutter analyze` 0 issue | PASS |
