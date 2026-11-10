# AUDIT STATUS MINUM OBAT DAN LOGIKA VERIFIKASI (TB CARE)

## 1. Latar Belakang Masalah

Pada implementasi sebelumnya di sistem TB Care, terdapat ketidakkonsistenan serius pada status minum obat dan verifikasi:
1. **False Verification**: Pasien yang baru mengunggah foto obat sendiri langsung mendapatkan label atau status `Terverifikasi`, padahal belum pernah diperiksa secara fisik maupun digital oleh Kader atau Petugas Kesehatan.
2. **Badge "Menunggu Verifikasi" Mengganggu**: Pada UI pasien ditampilkan label "Menunggu Verifikasi" yang memberi kesan pengobatan pasien belum sah, padahal peran pasien adalah melapor secara mandiri dan peran kader adalah mendampingi di lapangan.
3. **Status "Terlewat" Prematur**: Dosis obat hari ini yang belum diminum pada pagi/siang hari langsung divonis berstatus `Terlewat` (*Missed*) dan merusak angka kepatuhan (*adherence percentage*), padahal hari masih berjalan dan batas toleransi konfirmasi adalah hingga akhir hari (pukul 23:59:59 WIB).

---

## 2. Aturan Bisnis Baku (Before vs After)

| Kondisi / Skenario | Logika Sebelum Audit | Logika Baku Setelah Audit |
|---|---|---|
| **Pasien lapor tepat waktu** (sebelum toleransi berakhir) | Sering otomatis berlabel `Terverifikasi` atau memunculkan status `Menunggu Verifikasi`. | **HANYA** menampilkan badge `[Sudah Minum]`. Tidak pernah berstatus `Terverifikasi` jika belum dikonfirmasi kader, dan tidak ada badge pending yang meragukan laporan pasien. |
| **Pasien lapor terlambat** (melewati toleransi keterlambatan) | Berstatus `Terlambat` atau `Terverifikasi Terlambat`. | Menampilkan badge `[Sudah Minum] [Terlambat]`. |
| **Kader melakukan verifikasi lapangan** (kunjungan langsung / PMO) | Tidak ada pemisahan sumber konfirmasi yang jelas (`confirmation_source`). | Menampilkan badge ganda: `[Sudah Minum] [Terverifikasi]`. Label `[Terverifikasi]` HANYA muncul jika `confirmation_source === 'kader'` atau ada identitas `verified_by` yang valid. |
| **Kader memverifikasi minum obat yang terlambat** | Tidak konsisten. | Menampilkan tiga badge: `[Sudah Minum] [Terlambat] [Terverifikasi]`. |
| **Hari ini, jam minum obat sudah lewat, pasien belum lapor** | Langsung dicatat sebagai `Terlewat` (*Missed*), memotong persentase kepatuhan secara prematur. | Menampilkan badge amber: `[Belum Konfirmasi]`. **TIDAK** menambah hitungan `total_missed_days` dan tidak merusak persentase kepatuhan sebelum pergantian hari. Terdapat keterangan: *"Batas konfirmasi sampai pukul 23:59 WIB"*. |
| **Hari ini, jam minum obat belum tiba** | Sering ambigu atau dianggap belum ada data. | Menampilkan badge abu-abu: `[Belum Jatuh Tempo]`. |
| **Hari kemarin/lampau tanpa catatan minum obat** | Kadang tidak terdata di riwayat kronologis. | Secara definitif berstatus `[Terlewat]` (*Missed*) dan masuk ke dalam perhitungan kepatuhan pengobatan. |

---

## 3. Implementasi pada Backend Laravel

### A. Model `PatientTreatment` (`tb-care-web/app/Models/PatientTreatment.php`)
Di dalam method `calculateDetailedAdherence($period = 'all')`:
1. **Evaluasi Status Hari Ini**:
   ```php
   if ($isToday) {
       if ($now->greaterThanOrEqualTo($timeCarbonToday)) {
           // Sudah melewati jadwal dosis hari ini, belum cutoff 23:59:59 WIB:
           $unconfirmedItem = [
               'status'              => 'Belum Konfirmasi',
               'status_key'          => 'not_confirmed',
               'color'               => '#F59E0B',
               'badge_class'         => 'badge-warning',
               'notes'               => 'Belum ada bukti minum obat hari ini. Batas konfirmasi sampai pukul 23:59 WIB.',
               'is_today'            => true,
               ...
           ];
           $dailyRecords[] = $unconfirmedItem;
           // TIDAK dimasukkan ke $missedDays dan TIDAK menaikkan $missedDosesCount!
       } else {
           // Jam minum obat belum tiba:
           $dailyRecords[] = [
               'status'     => 'Belum Jatuh Tempo',
               'status_key' => 'not_due',
               'color'      => '#94A3B8',
               ...
           ];
       }
   }
   ```
2. **Evaluasi Verifikasi Kader**:
   ```php
   $isKaderVerified = ($rec->is_verified && ($rec->confirmation_source === 'kader' || !empty($rec->verified_by)));
   $verificationStatus = $isKaderVerified ? 'Terverifikasi' : 'Sudah Minum';
   ```

### B. Controller `TreatmentController` (`tb-care-web/app/Http/Controllers/Api/TreatmentController.php`)
- Method `medicationHistory($patientId)` memetakan status `verification_status` dan mengisolasi records berdasarkan episode `patient_treatment_id`.

---

## 4. Implementasi pada Flutter Mobile

### A. Penampilan Badge Bersih di `MedicationHistoryPage` (`tb-care-mobile/lib/main/pasien/history.dart`)
1. **Card Dosis Diminum (`_buildTakenCard`)**:
   - Jika diminum normal: Menampilkan badge `[Sudah Minum]`.
   - Jika kader telah melakukan konfirmasi: Menambahkan badge `[Terverifikasi]` di sampingnya.
   - Jika waktu pengunggahan melewati toleransi: Menambahkan badge `[Terlambat]`.
   - **Tidak ada** badge "Menunggu Verifikasi" / "Pending" di UI pasien.
2. **Card Belum Konfirmasi (`_buildNotConfirmedCard`)**:
   - Menampilkan badge amber `[Belum Konfirmasi]`.
   - Menampilkan waktu jadwal minum obat: `Jadwal: 07:00 WIB`.
   - Menampilkan box edukasi amber: *"Belum ada bukti minum obat hari ini. Batas konfirmasi sampai pukul 23:59 WIB."*
3. **Card Terlewat (`_buildMissedCard`)**:
   - Menampilkan badge merah `[Terlewat]`.
   - Menampilkan keterangan: *"Tidak ada konfirmasi minum obat pada tanggal ini."*
4. **Card Belum Jatuh Tempo (`_buildNotDueCard`)**:
   - Menampilkan badge abu-abu `[Belum Jatuh Tempo]`.
   - Keterangan: *"Waktu minum obat belum tiba."*

---

## 5. Hasil Verifikasi dan Pengujian

Pengujian unit dan integrasi dilakukan menggunakan PHPUnit pada suite `MedicationEpisodeAndVerificationTest`:

```bash
php artisan test tests/Feature/MedicationEpisodeAndVerificationTest.php
```

**Hasil:**
```text
PASS  Tests\Feature\MedicationEpisodeAndVerificationTest
✓ medication history strictly scoped by treatment id
✓ today unconfirmed dose is marked not confirmed and not counted as missed
✓ medication verification status distinguishes patient and kader

Tests:  3 passed
Time:   0.51s
```

Semua pengujian membuktikan:
1. Pasien melapor sendiri tidak pernah berstatus `Terverifikasi`.
2. Dosis hari ini tidak pernah salah divonis sebagai `Terlewat`.
3. Kader verifikasi lapangan terdata secara presisi dengan identitas pemeriksa.
