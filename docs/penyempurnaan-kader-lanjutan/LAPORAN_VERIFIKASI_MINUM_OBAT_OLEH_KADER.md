# LAPORAN VERIFIKASI MINUM OBAT OLEH KADER

**Tanggal Implementasi:** 8 November 2026  
**Tim Pengembang:** Senior Backend, Database, & Mobile Engineer  
**Status:** Selesai, Teruji 100%, Terintegrasi End-to-End

---

## 1. Latar Belakang Masalah Lapangan

Dalam program penanggulangan TB, sering terjadi kondisi di mana pasien lanjut usia, pasien tanpa kuota internet, atau pasien yang lupa/tidak sempat memfoto obat sebenarnya **tetap patuh meminum obat**. Di sisi lain, sistem aplikasi menandai hari tersebut sebagai `Terlewat` karena tidak ada bukti foto yang diunggah.

Ketika kader kesehatan mendatangi rumah pasien secara langsung (kunjungan lapangan), kader membutuhkan sarana untuk:
1. Memverifikasi status aktual kepatuhan pasien di lapangan.
2. Memperbaiki catatan pengobatan tanpa memaksa upload foto palsu.
3. Mempertahankan jejak audit (audit trail) yang transparan mengenai siapa yang memverifikasi dan apa alasannya.

---

## 2. Arsitektur Database & Migrasi Backend

Tabel: `medication_records`  
Migrasi: `2026_11_08_000001_add_kader_verification_to_medication_records_table.php`

Kolom Baru yang Ditambahkan:
```sql
ALTER TABLE `medication_records`
ADD COLUMN `status` enum('Diminum', 'Terlewat') DEFAULT 'Diminum' AFTER `late`,
ADD COLUMN `confirmation_source` enum('patient', 'kader') DEFAULT 'patient' AFTER `status`,
ADD COLUMN `verified_by` bigint unsigned NULL AFTER `confirmation_source`,
ADD COLUMN `verified_at` timestamp NULL AFTER `verified_by`,
ADD COLUMN `verification_note` text NULL AFTER `verified_at`,
ADD CONSTRAINT `fk_med_records_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
```

---

## 3. Spesifikasi Endpoint Verifikasi API

### A. Endpoint
`POST /api/kader/patients/{id}/verify-medication`

### B. Otorisasi & Proteksi IDOR
- Hanya dapat diakses oleh role Kader (`user_type_id == 3` dengan `officer_type_id == 4`).
- Memvalidasi kepemilikan wilayah binaan (`$patient->isAccessibleBy($user)`). Jika pasien berada di luar wilayah binaan kader, server mengembalikan **HTTP 403 Forbidden**.

### C. Validasi Input Request
```json
{
  "date": "2026-11-04",
  "action": "minum_tanpa_konfirmasi", // pilihan: "minum_tanpa_konfirmasi" | "tidak_minum"
  "note": "Pasien dikunjungi langsung di rumah, obat diminum pagi hari."
}
```

### D. Penanganan Proses Bisnis
1. **Pilihan 1: `minum_tanpa_konfirmasi`**
   - Menandai tanggal tersebut sebagai `Diminum`.
   - Mengisi `confirmation_source = 'kader'`.
   - Mengisi `verified_by = kader_id`.
   - Tidak memerlukan upload bukti foto (`photo = null`).
   - Memicu kalkulasi ulang kepatuhan harian (`calculateDetailedAdherence`) sehingga persentase kepatuhan pasien otomatis meningkat secara real-time.
2. **Pilihan 2: `tidak_minum`**
   - Mempertahankan status `Terlewat`.
   - Mengisi `confirmation_source = 'kader'`.
   - Mengisi `verified_by = kader_id`.
   - Menyimpan catatan kunjungan kader pada database sebagai bukti investigasi lapangan.

---

## 4. Implementasi UI/UX Flutter Mobile

File: `tb-care-mobile/lib/main/kader/patient_detail.dart`

1. **Card Dosis Terlewat Interaktif**:
   - Card riwayat minum obat yang berstatus `Terlewat` kini memiliki badge penanda:
     `[Icon Edit] Verifikasi Kunjungan Lapangan`.
   - Card dapat diklik langsung untuk memunculkan modal dialog.
2. **Modal Dialog Konfirmasi Lapangan**:
   - Menampilkan tanggal yang diverifikasi.
   - Pilihan radio card dengan kontras tinggi:
     - *Minum Obat Tanpa Konfirmasi* (Border hijau, aksen ramah).
     - *Tidak Minum Obat* (Border merah, aksen tegas).
   - Form input catatan kunjungan kader (opsional, maks 500 karakter).
   - Tombol `Simpan Verifikasi` dengan indikator loading animasi.
3. **Pembaruan Data Instan**:
   - Setelah respons 200 OK diterima dari server, aplikasi secara otomatis memanggil `_fetchFullPatientData(isRefresh: true)`. Seluruh ringkasan kepatuhan, counter hari minum, dan daftar obat langsung terbarui tanpa restart aplikasi.

---

## 5. Hasil Pengujian Backend & Mobile

| Uji Fitur | Skenario | Hasil |
|---|---|---|
| IDOR Protection | Kader A verifikasi pasien di luar desa binaan | HTTP 403 Forbidden |
| Audit Trail | Simpan verifikasi kader dengan catatan | Kolom `verified_by`, `confirmation_source = kader` terisi |
| Tanpa Foto | Verifikasi `minum_tanpa_konfirmasi` | Sukses disimpan tanpa file foto |
| Recalculate Adherence | Recalculate otomatis setelah verifikasi | Kepatuhan terhitung naik real-time |
| Flutter Widget Test | Klik dosis terlewat, pilih aksi, submit API | PASS 100% |
