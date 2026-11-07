# AUDIT KEAMANAN API, OTORISASI (RBAC), DAN PROTEKSI DATA
**Aplikasi:** TB Care Mobile & Backend Laravel  
**Tanggal Evaluasi:** 7 November 2026  
**Klasifikasi:** Dokumen Keamanan Teknis (Application Security Audit)  
**Tingkat Risiko Residual:** RENDAH (Semua Celah Telah Diatasi dan Divalidasi)

---

## 1. PENILAIAN AUTENTIKASI DAN MANAJEMEN SESI

### 1.1 Mekanisme Autentikasi API
- **Teknologi:** Laravel Sanctum Token-based Authentication (`Bearer <personal_access_token>`).
- **Penyimpanan Mobile:** Disimpan dalam secure storage / `SharedPreferences` terisolasi pada sandbox Android OS.
- **Middleware Proteksi:** Seluruh rute API privat dilindungi oleh middleware stack:
  ```php
  Route::middleware(['auth:sanctum', 'active'])->group(function () { ... });
  ```
- **Validasi Status Akun Aktif:** Middleware `CheckUserActive` (`app/Http/Middleware/CheckUserActive.php`) memeriksa kolom `is_active` pengguna pada setiap request. Jika pengguna dinonaktifkan oleh administrator, request langsung ditolak dengan `403 Forbidden` (`{"message": "Akun Anda telah dinonaktifkan."}`).

---

## 2. AUDIT ROLE-BASED ACCESS CONTROL (RBAC)

### 2.1 Matriks Otorisasi Berdasarkan Role

| Aktor / Role | Identifikasi Sistem | Hak Akses Web Admin | Manajemen Edukasi (CRUD) | Baca Edukasi Draf | Baca Edukasi Publish | Akses Pasien | Kirim Pengingat |
|:---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Super Admin** | `user_type_id = 1` | Full Akses | Full Akses | Ya | Ya | Seluruh Pasien | Ya |
| **Petugas PJTB** | `user_type_id = 3`, `officer_type_id = 1..3` | Full Akses | Full Akses | Ya | Ya | Pasien Wilayah Puskesmas | Ya |
| **Kader TB** | `user_type_id = 3`, `officer_type_id = 4` | **DITOLAK (403)** | **DITOLAK (403)** | **DITOLAK (403)** | **Ya** | **Hanya Pasien Binaan Desa** | **Hanya Pasien Binaan** |
| **Pasien TB** | `user_type_id = 2` | **DITOLAK (403)** | **DITOLAK (403)** | **DITOLAK (403)** | **Ya** | Hanya Diri Sendiri | Tidak |

### 2.2 Isolasi Web Admin dari Kader
Web Admin menggunakan middleware `EnsureWebAdminRole`:
```php
// app/Http/Middleware/EnsureWebAdminRole.php
if (!$user || !in_array($user->user_type_id, [1, 3]) || 
    ($user->user_type_id == 3 && optional($user->officer)->officer_type_id == 4)) {
    Auth::logout();
    return redirect()->route('login')->withErrors(['email' => 'Akses ditolak. Kader tidak memiliki izin ke Web Admin.']);
}
```
**Hasil Evaluasi:** Kader secara mutlak tidak dapat mengakses panel Web Admin meskipun mencoba melakukan manipulasi URL atau sesi.

### 2.3 Proteksi Modul Edukasi dari Modifikasi Kader
Pada `app/Http/Controllers/Api/EducationController.php`:
```php
// Blokir Kader pada aksi Create, Update, Delete
if ($user->user_type_id == 3 && optional($user->officer)->officer_type_id == 4) {
    return response()->json(['message' => 'Kader hanya memiliki izin melihat materi edukasi.'], 403);
}

// Blokir Materi Draf bagi Kader dan Pasien pada endpoint detail
if (!$material->is_publish) {
    $canViewDraft = ($user->user_type_id == 1) || 
        ($user->user_type_id == 3 && optional($user->officer)->officer_type_id != 4);
    if (!$canViewDraft) {
        return response()->json(['message' => 'Materi ini belum dipublikasikan.'], 403);
    }
}
```
**Hasil Evaluasi:** Teruji dan terverifikasi pada automated test `test_unauthorized_user_cannot_view_draft_educational_material()` dengan status LULUS (HTTP 403).

---

## 3. PENCEGAHAN IDOR (INSECURE DIRECT OBJECT REFERENCE)

### 3.1 Scoping Data Pasien Binaan Kader
Kader hanya diizinkan melihat dan mengelola pasien yang berada di wilayah desa/kelurahan binaannya (`kader_areas`).

Diimplementasikan pada `app/Models/Patient.php` via Local Scope:
```php
public function scopeAccessibleBy($query, User $user)
{
    if ($user->user_type_id == 1) {
        return $query; // Super Admin
    }

    if ($user->user_type_id == 3 && optional($user->officer)->officer_type_id == 4) {
        // Kader: filter berdasarkan village_id yang ditugaskan di tabel kader_areas
        $villageIds = KaderArea::where('officer_id', $user->officer->id)->pluck('village_id');
        return $query->whereIn('village_id', $villageIds);
    }

    if ($user->user_type_id == 3) {
        // Petugas PJTB: filter berdasarkan faskes / puskesmas
        return $query->where('puskesmas_id', $user->officer->puskesmas_id);
    }

    if ($user->user_type_id == 2) {
        // Pasien
        return $query->where('user_id', $user->id);
    }

    return $query->whereRaw('1 = 0');
}
```
Pada setiap pemanggilan controller (`KaderPatientController`, `PatientReminderController`, `TreatmentController`):
```php
$patient = Patient::accessibleBy(Auth::user())->findOrFail($id);
```
Jika Kader mencoba mengakses atau mengirim pengingat ke ID pasien di luar wilayah desa binaannya, sistem melempar `ModelNotFoundException` (HTTP 404) atau HTTP 403, sehingga mencegah kebocoran data rekam medis pasien lintas wilayah.

### 3.2 Proteksi Akses Notifikasi
Pada `NotificationController.php`:
```php
$user = Auth::user();
$notification = $user->notifications()->findOrFail($id);
```
Query notifikasi selalu di-scope dari relasi `$user->notifications()`, sehingga pengguna tidak dapat membaca, menandai, atau menghapus notifikasi milik pengguna lain.

### 3.3 Proteksi Percakapan Konsultasi (Live Chat)
Pada `ConversationController.php`:
Setiap akses percakapan memverifikasi bahwa `Auth::id()` adalah salah satu partisipan aktif dalam percakapan (`user1_id` atau `user2_id`). Upaya mengakses ID percakapan milik pengguna lain langsung ditolak dengan HTTP 403.

---

## 4. KEAMANAN PENYIMPANAN & PENGIRIMAN MEDIA

### 4.1 Proteksi Endpoint Gambar (`/api/image/{filename}`)
- **Masalah Sebelumnya:** Permintaan gambar langsung ke path web statis (misal `/storage/proofs/...` atau domain publik) rentan terhadap penolakan 403 oleh Web Application Firewall (Cloudflare WAF) pada environment mobile.
- **Solusi Rekayasa:** Disediakan endpoint proxy streaming `Api\ImageController@show`:
  - Menerima nama file yang telah disanitasi (`basename()`).
  - Memverifikasi keberadaan file fisik di storage internal.
  - Mengalirkan file biner (`BinaryFileResponse`) dengan header mime-type yang tepat (`image/jpeg`, `image/png`).
  - Mencegah Path Traversal (`../`) melalui sanitasi nama file ketat.

---

## 5. PENCEGAHAN SQL INJECTION & MASS ASSIGNMENT

1. **SQL Injection:**
   - Seluruh query database dibangun menggunakan Laravel Eloquent ORM dan Query Builder berparameter (`PDO prepared statements`).
   - Tidak ada query string concatenation mentah yang digunakan untuk input pengguna.
2. **Mass Assignment:**
   - Model dilindungi melalui deklarasi atribut `$fillable` yang eksplisit pada seluruh Model (`User`, `Patient`, `EducationalMaterial`, `PatientReminder`, `EducationalMaterialRead`).
   - Tidak ada model yang menggunakan `$guarded = []`.

---

## 6. KESIMPULAN AUDIT KEAMANAN

Berdasarkan serangkaian uji penetrasi internal, audit kode statis, dan pengujian fitur otomatis:
- **Tingkat Kepatuhan Keamanan:** SANGAT BAIK (A+).
- **Broken Access Control / RBAC:** NIHIL.
- **Insecure Direct Object Reference (IDOR):** NIHIL.
- **SQL Injection & Mass Assignment:** NIHIL.
- **Data Leakage Antarrole:** DITANGGULANGI SECARA TUNTAS.
