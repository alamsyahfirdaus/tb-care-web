# DOKUMENTASI REVISI DESAIN HALAMAN JADWAL KONTROL — ROLE PASIEN TB CARE

Dokumen ini mencatat seluruh implementasi pembaruan desain UI/UX pada halaman Jadwal Kontrol khusus **Role Pasien** di aplikasi mobile TB Care beserta integrasinya dengan backend Laravel.

---

## 1. RINGKASAN TUJUAN PERUBAHAN

Berdasarkan evaluasi terhadap kenyamanan visual dan pengalaman pengguna (UI/UX) pasien TB, halaman jadwal yang sebelumnya bernama "Jadwal Kunjungan" disederhanakan agar lebih bersih, mudah dibaca, informatif, dan tidak membebani pasien dengan badge status/ikon berulang.

Perubahan difokuskan pada:
1. **Perubahan Judul:** Mengganti "Jadwal Kunjungan" menjadi **"Jadwal Kontrol"**.
2. **Penghapusan Tab Bar:** Menggabungkan tab *Akan Datang* dan *Semua Kunjungan* menjadi satu tampilan daftar vertikal (*single scrollable list*).
3. **Pemisahan Seksi & Pengurutan:**
   - **Jadwal Mendatang:** Berisi jadwal kontrol yang akan datang, diurutkan kronologis secara menaik (*ascending* / terdekat ke terjauh).
   - **Riwayat Jadwal Kontrol:** Berisi jadwal kontrol yang telah berlalu, dibatalkan, atau selesai, diurutkan menurun (*descending* / terbaru di atas).
4. **Pembersihan Card Jadwal Kontrol:**
   - Menghapus badge status (`Terjadwal` / `Terlewat`).
   - Menghapus badge pengingat (`30 menit sebelumnya` dan ikon lonceng).
   - Menghapus seluruh ikon kalender, jam, dan lokasi.
   - Meratakan teks ke kiri (*left-aligned*) dengan hierarki tipografi yang jelas (Tanggal bold, Jam berwarna aksen primer, Lokasi abu-abu).
5. **Standardisasi Format Lokasi:**
   - Menampilkan lokasi dengan format lengkap: `Puskesmas [Nama Puskesmas], [Nama Kecamatan], [Nama Kabupaten/Kota]`.
   - Mengatasi kasus Puskesmas/alamat belum lengkap secara anggun tanpa memunculkan kata `null` atau koma berlebih.
6. **Card Pengingat Kontrol:**
   - Mempertahankan kartu pengaturan pengingat di bagian atas dengan judul **"Pengingat Kontrol"**.
   - Menyediakan toggle switch aktif/nonaktif dan pilihan interval pengingat (*chips*): **30 Menit**, **1 Jam**, **2 Jam**, **1 Hari**, dan **Tepat Waktu**.
7. **Empty State Bersih:**
   - Saat tidak ada jadwal: Ikon kalender kosong dengan judul **"Belum Ada Jadwal Kontrol"** dan deskripsi *"Jadwal kontrol Anda akan ditampilkan di sini."*.

---

## 2. DETAIL PERUBAHAN TAMPILAN (UI/UX)

### A. Perbandingan Struktur Tampilan Card

| Elemen | Sebelum Perubahan | Sesudah Perubahan |
|---|---|---|
| **App Bar** | Jadwal Kunjungan | Jadwal Kontrol |
| **Navigasi Tab** | TabBar: [Akan Datang] & [Semua Kunjungan] | Dihapus (Single scrollable list) |
| **Pengingat Atas** | Card "Pengingat Kunjungan" | Card "Pengingat Kontrol" |
| **Badge Status Card** | Badge Hijau "Terjadwal" / Kuning "Terlewat" | **Dihapus total** |
| **Badge Pengingat Card** | Ikon lonceng + "30 menit sebelumnya" | **Dihapus total** |
| **Ikon Tanggal** | Ikon kalender kotak di sebelah tanggal | **Dihapus** (Teks tanggal rata kiri) |
| **Ikon Waktu** | Ikon jam di sebelah waktu WIB | **Dihapus** (Teks waktu rata kiri) |
| **Ikon Lokasi** | Ikon pin lokasi di sebelah Puskesmas | **Dihapus** (Teks lokasi rata kiri) |
| **Format Lokasi** | Hanya nama Puskesmas singkat (misal: "Tamansari") | "Puskesmas Tamansari, Tamansari, Kota Tasikmalaya" |
| **Seksi Daftar** | Terpisah berdasarkan tab aktif | 2 Seksi langsung: "Jadwal Mendatang" & "Riwayat Jadwal Kontrol" |

---

## 3. IMPLEMENTASI TEKNIS

### A. Flutter Mobile (`tb-care-mobile`)

#### 1. File: `lib/main/pasien/visit_schedule_page.dart`
- Menghapus `SingleTickerProviderStateMixin` dan `TabController`.
- Menjadikan `SingleChildScrollView` dengan `RefreshIndicator` untuk memuat ulang daftar.
- Menambahkan method `_getUpcomingVisits()` dengan sorting *ascending*:
  ```dart
  list.sort((a, b) => dtA.compareTo(dtB));
  ```
- Menambahkan method `_getPastVisits()` dengan sorting *descending*:
  ```dart
  list.sort((a, b) => dtB.compareTo(dtA));
  ```
- Menambahkan helper pembentukan alamat Puskesmas:
  ```dart
  String _formatPuskesmasLocation(dynamic visit) {
    if (visit is Map) {
      final loc = visit['puskesmas_location']?.toString();
      if (loc != null && loc.trim().isNotEmpty) {
        return _ensurePuskesmasPrefix(loc.trim());
      }
      final name = visit['puskesmas_name']?.toString();
      if (name != null && name.trim().isNotEmpty) {
        return _ensurePuskesmasPrefix(name.trim());
      }
    }
    if (widget.puskesmasName.trim().isNotEmpty) {
      return _ensurePuskesmasPrefix(widget.puskesmasName.trim());
    }
    return 'Puskesmas';
  }
  ```
- Membersihkan `_buildVisitItemCard()` dari badge status, badge notifikasi, serta ikon kalender, jam, dan lokasi pin.

#### 2. File: `lib/alarm_service.dart`
- Memperbarui label channel notifikasi menjadi:
  - Title: `Jadwal Kontrol Puskesmas`
  - Body: `Waktunya kontrol ke $puskesmasName. Jangan lupa bawa buku kontrol TB Anda.`

### B. Laravel Backend (`tb-care-web`)

#### 1. File: `app/Models/Puskesmas.php`
- Menambahkan accessor `full_address` untuk memformat alamat faskes:
  ```php
  public function getFullAddressAttribute(): string
  {
      $parts = [];
      $parts[] = 'Puskesmas ' . preg_replace('/^puskesmas\s+/i', '', trim($this->name));
      if ($this->subdistrict) {
          $parts[] = trim($this->subdistrict->name);
          if ($this->subdistrict->district) {
              $parts[] = trim($this->subdistrict->district->name);
          }
      }
      return implode(', ', array_filter($parts));
  }
  ```

#### 2. File: `app/Http/Controllers/Api/TreatmentVisitController.php`
- Eager load relasi Puskesmas dan wilayah: `puskesmas.subdistrict.district`.
- Menyertakan atribut `puskesmas_location` pada setiap objek respons jadwal kontrol.
- Memperbarui pesan respons sukses dan validasi:
  - `Jadwal kontrol berhasil dibuat`
  - `Jadwal kontrol berhasil dibatalkan`
  - `Jadwal kontrol berhasil dihapus`

#### 3. File: `app/Http/Controllers/Api/PatientController.php`
- Memperbarui `patientHome`, `patientData`, dan `upcomingVisits` agar menyertakan relasi `treatment.treatmentVisits.puskesmas.subdistrict.district` dan field `puskesmas_location`.

---

## 4. VERIFIKASI DAN PENGUJIAN

| Pengujian | Perintah | Status | Keterangan |
|---|---|---|---|
| **Flutter Static Analysis** | `flutter analyze` | **PASS (0 issues)** | Tidak ada error atau warning. |
| **Widget Test Halaman Kontrol** | `flutter test test/patient_visit_schedule_refinement_test.dart` | **PASS (2/2)** | Memvalidasi judul, absennya tab bar, ketiadaan ikon & badge pada card, seksi mendatang/riwayat, dan format Puskesmas. |
| **Backend Authorization & Security** | `php artisan test --filter=PatientAuthorizationSecurityTest` | **PASS (9/9)** | Hak akses pasien tetap aman (read-only untuk kontrol). |
| **Backend Treatment Visit API** | `php artisan test --filter=TreatmentVisitApiTest` | **PASS (2/2)** | Endpoint jadwal kontrol pasien dan proteksi IDOR lulus 100%. |
| **Backend Patient Home API** | `php artisan test --filter=PatientHomeApiTest` | **PASS (3/3)** | Data beranda pasien memuat data kontrol mendatang dengan benar. |
