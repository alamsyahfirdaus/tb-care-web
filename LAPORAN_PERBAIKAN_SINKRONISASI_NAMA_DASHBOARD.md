# LAPORAN KOMPREHENSIF PERBAIKAN SINKRONISASI NAMA PENGGUNA PADA DASHBOARD TB CARE (SELURUH ROLE)

---

## 1. RINGKASAN EKSEKUTIF

Laporan ini mendokumentasikan analisis akar masalah, perancangan arsitektur, implementasi perbaikan kode, dan hasil pengujian menyeluruh terkait masalah **sinkronisasi nama pengguna pada sapaan Dashboard TB Care** di seluruh role:
1. **Role Pasien TB** (`HomePage`)
2. **Role Kader Puskesmas** (`KaderHomePage`)
3. **Role Petugas TB** (`StaffHomePage`): PJTB Puskesmas, Petugas Dinkes Kabupaten/Kota, dan Petugas Dinkes Provinsi.

### Deskripsi Masalah Awal:
Saat pengguna melakukan pembaruan nama pada halaman Profil (`ProfileEditPage`) dan menyimpannya (misalnya dari *"Alamsyah Firdaus"* menjadi *"Alamsyah Firdaus, S.Pd., M.Kom."*), sapaan pada Dashboard pengguna tetap menampilkan nama lama sebelum pengeditan. Pengguna harus melakukan logout dan login ulang agar nama baru muncul di Dashboard.

### Hasil Akhir Perbaikan:
Setelah perbaikan diterapkan:
- Nama pengguna pada sapaan Dashboard langsung diperbarui secara instan (**real-time/reaktif**) begitu profil berhasil disimpan tanpa perlu logout/login.
- Perubahan nama tersinkronisasi secara persisten ke database server (Laravel), penyimpanan lokal (`SharedPreferences`), reaktif state (`ValueNotifier`), dan cache beranda pasien (`PatientHomeService`).
- Kompatibel penuh dengan gelar akademik, singkatan profesional (seperti `S.Pd., M.Kom.`, `dr. Budi Santoso, Sp.P`), nama dengan tanda hubung/apostrof.
- Seluruh 255 pengujian backend (`php artisan test`) dan seluruh pengujian mobile (`flutter test`) lulus 100% dengan status analisis bersih (`flutter analyze: No issues found!`).

---

## 2. ANALISIS AKAR MASALAH (ROOT CAUSE ANALYSIS)

Melalui audit penelusuran alur data (*data-flow audit*) dari Frontend Flutter ke Backend Laravel, ditemukan 6 penyebab utama masalah:

### A. Ketiadaan Sinkronisasi Balik ke `SharedPreferences` pada `edit_profile.dart`
Saat form pengeditan profil dikirimkan via `PUT /api/profile/update` dan server mengembalikan kode HTTP `200 OK`, `ProfileEditPage` hanya menampilkan `SnackBar` notifikasi berhasil dan melakukan `Navigator.pop(context, true)`. Data `user_name` pada `SharedPreferences` tidak pernah diperbarui dengan nama baru yang disimpan.

### B. Header Profil pada `profile.dart` Tidak Menyinkronkan Nama Lokal
Saat `ProfilePage._loadUserData()` mengambil data terbaru dari endpoint `GET /api/profile/show`, data tersebut hanya disimpan di variabel lokal `_userData` milik `_ProfilePageState`. Nilai nama tidak disinkronkan kembali ke `SharedPreferences` maupun disiarkan ke dashboard.

### C. State Dashboard Petugas Menggunakan `widget.name` yang *Immutable*
Pada `StaffHomePage` (`lib/main/petugas/home.dart`), sapaan dashboard dibangun dengan kode:
```dart
Text(NameFormatter.formatPersonName(widget.name))
```
Karena `widget.name` dioper saat inisialisasi login atau splash screen, nilainya bersifat *immutable* selama instance `StaffHomePage` hidup. Saat pengguna berpindah tab ke Profil dan kembali ke Beranda melalui `IndexedStack`, `StaffHomePage` tidak pernah membaca ulang nama terbaru dari penyimpanan atau server.

### D. Prioritas Nama pada Dashboard Kader Mengabaikan Data Profil
Pada `KaderHomePage` (`lib/main/kader/home.dart`), sapaan dibangun dengan logika:
```dart
final rawKaderName = widget.name.isNotEmpty
    ? widget.name
    : (_kaderInfo['name']?.toString().isNotEmpty == true
        ? _kaderInfo['name'].toString()
        : 'Kader Puskesmas');
```
Karena `widget.name` selalu terisi sejak login, evaluasi operator ternary selalu berhenti pada `widget.name` dan mengabaikan nilai `_kaderInfo['name']` maupun data profil terbaru.

### E. Cache Beranda Pasien Menyimpan Nama Lama (*Stale Cache*)
Pada Role Pasien, `PatientHomeService` menyimpan respons beranda ke dalam cache lokal `cached_patient_home_${patientId}`. Saat pengguna berpindah tab atau menutup aplikasi, `HomePage` memuat data dari cache tersebut terlebih dahulu. Karena cache tidak diinvalidasi atau diperbarui saat pengeditan profil, nama lama tetap muncul di layar.

### F. Format Nama Memotong Gelar Akademik
`NameFormatter.formatPersonName` sebelumnya memaksakan huruf kecil setelah huruf pertama untuk setiap kata (`word[0].toUpperCase() + word.substring(1).toLowerCase()`). Hal ini menyebabkan gelar akademik seperti `S.Pd.` berubah menjadi `S.pd.` dan `M.Kom.` menjadi `M.kom.`.

---

## 3. IMPLEMENTASI PERBAIKAN ARSITEKTURAL

### A. Backend Laravel: Paritas Data `show()` dan `update()` (`ProfileController.php`)
Dibuat fungsi terpadu `formatUserData(User $user): array` yang digunakan bersama oleh endpoint `GET /api/profile/show` dan `PUT /api/profile/update`. Hal ini menjamin bahwa respons saat menyimpan profil selalu mengembalikan objek data pengguna yang lengkap dan konsisten (termasuk `name`, atribut wilayah, puskesmas, dan gelar).

### B. Mobile Flutter: Reaktif State Global (`SessionManager.dart`)
Pada `SessionManager`, diperkenalkan:
1. `static final ValueNotifier<String> userNameNotifier = ValueNotifier<String>('');`: Notifier reaktif global yang dapat diobservasi oleh semua widget dashboard secara langsung (*Observer Pattern*).
2. `static Future<void> updateUserName(String newName)`: Memperbarui nilai `userNameNotifier.value` dan menyimpannya secara asinkron ke `SharedPreferences` (`user_name`).
3. `static Future<String> getUserName()`: Mengambil nama pengguna aktif dari notifier atau SharedPreferences.
4. Pembersihan otomatis `userNameNotifier.value = ''` saat sesi dihapus (`clearUserSessionOnly` dan `logout`).

### C. Pembaruan Form Edit Profil (`edit_profile.dart`)
Setelah respons HTTP `200 OK` diterima dari `PUT /api/profile/update`:
```dart
String updatedName = _nameController.text.trim();
try {
  final resJson = jsonDecode(response.body);
  if (resJson is Map && resJson['data'] is Map && resJson['data']['name'] != null) {
    final serverName = resJson['data']['name'].toString().trim();
    if (serverName.isNotEmpty) updatedName = serverName;
  }
} catch (_) {}

await SessionManager.updateUserName(updatedName);

if (_isPatient) {
  await PatientHomeService.updateCachedPatientName(updatedName);
  await PatientHomeService.clearCache();
}
```

### D. Pembaruan Halaman Profil (`profile.dart`)
Pada `_loadUserData()`, saat memuat profil dari `GET /api/profile/show`, data nama disinkronkan kembali ke `SessionManager.updateUserName(rawName)` agar data lokal selalu selaras dengan database backend.

### E. Sinkronisasi Dashboard Role Petugas (`StaffHomePage`)
- Menambahkan state lokal `String _currentName = '';`.
- Menghubungkan listener `SessionManager.userNameNotifier.addListener(_onUserNameChanged)` pada `initState()`, dan melepaskannya pada `dispose()`.
- Menambahkan fungsi `_initStaffName()` untuk membaca nama dari `SessionManager`.
- Pada `onDestinationSelected(index)`, saat pengguna kembali ke tab 0 (Beranda), memicu `_initStaffName()` dan `_loadOfficerInfo()` di latar belakang.
- Tampilan sapaan menggunakan:
  ```dart
  Text(
    NameFormatter.formatPersonName(
      _currentName.isNotEmpty ? _currentName : widget.name,
    ),
    maxLines: 1,
    overflow: TextOverflow.ellipsis,
    ...
  )
  ```

### F. Sinkronisasi Dashboard Role Kader (`KaderHomePage`)
- Menambahkan state lokal `String _currentName = '';`.
- Mengamati `SessionManager.userNameNotifier`.
- Pada `_buildWelcomeCard()`, memprioritaskan `_currentName`:
  ```dart
  final effectiveName = _currentName.isNotEmpty
      ? _currentName
      : (_kaderInfo['name']?.toString().isNotEmpty == true
          ? _kaderInfo['name'].toString()
          : (widget.name.isNotEmpty ? widget.name : 'Kader Puskesmas'));
  final kaderName = NameFormatter.formatPersonName(effectiveName);
  ```
- Pada `onDestinationSelected(index)` tab 0, memicu pembaruan `_initKaderName()` dan `_fetchDashboardData()`.

### G. Sinkronisasi Dashboard Role Pasien (`HomePage`)
- Menambahkan state lokal `String _currentName = '';`.
- Mengamati `SessionManager.userNameNotifier`.
- Memperbarui cache lokal melalui `PatientHomeService.updateCachedPatientName(...)`.
- Pada `onDestinationSelected(index)` tab 0, memicu `_initPatientName()` dan `_fetchData(isBackground: true)`.
- Menggunakan `NameFormatter.formatPersonName(effectiveName)` dengan pembungkusan layout adaptif `Expanded` untuk mencegah `RenderFlex overflow`.

### H. Penyempurnaan Format Gelar & Gelar Akademik (`NameFormatter.dart`)
Mendukung partikel gelar bertitik (seperti `S.Pd.`, `M.Kom.`, `dr.`, `Sp.P`) serta mempertahankan tanda koma pemisah gelar tanpa merusak format Title Case pada nama dasar.

---

## 4. HASIL PENGUJIAN DAN VERIFIKASI

### A. Pengujian Otomatis Flutter (`flutter test`)
Dibuat pengujian unit dan widget terintegrasi pada `test/dashboard_user_name_sync_test.dart` dan `test/name_formatter_test.dart`:

| Modul Uji | Skenario | Hasil |
|---|---|---|
| `SessionManager` & Notifier | Pembaruan nama sinkron ke `SharedPreferences` dan `userNameNotifier` | **PASSED** |
| `PatientHomeService` Cache | Pembaruan nama pasien pada cache beranda lokal tanpa merusak data klinis | **PASSED** |
| `StaffHomePage` (Role Petugas) | Sapaan dashboard PJTB/Petugas langsung berubah saat profil diupdate | **PASSED** |
| `KaderHomePage` (Role Kader) | Welcome card kader langsung berubah saat profil diupdate | **PASSED** |
| `HomePage` (Role Pasien) | Greeting header pasien langsung berubah saat profil diupdate | **PASSED** |
| `NameFormatter` | Standarisasi gelar akademik, apostrof, nama berspasi ganda, dan nama bertitik | **PASSED** (7/7) |
| `KaderDashboardRedesignTest` | Pengujian regresi dashboard kader | **PASSED** (4/4) |
| `HomePageWidgetTest` | Pengujian regresi dashboard beranda pasien | **PASSED** (3/3) |

### B. Analisis Statis Kode Flutter (`flutter analyze`)
```bash
$ cd tb-care-mobile; flutter analyze
Analyzing tb-care-mobile...
No issues found! (ran in 18.0s)
```
Status: **0 errors, 0 warnings, 0 infos**.

### C. Pengujian Backend Laravel (`php artisan test`)
```bash
$ cd tb-care-web; php artisan test
  Tests: 255 passed
  Time:  56.44s
```
Status: **255 passed, 0 failed**. Seluruh fitur API, autentikasi, otorisasi RBAC wilayah, kontrol pengobatan, dan profil berjalan sempurna.

---

## 5. KESIMPULAN

Perbaikan sinkronisasi nama pengguna pada Dashboard TB Care telah diselesaikan secara tuntas dan menyeluruh pada source code **Flutter Mobile** dan **Laravel Backend**:
1. Sapaan Dashboard langsung menampilkan nama terbaru pengguna secara instan tanpa perlu logout dan login kembali.
2. Sinkronisasi berlaku adil dan konsisten pada **Role Pasien**, **Role Kader**, dan **Role Petugas** (PJTB Puskesmas, Dinkes Kab/Kota, Dinkes Provinsi).
3. Data tersimpan secara konsisten di database server, penyimpanan lokal perangkat, cache aplikasi, dan *state management*.
4. Layout UI, kartu dashboard, tipografi, warna, dan hirarki fitur tetap terlindungi tanpa adanya regresi atau gangguan visual (*zero visual and functional regression*).
