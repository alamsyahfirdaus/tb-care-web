# LAPORAN PENYEMPURNAAN VALIDASI & PENGINGAT KONTROL BERULANG
## Fleksibilitas Pengingat Jadwal Kunjungan Faskes

---

## 1. PENDAHULUAN

Kader kesehatan memegang peranan vital dalam mendampingi pasien TB agar tidak melewatkan jadwal kontrol faskes (pengambilan obat lanjutan, evaluasi dahak mikroskopis, rontgen ulang, dan konsultasi dokter). Sebelumnya, validasi pengingat kontrol terlalu kaku dan memunculkan peringatan palsu bahwa jadwal belum tersedia.

Dokumen ini menjelaskan perbaikan aturan bisnis pengingat kontrol, validasi backend, dan sinkronisasi data antar modul.

---

## 2. ATURAN BISNIS PENGINGAT KONTROL

| Aspek | Aturan Lama | Aturan Baru (Disempurnakan) |
|---|---|---|
| **Frekuensi Pengiriman** | Dibatasi cooldown 5 menit | **Tanpa batasan cooldown**. Kader dapat mengirim pengingat kontrol berulang kali (pagi, siang, atau beberapa kali dalam sehari). |
| **Waktu Pengiriman** | Terbatas pada jam tertentu | **Bebas waktu**. Boleh dikirim beberapa hari sebelum jadwal, pagi hari sebelum jam kontrol, atau pada jam kontrol. |
| **Jadwal Sudah Lewat** | Rentan ditolak otomatis | **Tetap diperbolehkan** selama status jadwal masih tercatat aktif (misal `Terjadwal` atau `Belum Hadir`) dan belum berstatus `Dibatalkan`. |
| **Jadwal Batal** | Belum divalidasi spesifik | **Ditolak dengan 422** (`Jadwal kontrol telah dibatalkan.`). |
| **Tidak Ada Jadwal** | Menutup tanpa feedback | **Ditolak dengan 422** (`Pasien tidak memiliki jadwal kontrol.`). |

---

## 3. LOGIKA VALIDASI LARAVEL BACKEND

Berkas: `tb-care-web/app/Http/Controllers/Api/KaderController.php`

```php
if ($request->type === 'visit') {
    $visitQuery = TreatmentVisit::whereHas('patientTreatment', function ($q) use ($patient) {
        $q->where('patient_id', $patient->id);
    });

    if ($request->filled('schedule_id')) {
        $visit = (clone $visitQuery)->find($request->schedule_id);
        if (!$visit) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal kontrol yang dipilih tidak ditemukan untuk pasien ini.',
            ], 404);
        }
    } else {
        $allVisits = (clone $visitQuery)->get();
        if ($allVisits->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Pasien tidak memiliki jadwal kontrol.',
            ], 422);
        }

        // Filter jadwal yang tidak dibatalkan
        $validVisits = $allVisits->filter(function ($v) {
            $st = strtolower(trim($v->visit_status ?? ''));
            return !in_array($st, ['dibatalkan', 'batal']);
        });

        if ($validVisits->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal kontrol telah dibatalkan.',
            ], 422);
        }

        $visit = $validVisits->sortBy('visit_date')->first();
    }

    $st = strtolower(trim($visit->visit_status ?? ''));
    if (in_array($st, ['dibatalkan', 'batal'])) {
        return response()->json([
            'success' => false,
            'message' => 'Jadwal kontrol telah dibatalkan.',
        ], 422);
    }
}
```

---

## 4. RESOLUSI DATA JADWAL KONTROL PADA FLUTTER

### A. Penyatuan Data pada Halaman Detail Pasien
Berkas: `tb-care-mobile/lib/main/kader/patient_detail.dart`

Ketika Kader menekan tombol `Ingatkan Kontrol` pada kartu jadwal kontrol pasien:
```dart
onPressed: () {
  final visit = _resolveNextUpcomingVisit();
  final reminderData = Map<String, dynamic>.from(_patientDetail);
  if (visit != null) {
    reminderData['next_visit'] = visit;
    reminderData['visit'] = visit;
    reminderData['visit_date'] = visit['visit_date'];
    reminderData['visit_time'] = visit['visit_time'];
    reminderData['schedule_id'] = visit['id'];
  }
  reminderData['treatments'] = _treatments;

  KaderReminderService.showReminderBottomSheet(
    context,
    patient: reminderData,
    defaultType: 'visit',
    client: widget.client,
    onSuccess: _fetchFullPatientData,
  );
}
```

### B. Pemeriksaan Komprehensif pada Service Pengingat
Berkas: `tb-care-mobile/lib/services/kader_reminder_service.dart`

Service kini secara rekursif memeriksa:
1. `patient['next_visit']` atau `patient['visit']`
2. `patient['visit_date']` atau `patient['next_visit_date']`
3. `patient['treatments']` -> loop tiap treatment dan periksa `visits`
4. `patient['visits']` langsung
5. `patient['active_treatment']['visits']`

Jika ditemukan jadwal yang tidak dibatalkan, `hasValidVisit` dipastikan bernilai `true`, template otomatis diisi dengan rincian jadwal yang benar, dan tombol kirim langsung aktif.

---

## 5. BUKTI PENGUJIAN OTOMATIS

Seluruh skenario telah teruji otomatis dalam `tests/Feature/KaderReminderFlowTest.php`:
- `test_visit_reminder_succeeds_when_patient_has_valid_visit` -> **PASS**
- `test_visit_reminder_can_be_sent_repeatedly_without_cooldown` -> **PASS**
- `test_visit_reminder_rejected_if_patient_has_no_visit_schedule` -> **PASS**
- `test_visit_reminder_rejected_if_visit_is_cancelled` -> **PASS**
- `test_visit_reminder_rejected_if_schedule_id_not_found` -> **PASS**
