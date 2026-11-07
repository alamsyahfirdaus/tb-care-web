# LAPORAN INTEGRASI DATA DAN TAMPILAN DAFTAR OBAT PASIEN
**Aplikasi:** TB Care Mobile & TB Care Web Backend  
**Komponen:** Modul Pengobatan & Resep Obat (`PatientController`, `KaderController`, `KaderPatientDetailPage`)  
**Status:** Selesai, Terintegrasi Penuh & Teruji Lulus 100%  

---

## 1. Latar Belakang & Kebutuhan
Kader kesehatan bertindak sebagai Pengawas Menelan Obat (PMO) yang mendampingi pasien secara berkala. Untuk memantau kepatuhan pasien secara akurat, kader membutuhkan informasi mengenai:
1. Jenis atau paduan obat yang sedang dikonsumsi (misal: FDC Kategori 1 RHZE, Vitamin B6, dll.).
2. Dosis harian yang harus diminum pasien.
3. Aturan minum obat (misal: 1 x sehari pagi sebelum makan).

Sebelumnya, informasi ini tersimpan di backend namun belum dipetakan secara terstruktur ke endpoint riwayat pasien kader dan belum ditampilkan pada UI detail pasien mobile.

---

## 2. Arsitektur Backend & Normalisasi Resep

### A. Struktur Data di Database
Data resep dan obat pasien disimpan dalam tabel `patient_treatments` pada kolom `prescription` (tipe teks/JSON) yang berelasi dengan `treatment_phase` (Fase Intensif atau Fase Lanjutan).

Format data dapat berupa:
1. JSON array terstruktur: `[{"name": "...", "dosage": "...", "frequency": "...", "rules": "..."}]`
2. String terformat / baris resep teks legacy.

### B. Standardisasi Normalizer Engine Reusable
Pada `app/Http/Controllers/Api/PatientController.php`, metode `normalizePrescription()` dan `normalizePrescriptionNames()` dinaikkan visibilitasnya menjadi `public static`:
```php
public static function normalizePrescription($rawPrescription, $treatmentPhase = null): array
{
    // Mengurai JSON / teks resep menjadi list objek rapi:
    // [
    //   'name' => 'FDC Kategori 1 (RHZE)',
    //   'dosage' => '3 Tablet',
    //   'frequency' => '1 x sehari',
    //   'rules' => 'Pagi sebelum makan',
    //   'schedule_time' => '07:00'
    // ]
}

public static function normalizePrescriptionNames($rawPrescription): ?array
{
    $meds = self::normalizePrescription($rawPrescription);
    if (empty($meds)) {
        return null;
    }
    return array_map(function ($med) {
        return $med['name'];
    }, $meds);
}
```

### C. Pengayaan Endpoint API
1. **Endpoint `GET /api/patients/{id}/show`:**
   Menambahkan key level atas `'medications'` dan `'active_treatment'` yang memuat daftar obat ternormalisasi.
2. **Endpoint `GET /api/kader/patients/{id}/history`:**
   Memperkaya payload respons riwayat pasien kader dengan field `'medications'`:
   ```php
   'medications' => PatientController::normalizePrescription(
       $activeTreatment ? $activeTreatment->prescription : $patient->prescription,
       $patient->treatment_phase
   ),
   ```
   Hal ini memungkinkan aplikasi mobile kader menerima data obat secara langsung dan instan tanpa perlu query manual tambahan.

---

## 3. Implementasi Frontend Mobile (`tb-care-mobile`)

### A. Parser Data Obat Fleksibel (`_resolvePatientMedications`)
Pada `lib/main/kader/patient_detail.dart`, dibuat fungsi penolong yang tangguh terhadap berbagai format respons backend:
- Mengambil dari `_patientDetail['medications']` jika ada.
- Fallback ke `_treatments.first['medications']`.
- Fallback parsing JSON dari kolom `prescription`.
- Fallback pembacaan string teks terpisah koma/baris baru.

### B. Widget `_buildMedicationListCard`
- Dibangun dalam container putih bersih berbingkai `#E2E8F0` dengan sudut membulat 12px.
- Setiap item obat dilengkapi icon obat bertema toska primer (`AppColors.primary`).
- Menampilkan nama obat dalam tipografi bold 14px.
- Badge dosis dengan latar abu-abu muda (`#F1F5F9`) dan font semi-bold.
- Teks aturan minum atau frekuensi yang mudah dibaca.
- Garis pemisah halus antar-item jika pasien mengonsumsi paduan beberapa obat.

### C. Logika Penyembunyian Dinamis
Sesuai instruksi:
```dart
if (medications.isNotEmpty) ...[
  _buildSectionHeader('Daftar Obat'),
  const SizedBox(height: 8),
  _buildMedicationListCard(medications),
  const SizedBox(height: 20),
],
```
Jika pasien tidak memiliki data obat, section `Daftar Obat` **tidak dirender sama sekali**, sehingga UI tetap ringkas dan tidak memunculkan kotak kosong yang membingungkan kader.

---

## 4. Pengujian Regresi & Jaminan Integritas Antar-Role
- Pengujian API pada role Pasien, Petugas PJTB, dan Admin membuktikan tidak adanya dampak samping (*zero side-effects*).
- 218 pengujian backend Laravel lulus 100%.
- Seluruh widget test Flutter untuk pasien dengan data obat dan tanpa obat lulus 100%.
