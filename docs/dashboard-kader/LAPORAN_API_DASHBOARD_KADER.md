# LAPORAN ARSITEKTUR DAN INTEGRASI API DASHBOARD KADER
## APLIKASI TB CARE (LARAVEL BACKEND)

---

## 1. DESKRIPSI ENDPOINT

Endpoint `/api/kader/dashboard` menyediakan data konsolidasi yang diperlukan untuk merender Beranda Role Kader secara komprehensif, mencakup 4 statistik kartu, daftar pasien belum minum obat, dan jadwal kontrol hari ini.

- **URL:** `/api/kader/dashboard`
- **Method:** `GET`
- **Autentikasi:** Bearer Token (Laravel Sanctum)
- **Role Terotorisasi:** Petugas (User Type 3) dengan jabatan Kader Puskesmas (Officer Type 4).

---

## 2. OPTIMASI KUERI DAN PENGAMANAN DATA (RBAC)

1. **Scoping Wilayah Binaan (RBAC Scoping):**
   - Mengambil seluruh `village_id` dari tabel `kader_areas` milik officer kader yang terautentikasi.
   - Apabila belum memiliki entri pada `kader_areas`, dilakukan fallback pada wilayah puskesmas induk.
   - Seluruh kueri pasien di-scope ketat menggunakan `whereIn('village_id', $villageIds)`.
2. **Pencegahan N+1 Query (Eager Loading):**
   - Kueri `activeTreatments` memuat relasi sekaligus:
     `with(['patient.user', 'patient.village', 'treatmentType', 'puskesmas'])`.
   - Kueri `TreatmentVisit` memuat relasi:
     `with(['patient.user', 'patient.village', 'treatment.puskesmas'])`.
3. **Penyelarasan Zona Waktu (WIB / Asia/Jakarta):**
   - Menggunakan `$today = Carbon::today('Asia/Jakarta')->toDateString()` untuk memastikan perhitungan hari ini sinkron dengan kalender pasien dan kader, bebas dari selisih UTC.

---

## 3. STRUKTUR PAYLOAD RESPONS LENGKAP

```json
{
  "success": true,
  "message": "Data dashboard kader berhasil diambil",
  "data": {
    "kader_info": {
      "id": 10,
      "name": "Dede Tetin",
      "username": "dedetetin",
      "puskesmas_id": 1,
      "puskesmas_name": "Puskesmas Tamansari",
      "assigned_villages": ["Setiamulya"]
    },
    "statistics": {
      "total_patients": 22,
      "belum_minum_obat": 20,
      "unconfirmed_today": 20,
      "sudah_minum": 2,
      "confirmed_today": 2,
      "jadwal_kontrol": 3,
      "control_schedules_today": 3,
      "active_treatments": 22,
      "need_reminder": 20,
      "upcoming_visits": 3
    },
    "unconfirmed_patients": [
      {
        "patient_id": 5,
        "name": "Alamsyah Firdaus",
        "nik": "3278053107980010",
        "village_name": "Setiamulya",
        "address": "RT 01/RW 01, Desa Setiamulya",
        "rt": "01",
        "rw": "01",
        "short_address": "RT 01/RW 01, Setiamulya",
        "medication_time": "07:00",
        "medication_time_formatted": "07:00 WIB",
        "status_label": "Belum konfirmasi minum obat",
        "treatment_id": 12,
        "phone": "0897654321"
      }
    ],
    "today_control_schedules": [
      {
        "visit_id": 101,
        "patient_id": 5,
        "treatment_id": 12,
        "patient_name": "Alamsyah Firdaus",
        "patient_short_address": "RT 01/RW 01, Setiamulya",
        "village_name": "Setiamulya",
        "visit_date": "2026-11-08",
        "visit_time": "09:00",
        "visit_time_formatted": "09:00 WIB",
        "visit_status": "Terjadwal",
        "visit_type": "Pemeriksaan Rutin",
        "puskesmas_name": "Puskesmas Tamansari",
        "notes": "Pemeriksaan sputum berkala"
      }
    ],
    "patients_need_reminder": [ ... ],
    "recent_activities": [ ... ]
  }
}
```

---

## 4. PENANGANAN BACKWARD COMPATIBILITY

Untuk mencegah *breaking changes* pada versi aplikasi mobile sebelumnya atau modul lain yang bergantung pada API ini:
- Key lama seperti `active_treatments`, `need_reminder`, dan `upcoming_visits` tetap dipertahankan dengan nilai yang konsisten.
- Key baru (`belum_minum_obat`, `sudah_minum`, `jadwal_kontrol`, `unconfirmed_patients`, `today_control_schedules`) ditambahkan sebagai *non-breaking additions*.
- Format nama selalu dinormalisasi menjadi Title Case menggunakan `App\Helpers\NameFormatter`.
