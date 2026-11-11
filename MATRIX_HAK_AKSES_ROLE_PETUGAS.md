# MATRIKS HAK AKSES ROLE PETUGAS TB CARE
**Aplikasi TB Care (Mobile Flutter & Web Laravel)**  
*Dokumen Pemetaan Hak Akses (RBAC), Fungsionalitas, dan Batasan Cakupan Wilayah*  
*Tanggal: 11 November 2026*  
*Status: Terverifikasi & Lulus Pengujian Keamanan*

---

## 1. PRINSIP DASAR RBAC ROLE PETUGAS

Sesuai ketetapan arsitektur aplikasi TB Care:
1. **Fitur & Menu Seragam**: Seluruh petugas kesehatan (`PJTB Puskesmas`, `Dinkes Kab/Kota`, `Dinkes Provinsi`) memiliki menu antarmuka dan kemampuan operasional aplikasi yang sama.
2. **Perbedaan Hanya pada Batasan Data (Territory Scoping)**: Perbedaan hak antar tingkatan jabatan bukan pada fungsionalitas fitur, melainkan pada jangkauan wilayah data (data boundary) yang dapat dilihat dan dikelola.
3. **Isolasi Role Kader dan Role Pasien**: Role Kader dibatasi pada wilayah desa penugasannya (`kader_areas`) dengan hak kelola pasien terbatas, sementara Pasien hanya dapat mengakses data dirinya sendiri (`user_type_id = 2`).

---

## 2. MATRIKS PERBANDINGAN TINGKATAN PETUGAS DAN ROLE LAIN

| Fitur / Operasi | Dinkes Provinsi (`officer_type_id: 1`) | Dinkes Kab/Kota (`officer_type_id: 2`) | PJTB Puskesmas (`officer_type_id: 3`) | Kader (`officer_type_id: 4`) | Pasien (`user_type_id: 2`) |
|---|:---:|:---:|:---:|:---:|:---:|
| **Cakupan Wilayah Data** | Seluruh Kab/Kota dalam Provinsi terkait | Seluruh Puskesmas dalam Kab/Kota terkait | Seluruh Desa dalam wilayah kerja Puskesmas | Desa/Kelurahan penugasan (`kader_areas`) | Hanya data diri sendiri |
| **Daftar Menu Petugas** | Lengkap (StaffHomePage) | Lengkap (StaffHomePage) | Lengkap (StaffHomePage) | Terbatas (KaderHomePage) | Terbatas (PasienHomePage) |
| **Lihat Daftar Pasien** | ✅ (Dalam Provinsi) | ✅ (Dalam Kab/Kota) | ✅ (Dalam Puskesmas) | ✅ (Dalam Desa Penugasan) | ❌ (Hanya Profil Diri) |
| **Tambah Pasien Baru** | ✅ (Dalam Provinsi) | ✅ (Dalam Kab/Kota) | ✅ (Dalam Puskesmas) | ❌ (Forbidden 403) | ❌ (Hanya Registrasi Akun) |
| **Ubah Data Pasien** | ✅ (Dalam Provinsi) | ✅ (Dalam Kab/Kota) | ✅ (Dalam Puskesmas) | ❌ (Forbidden 403) | ❌ (Hanya Edit Profil Kontak) |
| **Hapus Data Pasien** | ✅ (Dalam Provinsi) | ✅ (Dalam Kab/Kota) | ✅ (Dalam Puskesmas) | ❌ (Forbidden 403) | ❌ (Forbidden 403) |
| **Lihat Detail Pasien & Rekam Medis** | ✅ (Dalam Provinsi) | ✅ (Dalam Kab/Kota) | ✅ (Dalam Puskesmas) | ✅ (Dalam Desa Penugasan) | ✅ (Data Pribadi Saja) |
| **Monitoring Pengobatan Pasien** | ✅ (Dalam Provinsi) | ✅ (Dalam Kab/Kota) | ✅ (Dalam Puskesmas) | ✅ (Dalam Desa Penugasan) | ✅ (Pengobatan Pribadi) |
| **Verifikasi Bukti Minum Obat** | ✅ (Dalam Provinsi) | ✅ (Dalam Kab/Kota) | ✅ (Dalam Puskesmas) | ❌ (Hanya Lihat / Ingatkan) | ❌ (Hanya Unggah Bukti) |
| **Kelola Jadwal Kontrol** | ✅ (Buat, Ubah, Hapus) | ✅ (Buat, Ubah, Hapus) | ✅ (Buat, Ubah, Hapus) | ❌ (Hanya Lihat Jadwal) | ❌ (Hanya Konfirmasi Hadir) |
| **Monitoring & Riwayat Screening** | ✅ (Dalam Provinsi) | ✅ (Dalam Kab/Kota) | ✅ (Dalam Puskesmas) | ✅ (Dalam Desa Penugasan) | ✅ (Screening Pribadi) |
| **Kelola Materi Edukasi (CRUD)** | ✅ (Upload Gambar/Video) | ✅ (Upload Gambar/Video) | ✅ (Upload Gambar/Video) | ❌ (Hanya Membaca) | ❌ (Hanya Membaca) |
| **Konsultasi / Chat Medis** | ✅ (Pasien/Kader se-Provinsi) | ✅ (Pasien/Kader se-Kab/Kota) | ✅ (Pasien/Kader se-Puskesmas) | ✅ (Pasien di Desa Penugasan) | ✅ (Petugas/Kader Wilayah) |
| **Kirim Pesan Pengumuman / Broadcast** | ✅ (Sesuai Yurisdiksi) | ✅ (Sesuai Yurisdiksi) | ✅ (Sesuai Yurisdiksi) | ❌ (Tidak Tersedia) | ❌ (Tidak Tersedia) |
| **Penerimaan Notifikasi Kepatuhan Obat** | ✅ (Sesuai Wilayah) | ✅ (Sesuai Wilayah) | ✅ (Sesuai Wilayah) | ✅ (Khusus Pasien Binaan) | ✅ (Pengingat Minum Obat Diri) |

---

## 3. MEKANISME PENEGAKAN OTORISASI PADA BACKEND (LARAVEL)

Penegakan batasan wilayah kerja dilakukan pada layer **Eloquent Scope**, **Policy**, dan **Service**, bukan mengandalkan filter query frontend:

### A. Eloquent Scope `scopeAccessibleBy`
Diterapkan pada seluruh model utama:
- `Patient::accessibleBy($user)`
- `PatientTreatment::accessibleBy($user)`
- `MedicationRecord::accessibleBy($user)`
- `TreatmentVisit::accessibleBy($user)`
- `Screening::accessibleBy($user)`
- `CloseContact::accessibleBy($user)`

```php
// Logika Scoping Petugas pada Patient.php
if ($user->isOfficer()) {
    $officer = $user->officer;
    if ($officer->officer_type_id == 1) { // Dinkes Provinsi
        return $query->whereHas('village.subdistrict.district', function ($q) use ($officer) {
            $q->where('province_id', $officer->district?->province_id);
        });
    } elseif ($officer->officer_type_id == 2) { // Dinkes Kab/Kota
        return $query->whereHas('village.subdistrict', function ($q) use ($officer) {
            $q->where('district_id', $officer->district_id);
        });
    } elseif ($officer->officer_type_id == 3) { // PJTB Puskesmas
        return $query->where('puskesmas_id', $officer->puskesmas_id);
    }
}
```

### B. Validasi Policy Penugasan Wilayah Pasien (`PatientPolicy::canAssignTerritory`)
Petugas tidak dapat memindahkan atau mendaftarkan pasien ke wilayah di luar kewenangannya:
- **PJTB Puskesmas**: Hanya dapat mendaftarkan pasien di bawah Puskesmas tempatnya bertugas.
- **Dinkes Kab/Kota**: Hanya dapat mendaftarkan/menugaskan pasien pada Desa/Kelurahan dalam Kabupaten/Kota terkait.
- **Dinkes Provinsi**: Hanya dapat mendaftarkan/menugaskan pasien pada wilayah di dalam Provinsinya.
- **Kader**: Ditolak 403 untuk seluruh aksi pembuatan dan penghapusan pasien master (`can:create`, `can:delete`).

### C. Proteksi Chat & Konsultasi (`ChatAuthorizationService`)
Petugas hanya dapat menerima dan memulai percakapan dengan pengguna (Kader/Pasien) yang berdomisili atau dirawat di fasilitas kesehatan dalam yurisdiksi petugas tersebut. Pasien di luar wilayah yurisdiksi tidak akan muncul dalam daftar kontak konsultasi.

---

## 4. KESIMPULAN VERIFIKASI

Matriks hak akses Role Petugas menjamin:
1. Tidak ada celah eskalasi hak istimewa (Privilege Escalation).
2. Tidak ada kebocoran data antar wilayah (No Territorial Data Leakage).
3. Penyeragaman fitur pada PJTB dan petugas di atasnya terpenuhi 100%.
4. Hak akses dan batasan operasional Role Kader dan Role Pasien tetap terlindungi secara aman.
