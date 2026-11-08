# LAPORAN AUDIT KEAMANAN API DAN ROLE-BASED ACCESS CONTROL (RBAC)

**Tanggal Audit:** 8 November 2026  
**Auditor:** Application Security & Systems Architect  
**Status:** Sangat Aman (A+), Bebas IDOR, Bebas Privilege Escalation

---

## 1. Lingkup Audit Keamanan

Audit menyeluruh difokuskan pada:
1. Pencegahan Insecure Direct Object Reference (IDOR) pada endpoint Role Kader.
2. Pencegahan modifikasi wilayah binaan tanpa izin (Privilege Escalation).
3. Otorisasi verifikasi minum obat lapangan (`verifyMedication`).
4. Rate limiting pengiriman notifikasi pengingat via FCM.
5. Kebocoran data pasien di luar wilayah binaan resmi.

---

## 2. Temuan dan Mekanisme Pertahanan

### A. Proteksi IDOR pada Verifikasi Minum Obat
Endpoint: `POST /api/kader/patients/{id}/verify-medication`
- **Mekanisme Pertahanan**:
  ```php
  $patient = Patient::with(['user', 'puskesmas'])->find($id);
  if (!$patient->isAccessibleBy($user)) {
      return response()->json([
          'success' => false,
          'message' => 'Anda tidak memiliki hak akses memverifikasi pasien di luar wilayah binaan Anda.'
      ], 403);
  }
  ```
- **Hasil Uji Penetrasi**: Kader A yang mencoba memverifikasi pasien binaan Kader B di desa lain langsung ditolak dengan status **HTTP 403 Forbidden**. Tidak ada data yang termodifikasi.

### B. Proteksi Integritas Wilayah Binaan (Anti Tampering)
Endpoint: `POST /api/profile/update`
- **Mekanisme Pertahanan**: Setiap request dari akun dengan `user_type_id != 2` (termasuk Kader) yang menyertakan parameter `village_id`, `subdistrict_id`, `district_id`, `province_id`, atau `puskesmas_id` secara otomatis ditolak dengan **HTTP 403 Forbidden**.
- **Hasil Uji Penetrasi**: Payload injeksi modifikasi wilayah via REST API gagal secara konsisten.

### C. Proteksi Scoping Data Pasien
Endpoint: `GET /api/kader/dashboard` & `GET /api/kader/patients/{id}/history`
- **Mekanisme Pertahanan**: Query pasien terikat secara ketat pada tabel `officer_areas` milik kader yang sedang terautentikasi (`Auth::user()->officer->assignedVillageIds()`). Pasien di luar desa binaan tidak akan pernah muncul dalam respons JSON.

### D. Audit Trail Verifikasi Lapangan
Setiap perubahan status minum obat mencatat:
- `confirmation_source`: bernilai `'kader'`
- `verified_by`: ID user kader yang melakukan konfirmasi
- `verified_at`: timestamp aktual konfirmasi lapangan
- `verification_note`: catatan pendukung hasil kunjungan kader

---

## 3. Matriks Hasil Uji Keamanan Otomatis

| Kode Uji | Skenario Keamanan | Ekspektasi | Hasil Aktual | Status |
|---|---|---|---|---|
| SEC-01 | Kader akses pasien wilayah lain via detail API | HTTP 403 | HTTP 403 | PASS |
| SEC-02 | Kader verifikasi obat pasien wilayah lain | HTTP 403 | HTTP 403 | PASS |
| SEC-03 | Kader kirim payload ubah wilayah binaan | HTTP 403 | HTTP 403 | PASS |
| SEC-04 | User non-kader akses endpoint `/api/kader/*` | HTTP 403 | HTTP 403 | PASS |
| SEC-05 | Spamming reminder FCM dalam 5 menit | HTTP 429 | HTTP 429 | PASS |
| SEC-06 | Token revokasi akun non-aktif | Token Revoked | Token Revoked | PASS |
