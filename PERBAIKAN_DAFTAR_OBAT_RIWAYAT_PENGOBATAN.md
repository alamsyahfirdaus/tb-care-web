# PERBAIKAN DAFTAR OBAT PADA RIWAYAT PENGOBATAN (TB CARE)

## 1. Analisis Akar Masalah (Root Cause Analysis)

Pada halaman **Riwayat Pengobatan** (`TreatmentHistoryPage`), sebelumnya sering muncul teks peringatan:
> *"Belum ada data obat untuk pengobatan ini."*
meskipun pada data master pasien dan pengobatan telah dimasukkan resep obat saat pendaftaran atau pemeriksaan.

### Akar Masalah yang Ditemukan:
1. **Format Serialisasi JSON yang Bervariasi**:
   Kolom `prescription` pada tabel `patient_treatments` tersimpan dalam beberapa format tergantung bagaimana data dibuat:
   - Format Array of Strings sederhana: `["Isoniazid", "Rifampisin", "Pirazinamid"]`
   - Format Array of Objects terstruktur: `[{"name": "Isoniazid", "dosage": "300 mg", "frequency": "1x sehari", "rules": "Sebelum makan"}]`
   - Format JSON String yang ter-escape dua kali (*double JSON string*) atau teks mentah yang dipisahkan baris baru (`\n`).
2. **Parser Mobile yang Terlalu Kaku**:
   Sebelumnya parser di Flutter mengharapkan key spesifik tertentu tanpa fallback fleksibel, sehingga ketika tipe datanya berupa string langsung atau key alternatif (`nama_obat`, `drug_name`, `nama`), parser menghasilkan `null` atau `empty list`, yang memicu tampilan fallback *"Belum ada data obat untuk pengobatan ini"*.

---

## 2. Normalisasi Data di Backend Laravel

Di dalam `TreatmentController.php` (pada method `getTreatmentsByPatient` dan `medications`):
Backend memastikan kolom `prescription` didecode dan dinormalisasi menjadi array obat yang seragam:
- Jika tersimpan sebagai JSON string, didecode secara aman.
- Jika berupa string tunggal atau string berpemisah baris baru (`\n`), dipisah menjadi item-item array.
- Nilai obat dikirimkan sebagai objek lengkap yang mencakup `name`, `dosage`, `frequency`, dan `instructions` (atau `rules`).

---

## 3. Penyempurnaan Parser dan UI di Flutter Mobile

### A. Model Parser Tangguh (`lib/models/patient_medication.dart`)
Model `PatientMedication.fromDynamic(dynamic item)` mendukung berbagai variasi masukan:
- **String langsung**: `"Isoniazid"` -> `PatientMedication(name: 'Isoniazid')`.
- **Map / Objek**: Mendukung key alias `name`, `nama_obat`, `nama`, `drug_name`, `medicine_name`, `dosage`, `dosis`, `frequency`, `frekuensi`, `rules`, `instruksi`, `aturan`, `catatan`.

### B. Desain Layout Baru pada `TreatmentHistoryPage` (`lib/main/pasien/treatment_history.dart`)
Metode `_buildDrugItem(PatientMedication drug, int index)`:
1. **Nomor Urut Melingkar (*Numbered Badge*)**:
   - Container bulat berukuran 22x22px dengan background biru muda (`#EFF6FF`).
   - Teks nomor urut: 1, 2, 3... warna primer (`#1E40AF`), semi-bold.
2. **Nama Obat**:
   - Tipografi: Plus Jakarta Sans, 13px, font weight bold (`FontWeight.w700`), warna `#1E293B`.
3. **Instruksi Dosis dan Aturan Minum**:
   - Menyatukan informasi `dosage`, `frequency`, dan `rules` yang tersedia (misal: *"300 mg • 1x sehari • Pagi sebelum makan"*).
   - **Fallback Bersih**: Jika tidak ada data dosis khusus di resep, menampilkan teks halus:
     `Sesuai dosis resep`
     (warna abu-abu `#64748B`, font style normal, bukan miring atau tanda tanya).
4. **Kondisi Tanpa Obat**:
   - Jika suatu episode pengobatan benar-benar belum memiliki resep yang tercatat, card menampilkan pesan informatif dalam wadah rounded abu-abu muda:
     *"Belum ada data obat untuk pengobatan ini."*

---

## 4. Hasil Verifikasi dan Pengujian

1. **Unit Test Model Obat (`test/patient_treatment_tab_and_medication_test.dart`)**:
   - String input: Berhasil diparsing menjadi nama obat.
   - Structured Map input: Berhasil diparsing menjadi nama, dosis, frekuensi, dan aturan minum.
   - Status: **PASS**.
2. **Integration Test Backend (`MedicationApiTest`)**:
   - Endpoint riwayat pengobatan mengembalikan data obat ternormalisasi untuk setiap episode pengobatan.
   - Status: **PASS**.
3. **Flutter Static Analysis**:
   - `flutter analyze`: **0 issues found**.
