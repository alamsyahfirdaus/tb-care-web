# LAPORAN IMPLEMENTASI WILAYAH KADER MULTI-DESA & KONSOLIDASI PROFIL
**Aplikasi TB Care — Ekosistem Mobile (Flutter) & Web Backend (Laravel)**
*Dokumentasi Teknis Penugasan Wilayah Multi-Desa, Scoping, dan Proteksi Read-Only*

---

## 1. RINGKASAN EKSEKUTIF

Kader kesehatan di Puskesmas seringkali ditugaskan untuk mengawasi lebih dari satu desa atau kelurahan binaan. Pada arsitektur TB Care yang baru, sistem mendukung penugasan multi-desa bagi satu akun kader secara fleksibel, menyajikan ringkasan wilayah yang terstruktur pada profil kader, serta mengamankan hak akses data pasien melalui pengawasan wilayah (territory scoping) berbasis desa yang terintegrasi.

---

## 2. SPESIFIKASI PROSES BISNIS DAN KEBUTUHAN PENGGUNA

### 2.1 Struktur dan Urutan Kartu Profil Kader
Halaman **Profil Saya** (`ProfilePage`) untuk role Kader distandardisasi dengan urutan kartu yang logis dan konsisten:
1. **Kartu 1:** `Informasi Pribadi` (Nama Lengkap, Jenis Kelamin, Tempat/Tanggal Lahir, Nomor Telepon).
2. **Kartu 2:** `Informasi Akun` (Username, Email, Tanggal Bergabung, Terakhir Login).
3. **Kartu 3:** `Wilayah Kader` (Read-Only: Desa/Kelurahan Binaan, Kecamatan, Kota/Kabupaten, Provinsi).

### 2.2 Konsolidasi Penugasan Multi-Desa
- Apabila kader ditugaskan di lebih dari satu desa (misalnya Desa Sukahurip, Desa Setiawargi, dan Desa Mulyasari), nama-nama desa ditampilkan secara terkonsolidasi dan terpisah koma (misal: `Sukahurip, Setiawargi, Mulyasari`).
- Kecamatan, Kota/Kabupaten, dan Provinsi hanya ditampilkan satu kali secara elegan karena seluruh desa penugasan berada di bawah payung wilayah kerja Puskesmas yang sama.

### 2.3 Sifat Read-Only Mutlak
- Kartu `Wilayah Kader` bersifat **Read-Only** murni bagi pengguna dengan role Kader.
- Kader tidak memiliki tombol, form, atau endpoint untuk mengubah wilayah binaannya secara mandiri.
- Seluruh mutasi atau penugasan wilayah kader hanya dapat diatur oleh Penanggung Jawab TB (PJTB) atau Administrator Puskesmas melalui Web Admin.

---

## 3. IMPLEMENTASI BACKEND (LARAVEL)

### 3.1 Konsolidasi Wilayah pada ProfileController
File: `tb-care-web/app/Http/Controllers/Api/ProfileController.php`

Controller memeriksa relasi penugasan wilayah kader (`officer_areas` / `kader_areas`) dan mengonsolidasikan seluruh desa yang ditugaskan:
```php
if ($user->hasRole('kader')) {
    $areas = DB::table('officer_areas')
        ->where('user_id', $user->id)
        ->get();

    $villageNames = $areas->pluck('village_name')->filter()->unique()->values()->all();
    $firstArea = $areas->first();

    $territoryData = [
        'is_multi_village' => count($villageNames) > 1,
        'village_name' => count($villageNames) > 0 ? implode(', ', $villageNames) : ($user->village_name ?? '-'),
        'villages' => $villageNames,
        'subdistrict_name' => $firstArea->subdistrict_name ?? $user->subdistrict_name ?? '-',
        'district_name' => $firstArea->district_name ?? $user->district_name ?? '-',
        'province_name' => $firstArea->province_name ?? $user->province_name ?? '-',
    ];
}
```

### 3.2 Territory Scoping & IDOR Prevention
Pada repository atau query builder pasien (`PatientRepository` / `KaderDataScopingService`), data pasien disaring berdasarkan seluruh desa yang menjadi tugas kader:
```php
$assignedVillages = DB::table('officer_areas')
    ->where('user_id', $kaderId)
    ->pluck('village_name');

$patients = Patient::whereIn('village_name', $assignedVillages)
    ->with(['treatment', 'medications'])
    ->get();
```
Jika kader mencoba mengakses detail, jadwal minum obat, atau melakukan verifikasi pasien dari desa yang tidak tercantum dalam `assignedVillages`, API secara tegas mengembalikan status HTTP `403 Forbidden`.

---

## 4. IMPLEMENTASI FRONTEND (FLUTTER)

### 4.1 Rendering Profil dengan Wilayah Multi-Desa
File: `tb-care-mobile/lib/profile.dart`

```dart
// Kartu 3: Wilayah Kader (Read-Only)
if (userRole == 'kader') ...[
  const SizedBox(height: 16),
  Container(
    width: double.infinity,
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      border: Border.all(color: const Color(0xFFE2E8F0)),
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Wilayah Kader',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 12),
        _buildInfoRow(
          icon: Icons.holiday_village_outlined,
          label: 'Desa / Kelurahan',
          value: territoryVillages.isNotEmpty
              ? territoryVillages.join(', ')
              : (userData['village_name'] ?? '-'),
        ),
        _buildInfoRow(
          icon: Icons.location_city_outlined,
          label: 'Kecamatan',
          value: userData['subdistrict_name'] ?? '-',
        ),
        _buildInfoRow(
          icon: Icons.map_outlined,
          label: 'Kota / Kabupaten',
          value: userData['district_name'] ?? '-',
        ),
        _buildInfoRow(
          icon: Icons.public_outlined,
          label: 'Provinsi',
          value: userData['province_name'] ?? '-',
        ),
      ],
    ),
  ),
],
```

---

## 5. HASIL PENGUJIAN OTOMATIS

1. **Laravel Feature Test (`tests/Feature/KaderVerificationAndProfileTerritoryTest.php`):**
   - `✓ test_multi_desa_kader_profile_consolidates_villages`: Memvalidasi penugasan 3 desa berbeda pada satu kader menghasilkan string koma yang valid pada response API profil dan array `villages` yang lengkap.
   - `✓ test_kader_outside_scope_cannot_verify_medication`: Memastikan percobaan verifikasi pada pasien di luar daftar desa kader diblokir dengan HTTP 403.
2. **Flutter Widget Test (`test/kader_profile_territory_and_notification_test.dart`):**
   - `✓ ProfilePage shows notification icon in AppBar and displays read-only "Wilayah Kader" card for Kader`: Memvalidasi kartu Wilayah Kader dirender pada urutan ke-3 tanpa opsi edit apapun.
