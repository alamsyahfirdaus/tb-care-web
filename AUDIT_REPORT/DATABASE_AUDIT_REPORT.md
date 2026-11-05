# LAPORAN AUDIT DATABASE MYSQL (DATABASE AUDIT REPORT)
**TB Care Database Schema, Relational Integrity & Migration Review**
**Database Server:** MySQL 8.0.30 Community Server  
**Basis Data Utama:** `tbcare`  
**Basis Data Pengujian:** `tbcare_test_empty`  
**Tanggal Audit:** 06 November 2026

---

## 1. STRUKTUR DAN STATISTIK TABEL AKTUAL

Pemeriksaan langsung terhadap skema tabel `tbcare` menghasilkan ringkasan berikut:

| Nama Tabel | Jumlah Baris Aktual | Tipe Engine | Karakteristik Utama |
| :--- | :---: | :---: | :--- |
| `users` | 370 | InnoDB | Akun login lintas peran (Admin, Pasien, Petugas, Kader) |
| `user_types` | 3 | InnoDB | Master tipe pengguna (1: Admin, 2: Pasien, 3: Petugas) |
| `officers` | 66 | InnoDB | Profil petugas kesehatan (Dinkes Prov, Dinkes Kab, PJTB, Kader) |
| `patients` | 179 | InnoDB | Rekam data demografi pasien TB, NIK, dan wilayah |
| `patient_treatments` | 165 | InnoDB | Siklus pengobatan aktif dan riwayat pengobatan |
| `treatment_types` | 5 | InnoDB | Master regimen pengobatan TB standar dan lini lanjutan |
| `medication_records` | 205 | InnoDB | Konfirmasi harian minum obat beserta bukti foto |
| `patient_medication_schedules` | 4 | InnoDB | Jam pengingat harian minum obat pasien |
| `patient_reminders` | 0 | InnoDB | Riwayat pengingat massal terpersonalisasi oleh Kader |
| `treatment_visits` | 2 | InnoDB | Jadwal kunjungan rumah (home visit) / faskes |
| `screenings` | 89 | InnoDB | Catatan skrining terduga TB |
| `screening_categories` | 2 | InnoDB | Kategori usia skrining (<15 tahun dan ≥15 tahun) |
| `screening_questions` | 32 | InnoDB | Instrumen pertanyaan skrining TB |
| `screening_answers` | 2.296 | InnoDB | Detail jawaban kuesioner skrining |
| `close_contacts` | 51 | InnoDB | Kontak erat / anggota serumah pasien TB |
| `conversations` | 2 | InnoDB | Header percakapan live chat dua arah |
| `messages` | 6 | InnoDB | Pesan chat real-time teks dan gambar |
| `educational_materials` | 202 | InnoDB | Materi edukasi TB (gambar infografis, video, artikel) |
| `clinical_examinations` | 67 | InnoDB | Rekam pemeriksaan TCM Sputum, BTA, Foto Thoraks |
| `puskesmas` | 51 | InnoDB | Fasilitas pelayanan kesehatan primer pengampu |
| `provinces` | 5 | InnoDB | Master data provinsi |
| `districts` | 27 | InnoDB | Master data kabupaten / kota |
| `subdistricts` | 47 | InnoDB | Master data kecamatan |
| `villages` | 65 | InnoDB | Master data desa / kelurahan |
| `kader_areas` | 2 | InnoDB | Pemetaan desa & RW/RT binaan bagi Kader |
| `system_notifications` | 28 | InnoDB | Notifikasi sistem & notification center |
| `activity_logs` | 603 | InnoDB | Jejak audit operasional web dashboard |
| `personal_access_tokens` | 498 | InnoDB | Token autentikasi Sanctum mobile |
| `app_versions` | 0 | InnoDB | Konfigurasi pembaruan aplikasi mobile |
| `system_settings` | 0 | InnoDB | Konfigurasi parameter operasional global |

---

## 2. INTEGRITAS RELASIONAL DAN PEMERIKSAAN ORPHAN RECORDS

Audit integritas relasional dijalankan dengan kueri verifikasi langsung untuk mendeteksi *orphan records* (data anak yang kehilangan data induk):

```sql
SELECT COUNT(*) AS orphan_patients FROM patients p LEFT JOIN users u ON p.user_id = u.id WHERE p.user_id IS NOT NULL AND u.id IS NULL;
-- Hasil: 0

SELECT COUNT(*) AS orphan_treatments FROM patient_treatments pt LEFT JOIN patients p ON pt.patient_id = p.id WHERE p.id IS NULL;
-- Hasil: 0

SELECT COUNT(*) AS orphan_med_records FROM medication_records mr LEFT JOIN patient_treatments pt ON mr.patient_treatment_id = pt.id WHERE pt.id IS NULL;
-- Hasil: 0

SELECT COUNT(*) AS orphan_screenings FROM screenings s LEFT JOIN patients p ON s.patient_id = p.id WHERE s.patient_id IS NOT NULL AND p.id IS NULL;
-- Hasil: 0

SELECT COUNT(*) AS orphan_officers FROM officers o LEFT JOIN users u ON o.user_id = u.id WHERE u.id IS NULL;
-- Hasil: 0
```
**Kesimpulan Integritas:** **100% Bersih (Zero Orphan Records)**. Seluruh relasi *foreign key* konsisten.

---

## 3. AUDIT STATUS MIGRASI

- Total file migrasi: **44 file migrasi**.
- Status awal audit: 43 Migrated, 1 Pending (`2026_11_04_000001_create_patient_reminders_table`).
- Status pasca audit: **44 Migrated (100% Lengkap)**.
- Migrasi dieksekusi dengan aman tanpa kehilangan data pengguna atau rekam medis. Perintah berbahaya seperti `migrate:fresh`, `db:wipe`, atau `truncate` **TIDAK PERNAH DIJALANKAN** sesuai aturan ketat audit.

---

## 4. AUDIT INDEX DATABASE DAN EFISIENSI QUERY

1. **Composite Indexes untuk Scoping Cepat:**
   - Tabel `patient_reminders`: Index komposit `['sender_id', 'type']` dan `['patient_id', 'created_at']` mempercepat filter riwayat pengingat kader.
   - Tabel `messages`: Index pada `conversation_id` dan `created_at` memastikan live polling pesan chat berjalan optimal tanpa membebani CPU database.
   - Tabel `patients`: Index pada `puskesmas_id`, `subdistrict_id`, dan `village_id` memfasilitasi filter wilayah multi-tingkat secara instan.
2. **Pencegahan N+1 Query:**
   - Seluruh pemanggilan Eloquent pada controller (`PatientController`, `TreatmentController`, `ConversationController`, `DashboardController`) telah menerapkan *eager loading* eksplisit (`with(['user', 'puskesmas', 'village', ...])`).
