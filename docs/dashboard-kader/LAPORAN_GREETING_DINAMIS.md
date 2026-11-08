# LAPORAN IMPLEMENTASI GREETING DINAMIS BERDASARKAN WAKTU
## APLIKASI TB CARE (ROLE KADER, PETUGAS, DAN PASIEN)

---

## 1. PENDAHULUAN & LATAR BELAKANG

Sebelumnya, header Dashboard Role Kader menampilkan sapaan statis:
```text
Selamat Datang,
Kader Puskesmas
```

Untuk meningkatkan pengalaman pengguna (*user experience*), personalisasi, dan konsistensi antar peran (*roles*), sapaan diubah menjadi **greeting dinamis** yang secara otomatis mendeteksi waktu lokal perangkat dan menyapa pengguna secara ramah dan profesional, diikuti dengan nama pengguna yang diformat menggunakan standar Title Case.

Sapaan dinamis ini kini distandarisasi di seluruh dashboard aplikasi TB Care:
1. **Dashboard Kader** (`lib/main/kader/home.dart`)
2. **Dashboard Petugas / PJTB** (`lib/main/petugas/home.dart`)
3. **Dashboard Pasien** (`lib/main/pasien/home.dart`)

---

## 2. SPESIFIKASI DAN ATURAN WAKTU GREETING

Pembagian waktu mengacu pada standar waktu Indonesia (WIB) dengan batasan eksak per jam dan menit:

| Rentang Waktu (Jam:Menit) | Format Sapaan | Contoh Tampilan Lengkap |
|---|---|---|
| **00.00 – 10.59** | `Selamat Pagi,` | Selamat Pagi,<br>**Dede Tetin** |
| **11.00 – 14.59** | `Selamat Siang,` | Selamat Siang,<br>**Dede Tetin** |
| **15.00 – 17.59** | `Selamat Sore,` | Selamat Sore,<br>**Dede Tetin** |
| **18.00 – 23.59** | `Selamat Malam,` | Selamat Malam,<br>**Dede Tetin** |

Jika `withComma: false` dilewatkan ke helper, tanda koma di akhir kata dihilangkan.

---

## 3. IMPLEMENTASI TEKNIS FLUTTER

### A. Shared Service: `GreetingHelper`
Lokasi file: [`tb-care-mobile/lib/services/greeting_helper.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/services/greeting_helper.dart)

```dart
class GreetingHelper {
  /// Mengembalikan sapaan waktu sesuai aturan:
  /// - 00.00–10.59 : Selamat Pagi
  /// - 11.00–14.59 : Selamat Siang
  /// - 15.00–17.59 : Selamat Sore
  /// - 18.00–23.59 : Selamat Malam
  static String getGreeting({DateTime? time, bool withComma = true}) {
    final now = time ?? DateTime.now();
    final hour = now.hour;
    final minute = now.minute;
    final timeInMinutes = hour * 60 + minute;

    String greeting;
    if (timeInMinutes < 11 * 60) {
      greeting = 'Selamat Pagi';
    } else if (timeInMinutes < 15 * 60) {
      greeting = 'Selamat Siang';
    } else if (timeInMinutes < 18 * 60) {
      greeting = 'Selamat Sore';
    } else {
      greeting = 'Selamat Malam';
    }

    return withComma ? '$greeting,' : greeting;
  }
}
```

### B. Penerapan pada Dashboard Kader
Lokasi file: [`tb-care-mobile/lib/main/kader/home.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/kader/home.dart)

```dart
Widget _buildWelcomeCard() {
  final greeting = GreetingHelper.getGreeting();
  final displayName = _userName.trim().isNotEmpty
      ? NameFormatter.formatPersonName(_userName)
      : 'Kader Puskesmas';

  return Container(
    padding: const EdgeInsets.all(18),
    decoration: BoxDecoration(
      color: primaryColor, // Solid Primary Blue tanpa gradient
      borderRadius: BorderRadius.circular(16),
      boxShadow: [
        BoxShadow(
          color: primaryColor.withValues(alpha: 0.18),
          blurRadius: 10,
          offset: const Offset(0, 4),
        ),
      ],
    ),
    child: Row(
      children: [
        // Avatar circle solid dengan initial
        CircleAvatar(
          radius: 26,
          backgroundColor: Colors.white.withValues(alpha: 0.22),
          child: Text(
            displayName.isNotEmpty ? displayName[0].toUpperCase() : 'K',
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                greeting,
                style: const TextStyle(
                  color: Colors.white70,
                  fontSize: 13,
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                displayName,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 17,
                  fontWeight: FontWeight.bold,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ],
    ),
  );
}
```

### C. Konsistensi pada Dashboard Petugas & Pasien
- **Dashboard Petugas** ([`lib/main/petugas/home.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/petugas/home.dart)): Menggunakan `GreetingHelper.getGreeting()` dan `NameFormatter.formatPersonName(officerName)` di dalam header card.
- **Dashboard Pasien** ([`lib/main/pasien/home.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/pasien/home.dart)): Dimutakhirkan untuk menggunakan `GreetingHelper.getGreeting()` terpusat, menggantikan hardcoded logic sebelumnya sehingga seluruh role memiliki sinkronisasi jam sapaan yang identik.

---

## 4. VERIFIKASI DAN PENGUJIAN

Pengujian otomatis dilakukan melalui file [`tb-care-mobile/test/greeting_helper_test.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/test/greeting_helper_test.dart):

```bash
flutter test test/greeting_helper_test.dart
```

Hasil Pengujian:
- ✓ `GreetingHelper Unit Tests Pagi: 00:00 to 10:59` (PASS)
- ✓ `GreetingHelper Unit Tests Siang: 11:00 to 14:59` (PASS)
- ✓ `GreetingHelper Unit Tests Sore: 15:00 to 17:59` (PASS)
- ✓ `GreetingHelper Unit Tests Malam: 18:00 to 23:59` (PASS)

Semua pengujian batas waktu (misal: 10:59 vs 11:00, 14:59 vs 15:00, 17:59 vs 18:00, 23:59 vs 00:00) dinyatakan **LULUS 100%**.
