# LAPORAN STATUS MASALAH TERSISA (REMAINING ISSUES REPORT)
**TB Care Technical Assessment: Unresolved Items, Risks & Next Steps**
**Tanggal Audit:** 06 November 2026

---

## 1. STATUS MASALAH KRITIS & TINGGI (CRITICAL & HIGH ISSUES)

- **Masalah Kritis Tersisa:** **NIHIL (0 Masalah)**.
- **Masalah Berisiko Tinggi Tersisa:** **NIHIL (0 Masalah)**.
- Seluruh bug arsitektur, kegagalan test suite relasi model, migrasi skema database yang tertunda, dan inkonsistensi RBAC telah berhasil diidentifikasi, diperbaiki di tingkat *root cause*, dan diverifikasi ulang secara otomatis.

---

## 2. OBSERVASI TEKNIKAL & TINDAKAN LANJUTAN (OPERATIONAL ADVISORIES)

Meskipun sistem telah 100% lulus audit dan pengujian, berikut beberapa rekomendasi operasional untuk persiapan *Production Release*:

### 1. Peringatan Migrasi Built-in Kotlin pada Android Gradle
- **Kategori:** Warning / Technical Debt Lingkungan Android
- **Deskripsi:** Saat menjalankan `flutter build apk`, Gradle memunculkan peringatan bahwa plugin berikut masih menerapkan Kotlin Gradle Plugin (KGP) gaya lama: `android_alarm_manager_plus`, `device_info_plus`, `file_picker`, `package_info_plus`, `share_plus`, `workmanager_android`.
- **Dampak Saat Ini:** Tidak ada kegagalan build (build sukses 100%). Namun versi Flutter masa depan mungkin menuntut Built-in Kotlin.
- **Tindakan Lanjutan:** Saat tim memperbarui versi SDK Flutter di masa depan, lakukan *upgrade* bertahap pada paket-paket tersebut mengikuti panduan resmi migrasi Flutter Built-in Kotlin.

### 2. Konfigurasi Keystore Rilis Resmi (Production Keystore)
- **Kategori:** Prosedur Deployment
- **Deskripsi:** File `key.properties` dan keystore rilis sengaja tidak disimpan di dalam repositori Git lokal (praktik terbaik keamanan). Build `release` pada file `android/app/build.gradle.kts` saat ini diarahkan ke *debug signing* untuk kebutuhan pengujian.
- **Tindakan Lanjutan:** Sebelum mempublikasikan APK/AAB resmi ke Google Play Store, siapkan `upload-keystore.jks` resmi instansi dan isi konfigurasi `android/key.properties`.

### 3. Pemantauan Token FCM Expired
- **Kategori:** Integrasi Pihak Ketiga
- **Deskripsi:** Fitur pengiriman FCM telah dilengkapi mekanisme pembersihan otomatis jika token berstatus `UNREGISTERED` (404/410).
- **Tindakan Lanjutan:** Pastikan kuota Firebase Cloud Messaging dan akun Google Service Account tetap aktif dan terpelihara pada dashboard Firebase Console.
