# LAPORAN PENGEMBANGAN: PENYEMBUNYIAN JADWAL KONTROL YANG SUDAH TERLEWAT
**Sistem Aplikasi TB Care (Dashboard Kader & Detail Pasien)**  
*Dokumen Logika Query & Integritas Data Pasien*

---

## 1. Latar Belakang & Masalah
Jadwal kontrol ke Puskesmas yang tanggal dan jam pelaksanaannya sudah lewat (misalnya jadwal kontrol hari ini pukul 08:00 WIB, tetapi waktu saat ini sudah pukul 10:00 WIB) sebelumnya masih tetap tampil pada:
- **Dashboard Kader:** Bagian *"Jadwal Kontrol Hari Ini"*.
- **Detail Pasien:** Bagian *"Jadwal Kontrol Puskesmas Berikutnya"*.

Hal ini membingungkan kader kesehatan karena kader mengira pasien tersebut masih memiliki jadwal kontrol mendatang hari ini.

### Prinsip Utama Penanganan:
> **PENTING:** Data riwayat jadwal kunjungan di database **TIDAK BOLEH DIHAPUS** dan status kunjungan di database **TIDAK BOLEH DIUBAH** secara sepihak. Penyembunyian hanya dilakukan pada level tampilan (*display/query filter*) agar kader hanya melihat jadwal yang masih aktif dan relevan untuk diingatkan.

---

## 2. Kriteria & Logika Filter Waktu

Sebuah jadwal kontrol disembunyikan dari daftar jadwal kontrol aktif jika:
$$\text{now}(\text{Asia/Jakarta}) > (\text{visit\_date} + \text{visit\_time})$$

- Jika tanggal kontrol adalah **hari ini**, sistem memeriksa apakah waktu jam pelaksanaan kontrol (`visit_time`) sudah lebih awal dari waktu saat ini. Jika sudah lewat, jadwal tidak ditampilkan pada daftar aktif.
- Jika tanggal kontrol sudah **di masa lalu** ($< \text{hari ini}$), jadwal otomatis tidak dimasukkan ke dalam jadwal kontrol aktif mendatang.
- Jadwal yang memiliki status pembatalan (`Dibatalkan`, `Batal`, `Selesai`, `Hadir`) tetap dikecualikan dari jadwal kontrol aktif.

---

## 3. Implementasi Kode

### A. Laravel Backend (`KaderController.php`)
Di method `dashboard()` pada [KaderController.php](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Controllers/Api/KaderController.php#L191-L245):

```php
// Cari jadwal kontrol khusus hari ini (hanya yang belum lewat waktu pelaksanaan)
foreach ($activeTreatment->visits as $v) {
    $st = strtolower(trim($v->visit_status ?? ''));
    $vDate = !empty($v->visit_date) ? Carbon::parse($v->visit_date)->toDateString() : null;
    if ($vDate === $today && !in_array($st, ['dibatalkan', 'batal', 'selesai', 'hadir'])) {
        $vTime = $v->visit_time ? substr($v->visit_time, 0, 5) : '08:00';

        // Sembunyikan jadwal kontrol hari ini jika waktu pelaksanaannya sudah terlewat
        $visitDateTime = Carbon::parse($vDate . ' ' . $vTime, 'Asia/Jakarta');
        if (Carbon::now('Asia/Jakarta')->greaterThan($visitDateTime)) {
            continue;
        }

        $todayControlSchedules[] = [ ... ];
    }
}
```

Dan pada method `patientHistory()` pada [KaderController.php](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/app/Http/Controllers/Api/KaderController.php#L783-L795):
```php
// Cari 1 Jadwal Kunjungan Puskesmas Berikutnya yang terdekat dan masih aktif (belum lewat waktu)
$todayStr = Carbon::today('Asia/Jakarta')->format('Y-m-d');
$candidateVisits = TreatmentVisit::whereIn('patient_treatment_id', $treatmentIds)
    ->where('visit_date', '>=', $todayStr)
    ->whereNotIn('visit_status', ['Dibatalkan', 'Batal', 'Selesai', 'Hadir'])
    ->orderBy('visit_date', 'asc')
    ->orderBy('visit_time', 'asc')
    ->get();

$nextVisit = $candidateVisits->first(function ($v) {
    $vDate = Carbon::parse($v->visit_date)->toDateString();
    $vTime = $v->visit_time ? substr($v->visit_time, 0, 5) : '08:00';
    $visitDateTime = Carbon::parse($vDate . ' ' . $vTime, 'Asia/Jakarta');
    return $visitDateTime->greaterThanOrEqualTo(Carbon::now('Asia/Jakarta'));
});
```

### B. Flutter Mobile (`home.dart` & `patient_detail.dart`)
1. **Pada [home.dart](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/kader/home.dart#L125-L145):**
```dart
final now = DateTime.now();
_todayControlSchedules = rawSchedules.where((s) {
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
```

2. **Pada [patient_detail.dart](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/kader/patient_detail.dart#L292-L345):**
Disediakan helper `_isVisitPassed(v)` yang memeriksa apakah jadwal telah melewati `DateTime.now()`. Hanya jadwal yang belum lewat yang dapat dipilih sebagai `_resolveNextUpcomingVisit()`. Jika semua kunjungan telah terlewat, kartu kontrol secara aman menampilkan empty state: *"Belum Ada Jadwal Kontrol"*.

---

## 4. Hasil Pengujian Otomatis
- **Backend:** `KaderDashboardRedesignTest.php`
  ```text
  ✓ passed control schedules are excluded from today schedules (kunjungan 07:30 disembunyikan saat waktu uji 08:30)
  ```
- **Mobile:** `kader_pengingat_dan_konsultasi_refinement_test.dart`
  ```text
  ✓ Passed control schedule is hidden while upcoming schedule is shown
  ✓ Detail Pasien hides visit schedule that has already passed
  ```
Seluruh pengujian lolos 100%.
