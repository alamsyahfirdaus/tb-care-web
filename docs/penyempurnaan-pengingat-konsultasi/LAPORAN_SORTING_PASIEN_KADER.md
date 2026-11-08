# LAPORAN PENGEMBANGAN: RESTORE PENGURUTAN DAFTAR PASIEN BERDASARKAN WAKTU
**Sistem Aplikasi TB Care (Role Kader Dashboard & Mobile Integration)**  
*Dokumen Teknis & Implementasi Arsitektur*

---

## 1. Konteks & Koreksi Kebutuhan Operasional
Daftar pasien pada Dashboard Role Kader memiliki fungsi pemantauan harian aktif (*active operational monitoring*). 

Dalam alur kerja aktual kader kesehatan di lapangan:
- Pasien yang memiliki jadwal minum obat lebih awal (misalnya pukul 05:00 WIB atau 06:00 WIB) membutuhkan pengingat dan pemantauan prioritas terlebih dahulu sebelum pasien yang jadwal minum obatnya siang atau malam hari.
- Hal serupa berlaku untuk jadwal kontrol Puskesmas: pasien yang jadwal kunjungannya paling pagi (misalnya 08:00 WIB) harus diprioritaskan diingatkan sebelum pasien dengan jadwal siang (13:00 WIB).

Oleh karena itu, pengurutan alfabetis A–Z pada kedua daftar di Dashboard Kader **dikembalikan ke pengurutan berbasis waktu (*chronological time-based sorting*) secara ascending (paling awal ke paling akhir)**.

---

## 2. Ketentuan Pengurutan Berdasarkan Waktu

### A. Bagian "Pasien Belum Minum Obat"
1. **Dasar Pengurutan:** `medication_time` secara ascending (paling awal ke paling akhir).
2. **Posisi Teratas:** Pasien dengan jadwal minum obat tercepat hari ini berada di posisi paling atas (misal `05:00 WIB`, disusul `06:00 WIB`, lalu `07:00 WIB`).
3. **Determinisme (Tie-breaker):** Jika terdapat pasien dengan jam minum obat yang persis sama, diurutkan deterministik berdasarkan `id` atau `nik`.
4. **Data Tanpa Waktu / Invalid:** Ditempatkan di bagian paling akhir (`99:99`).
5. **Format Tampilan:** Informasi jam minum obat tetap ditampilkan konsisten `HH:mm WIB` dan nama pasien tetap ditampilkan secara wajar (Title Case).

Contoh:
| Urutan | Nama Pasien | Jam Minum Obat | Keterangan |
| :---: | :--- | :---: | :--- |
| **1** | Yopi Mulyawan | `06:00 WIB` | Tercepat, paling atas |
| **2** | Dede Sumiati | `07:00 WIB` | Urutan kedua |
| **3** | Ahmad Dahlan | `08:00 WIB` | Urutan ketiga |
| **4** | Imat | `09:00 WIB` | Urutan keempat |

### B. Bagian "Jadwal Kontrol Hari Ini"
1. **Dasar Pengurutan:** Waktu kontrol (`visit_time` / kombinasi tanggal dan jam kontrol secara kronologis).
2. **Posisi Teratas:** Jadwal kontrol terawal hari ini yang belum lewat waktu pelaksanaannya.
3. **Data Tanpa Waktu / Invalid:** Ditempatkan di bagian paling akhir.

---

## 3. Implementasi Kode

### A. Laravel Backend (`KaderController.php`)
Di dalam method `dashboard()` pada [KaderController.php](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Controllers/Api/KaderController.php#L330-L360):

```php
// Urutkan daftar Pasien Belum Minum Obat berdasarkan waktu minum obat paling awal (ascending)
usort($unconfirmedPatients, function ($a, $b) {
    $timeA = !empty($a['medication_time']) ? $a['medication_time'] : '99:99';
    $timeB = !empty($b['medication_time']) ? $b['medication_time'] : '99:99';
    $cmp = strcmp($timeA, $timeB);
    if ($cmp !== 0) {
        return $cmp;
    }
    return strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
});

// Urutkan daftar Jadwal Kontrol Hari Ini berdasarkan waktu kontrol paling awal (ascending)
usort($todayControlSchedules, function ($a, $b) {
    $dateA = $a['visit_date'] ?? '';
    $timeA = !empty($a['visit_time']) ? $a['visit_time'] : '99:99';
    $dtA = trim($dateA . ' ' . $timeA);

    $dateB = $b['visit_date'] ?? '';
    $timeB = !empty($b['visit_time']) ? $b['visit_time'] : '99:99';
    $dtB = trim($dateB . ' ' . $timeB);

    $cmp = strcmp($dtA, $dtB);
    if ($cmp !== 0) {
        return $cmp;
    }
    return strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
});
```

### B. Flutter Mobile (`home.dart`)
Pada method `_fetchDashboardData()` di [home.dart](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/kader/home.dart#L114-L160):

```dart
final rawUnconfirmed = List<dynamic>.from(
  body['unconfirmed_patients'] ??
  body['unconfirmed_patients_today'] ??
  [],
);
// Urutkan berdasarkan jadwal minum obat paling awal (ascending), bukan nama A-Z
rawUnconfirmed.sort((a, b) {
  final timeA = (a['medication_time'] ?? '99:99').toString();
  final timeB = (b['medication_time'] ?? '99:99').toString();
  final cmp = timeA.compareTo(timeB);
  if (cmp != 0) return cmp;
  final idA = (a['id'] ?? a['patient_id'] ?? '').toString();
  final idB = (b['id'] ?? b['patient_id'] ?? '').toString();
  return idA.compareTo(idB);
});
_unconfirmedPatients = rawUnconfirmed;

final rawSchedules = List<dynamic>.from(
  body['today_control_schedules'] ??
  body['control_schedules_today'] ??
  [],
);
final now = DateTime.now();
final activeSchedules = rawSchedules.where((s) {
  if (s is! Map) return true;
  final dateStr = (s['visit_date'] ?? '').toString();
  final timeStr = (s['visit_time'] ?? '08:00').toString();
  try {
    final d = DateTime.parse(dateStr.split('T').first);
    final parts = timeStr.split(':');
    final h = parts.isNotEmpty ? int.parse(parts[0]) : 8;
    final m = parts.length > 1 ? int.parse(parts[1]) : 0;
    final scheduleDt = DateTime(d.year, d.month, d.day, h, m);
    return !now.isAfter(scheduleDt);
  } catch (_) {
    return true;
  }
}).toList();

// Urutkan jadwal kontrol berdasarkan waktu kontrol paling awal (ascending)
activeSchedules.sort((a, b) {
  final dateA = (a['visit_date'] ?? '').toString();
  final timeA = (a['visit_time'] ?? '99:99').toString();
  final dtA = '$dateA $timeA'.trim();

  final dateB = (b['visit_date'] ?? '').toString();
  final timeB = (b['visit_time'] ?? '99:99').toString();
  final dtB = '$dateB $timeB'.trim();

  final cmp = dtA.compareTo(dtB);
  if (cmp != 0) return cmp;
  final idA = (a['id'] ?? a['patient_id'] ?? '').toString();
  final idB = (b['id'] ?? b['patient_id'] ?? '').toString();
  return idA.compareTo(idB);
});
_todayControlSchedules = activeSchedules;
```

---

## 4. Hasil Pengujian Otomatis

### Pengujian Backend (`KaderDashboardRedesignTest.php`)
```text
✓ unconfirmed patients are sorted by medication time ascending
✓ today control schedules are sorted by visit time ascending
Tests: 6 passed (0.71s)
```

### Pengujian Mobile (`kader_pengingat_dan_konsultasi_refinement_test.dart`)
```text
✓ Pasien Belum Minum Obat is sorted by medication time ascending (earliest first, NOT A-Z)
  Yopi Mulyawan (06:00 WIB, top) < Dede Sumiati (07:00 WIB) < Ahmad Dahlan (08:00 WIB) < Imat (09:00 WIB, bottom)

✓ Jadwal Kontrol Hari Ini is sorted by visit time ascending (earliest first, NOT A-Z)
  Yopi Mulyawan (20:30 WIB, top) < Dedi Mulyana (21:15 WIB) < Ahmad Dahlan (22:00 WIB) < Dede Sumiati (23:30 WIB, bottom)

Tests: 12 passed (3.2s)
```

## 5. Kesimpulan
Pengurutan Pasien Belum Minum Obat dan Jadwal Kontrol Hari Ini pada Dashboard Kader telah sepenuhnya dikembalikan ke pengurutan berbasis waktu (*time-based ascending*). Alur pemantauan kader kembali selaras dengan prioritas jam pelaksanaan pengobatan pasien di lapangan.
