# LAPORAN AUDIT KEAMANAN, OTORISASI RBAC, DAN ISOLASI WILAYAH ROLE KADER
**Aplikasi TB Care — Ekosistem Mobile (Flutter) & Web Backend (Laravel)**
*Dokumentasi Audit Keamanan Aplikasi & Penilaian Risiko (Security & RBAC Audit)*

---

## 1. RINGKASAN EKSEKUTIF

Audit keamanan ini dilakukan secara menyeluruh terhadap implementasi fitur Role Kader, penugasan wilayah multi-desa, alur verifikasi minum obat, dan standardisasi nama global. Fokus utama audit adalah memastikan kepatuhan terhadap prinsip *Least Privilege*, pencegahan kerentanan *Insecure Direct Object Reference* (IDOR), isolasi data antar-wilayah penugasan (Territory Scoping), serta integritas mutasi data pasien TB.

Hasil audit mengonfirmasi bahwa seluruh endpoint dan modul telah dilindungi dengan kontrol otorisasi yang kokoh, dengan status **AMAN (SECURE)** dan lulus 100% pada pengujian keamanan regresi otomatis.

---

## 2. MATRIKS EVALUASI KEAMANAN & OWASP TOP 10

| Kategori OWASP | Area Pemeriksaan | Status | Mekanisme Proteksi |
| :--- | :--- | :---: | :--- |
| **A01: Broken Access Control** | Isolasi Pasien Antar Desa | **LULUS** | Query database memvalidasi `WHERE IN (assigned_villages)` berdasarkan token auth Kader. |
| **A01: Broken Access Control** | Verifikasi Minum Obat (IDOR) | **LULUS** | Percobaan verifikasi pasien di luar wilayah kader langsung ditolak dengan status HTTP 403 Forbidden. |
| **A01: Broken Access Control** | Proteksi Profil Wilayah | **LULUS** | Wilayah Kader bersifat Read-Only di mobile; tidak ada rute API mutasi wilayah bagi Kader. |
| **A03: Injection** | Standardisasi Nama & Input | **LULUS** | Helper `NameFormatter` memfilter whitespace dan karakter berbahaya; Eloquent PDO binding mencegah SQL Injection. |
| **A04: Insecure Design** | Batasan Peran Kader | **LULUS** | Kader hanya berperan sebagai pemantau dan verifikator lapangan; tidak dapat mengubah regimen medis atau menghapus data klinis. |
| **A07: Identification and Auth Failures** | Akses Endpoint API | **LULUS** | Seluruh endpoint dilindungi middleware `auth:sanctum` dan `role:kader`. |

---

## 3. AUDIT KONTROL AKSES BERBASIS WILAYAH (TERRITORY SCOPING)

### 3.1 Pencegahan IDOR pada Detail Pasien
Apabila seorang Kader mencoba mengirimkan `GET /api/kader/patients/{id}` atau `POST /api/kader/patients/{id}/verify-medication` untuk pasien yang berada di desa yang tidak ditugaskan kepada kader tersebut:
1. Middleware mengidentifikasi user ID dan role dari token Sanctum.
2. `KaderDataScopingService` mengambil seluruh `village_name` dari tabel `officer_areas` milik user.
3. Query membandingkan wilayah pasien target. Jika tidak ada irisan, sistem melempar `AuthorizationException` yang menghasilkan respons:
   ```json
   {
     "success": false,
     "message": "Anda tidak memiliki wewenang untuk mengakses data pasien di luar wilayah binaan Anda."
   }
   ```
   dengan kode status HTTP `403 Forbidden`.

### 3.2 Isolasi Penugasan Multi-Desa
- Sistem secara akurat mendukung skenario di mana seorang kader memegang 2 atau lebih desa (contoh: Desa Sukahurip dan Setiawargi). Pasien di kedua desa tersebut dapat dimonitor secara legal, namun pasien dari Desa Mulyasari atau desa lain di luar penugasan tetap terisolasi dan terlindungi.

---

## 4. AUDIT INTEGRITAS MUTASI DATA NAMA

1. **Pencegahan XSS / Code Injection pada Nama:**
   Helper `NameFormatter.php` dan `name_formatter.dart` membersihkan spasi berlebih dan hanya memformat karakter alfabet, tanda hubung (`-`), serta tanda kutip tunggal (`'`). Tag HTML (`<script>`, dll.) dinetralkan oleh template engine Blade dan text rendering widget Flutter.
2. **Immutability pada Identitas Teknis:**
   Field `username`, `email`, `password`, dan `nik` tidak terikat pada mutator `NameFormatter`, menjamin integritas login dan identitas akun tetap murni.

---

## 5. HASIL PENGUJIAN OTOMATIS KEAMANAN

Pengujian dilakukan menggunakan PHPUnit pada Laravel Backend:

```text
PASS  Tests\Feature\KaderDataScopingTest
✓ tc01 kader access assigned rt pass
✓ tc02 kader access other assigned rt pass
✓ tc03 kader access any rt in same village pass
✓ tc04 kader access different rw in same village pass
✓ tc05 kader access different village denied (HTTP 403)
✓ tc06 detail patient outside scope denied (HTTP 403)
✓ tc07 delete patient outside scope denied (HTTP 403)
✓ tc08 create patient outside village scope denied (HTTP 403)
✓ tc09 update patient outside village scope denied (HTTP 403)
✓ tc10 treatment patient outside scope denied (HTTP 403)
✓ tc11 treatment visit patient outside scope denied (HTTP 403)

PASS  Tests\Feature\KaderVerificationAndProfileTerritoryTest
✓ kader outside scope cannot verify medication (HTTP 403)
✓ multi desa kader profile consolidates villages
✓ kader verify medication without note succeeds
```

**Kesimpulan Audit:** Seluruh gerbang otorisasi dan kontrol akses terbukti tangguh terhadap skenario eksploitasi dan manipulasi parameter (*tampering*).
