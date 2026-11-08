# LAPORAN PROFIL DAN WILAYAH BINAAN KADER

**Tanggal Implementasi:** 8 November 2026  
**Tim Pengembang:** Senior Backend & Mobile Developer  
**Status:** Selesai, Teruji 100%, Keamanan Terverifikasi

---

## 1. Latar Belakang & Persyaratan Keamanan

Kader kesehatan memiliki wilayah binaan resmi yang ditetapkan oleh Puskesmas atau Dinas Kesehatan. Untuk transparansi informasi namun tetap menjaga integritas data:
1. Kader harus dapat melihat wilayah binaannya pada profil aplikasi.
2. Wilayah binaan harus **SEPENUHNYA READ-ONLY** bagi kader.
3. Kader **TIDAK DAPAT** mengubah, memanipulasi, atau mengedit wilayah binaan melalui aplikasi mobile.
4. Perubahan wilayah binaan hanya boleh dilakukan oleh Administrator melalui Web Admin TB Care.

---

## 2. Implementasi Backend (`tb-care-web`)

File: `app/Http/Controllers/Api/ProfileController.php`

### A. Penyediaan Data Wilayah Binaan pada `/api/profile/show`
Backend memuat relasi wilayah kader dari tabel `officer_areas` atau `officers`:
```php
if ($user->officer && ($isKader || (int)$user->officer->officer_type_id === 4)) {
    $areas = $user->officer->areas()->with(['village.subdistrict.district.province'])->get();
    ...
    $userData['kader_territory'] = [
        'village_name'      => $vilName ?? '-',
        'subdistrict_name'  => $subName ?? '-',
        'district_name'     => $distName ?? '-',
        'province_name'     => $provName ?? '-',
        'formatted_address' => $formatted,
    ];
}
```

### B. Proteksi Otorisasi Mutasi pada `/api/profile/update`
Metode `update` pada `ProfileController` secara ketat memblokir percobaan mutasi wilayah oleh non-pasien:
```php
$territoryFields = ['village_id', 'subdistrict_id', 'district_id', 'province_id', 'puskesmas_id'];
$hasTerritoryPayload = false;
foreach ($territoryFields as $tf) {
    if ($request->has($tf)) {
        $hasTerritoryPayload = true;
        break;
    }
}

if ($hasTerritoryPayload && (int)$user->user_type_id !== 2) {
    return response()->json([
        'success' => false,
        'message' => 'Anda tidak memiliki wewenang untuk mengubah wilayah binaan atau fasilitas kesehatan secara mandiri.',
    ], 403);
}
```

---

## 3. Implementasi Frontend Mobile (`tb-care-mobile`)

File: `lib/profile.dart`

Card informasi baru ditambahkan tepat di bawah card `Informasi Pribadi`:
- **Judul Card**: `"Wilayah Kader"`
- **Komponen**:
  1. `Desa / Kelurahan`: Nama Desa/Kelurahan binaan (misal `Setiamulya`).
  2. `Kecamatan`: Nama Kecamatan (misal `Tamansari`).
  3. `Kota / Kabupaten`: Nama Kota/Kabupaten (misal `Kota Tasikmalaya`).
  4. `Provinsi`: Nama Provinsi (misal `Jawa Barat`).
- **Sifat**: Bersifat read-only menggunakan `_buildInfoItem` tanpa action button ubah data. Card ini tidak ditampilkan untuk pasien atau role non-kader.

---

## 4. Hasil Verifikasi Pengujian

| Pengujian | Hasil |
|---|---|
| Tampilan Mobile | Card `Wilayah Kader` tampil lengkap untuk kader | PASS |
| Non-Kader (Pasien) | Card `Wilayah Kader` tidak ditampilkan untuk pasien | PASS |
| Backend Rejection | Kader mengirim payload update wilayah -> HTTP 403 | PASS (`KaderVerificationAndProfileTerritoryTest`) |
| Regresi Laravel | 222 / 222 tests pass | PASS |
| Flutter Mobile Test | `kader_profile_territory_and_notification_test.dart` pass | PASS |
