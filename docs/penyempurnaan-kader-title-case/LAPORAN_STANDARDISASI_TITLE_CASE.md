# LAPORAN STANDARDISASI GLOBAL TITLE CASE NAMA PENGGUNA
**Aplikasi TB Care — Ekosistem Mobile (Flutter) & Web Backend (Laravel)**
*Dokumentasi Teknis Format Nama Lengkap, Penanganan Tanda Hubung & Apostrof*

---

## 1. RINGKASAN EKSEKUTIF

Sebelumnya, nama-nama pengguna dan kontak erat di TB Care tersimpan dengan variasi kapitalisasi yang tidak konsisten (contoh: `anes Maulida sukmara`, `DEDE SUMIATI`, `ardil fahmi`). Hal ini terlihat jelas saat memilih pasien pada halaman **Konsultasi Baru (Pilih Pasien)** maupun detail informasi pasien. 

Melalui pembaruan ini, seluruh nama orang di seluruh ekosistem TB Care distandardisasi menjadi **Title Case** yang rapi, profesional, dan akurat secara linguistik, baik saat disimpan di database, saat disajikan lewat API backend, maupun saat dirender pada frontend Flutter.

---

## 2. PRINSIP DAN BATASAN REKAYASA (GUARDRAILS)

1. **Hanya Berlaku untuk Nama Orang:**
   - Standardisasi Title Case **HANYA** berlaku untuk field nama orang (`users.name`, `close_contacts.name`, `patients.name`).
2. **Perlindungan Ketat Identitas Non-Nama (Non-Name Fields):**
   - Username (misal: `alamsyahfirdaus1`, `kader001`) **TETAP LOWERCASE / ASLI**.
   - Email (misal: `kader@puskesmas.id`) **TETAP LOWERCASE / ASLI**.
   - Kata Sandi / Hash Password **TIDAK TERSENTUH**.
   - NIK (16 digit angka) **TIDAK DIUBAH**.
   - Nomor Telepon / HP **TIDAK DIUBAH**.
3. **Penanganan Kasus Nama Majemuk & Tanda Baca Khusus:**
   - **Tanda Hubung (Hyphens):** Nama seperti `Dewi-Ayu` harus mengkapitalkan kedua kata sebelum dan sesudah tanda hubung.
   - **Apostrof Nama Indonesia / Arab:** Nama seperti `Sa'diyah`, `Ma'ruf`, `Syafi'i` menggunakan apostrof sebagai tanda 'ain atau hamzah di tengah kata, sehingga huruf setelah apostrof tetap huruf kecil (`Sa'diyah`, bukan `Sa'Diyah`).
   - **Apostrof Nama Barat / Patronymic:** Nama dengan prefiks satu huruf seperti `D'Angelo` atau `O'Connor` mengkapitalkan huruf setelah apostrof.

---

## 3. IMPLEMENTASI BACKEND (LARAVEL)

### 3.1 Helper `NameFormatter`
File: `tb-care-web/app/Helpers/NameFormatter.php`

```php
namespace App\Helpers;

class NameFormatter
{
    public static function format(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $trimmed = trim(preg_replace('/\s+/', ' ', $name));
        if ($trimmed === '') {
            return '';
        }

        $words = explode(' ', $trimmed);
        $formattedWords = [];

        foreach ($words as $word) {
            // Tangani kata yang memiliki tanda hubung (-)
            if (strpos($word, '-') !== false) {
                $subParts = explode('-', $word);
                $formattedSubParts = array_map(function ($part) {
                    return self::formatSingleWord($part);
                }, $subParts);
                $formattedWords[] = implode('-', $formattedSubParts);
            } else {
                $formattedWords[] = self::formatSingleWord($word);
            }
        }

        return implode(' ', $formattedWords);
    }

    private static function formatSingleWord(string $word): string
    {
        if ($word === '') {
            return '';
        }

        // Tangani apostrof (baik ' maupun ’)
        if (strpos($word, "'") !== false || strpos($word, "’") !== false) {
            $delim = strpos($word, "'") !== false ? "'" : "’";
            $parts = explode($delim, $word);
            $prefix = mb_convert_case($parts[0], MB_CASE_TITLE, 'UTF-8');
            
            // Jika prefiks sebelum apostrof > 1 karakter (seperti Sa'diyah, Ma'ruf), bagian setelahnya lowercase
            // Jika prefiks = 1 karakter (seperti D'Angelo, O'Connor), bagian setelahnya Title Case
            if (mb_strlen($parts[0]) > 1) {
                $suffix = mb_strtolower($parts[1], 'UTF-8');
            } else {
                $suffix = mb_convert_case($parts[1], MB_CASE_TITLE, 'UTF-8');
            }
            return $prefix . $delim . $suffix;
        }

        return mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
    }
}
```

### 3.2 Eloquent Mutators & Accessors
Diterapkan pada model `App\Models\User` dan `App\Models\CloseContact`:
```php
use App\Helpers\NameFormatter;

protected function name(): Attribute
{
    return Attribute::make(
        get: fn (?string $value) => NameFormatter::format($value),
        set: fn (?string $value) => NameFormatter::format($value),
    );
}
```
Dengan konfigurasi ini:
- Setiap data nama yang diinput atau diupdate secara otomatis distandardisasi sebelum disimpan ke MySQL.
- Setiap pengambilan data nama melalui Eloquent dijamin mengembalikan format Title Case yang seragam.

---

## 4. IMPLEMENTASI FRONTEND (FLUTTER)

### 4.1 Service `NameFormatter`
File: `tb-care-mobile/lib/services/name_formatter.dart`

```dart
class NameFormatter {
  static String formatPersonName(String? name) {
    if (name == null) return '';
    final trimmed = name.trim().replaceAll(RegExp(r'\s+'), ' ');
    if (trimmed.isEmpty) return '';

    return trimmed.split(' ').map((word) {
      if (word.contains('-')) {
        return word.split('-').map(_formatSingleWord).join('-');
      }
      return _formatSingleWord(word);
    }).join(' ');
  }

  static String _formatSingleWord(String word) {
    if (word.isEmpty) return '';
    final delim = word.contains("'") ? "'" : (word.contains('’') ? '’' : null);
    if (delim != null) {
      final parts = word.split(delim);
      if (parts.length >= 2) {
        final prefix = _capitalize(parts[0]);
        // Sa'diyah vs D'Angelo
        final suffix = parts[0].length > 1
            ? parts.sublist(1).join(delim).toLowerCase()
            : parts.sublist(1).map(_capitalize).join(delim);
        return '$prefix$delim$suffix';
      }
    }
    return _capitalize(word);
  }

  static String _capitalize(String s) {
    if (s.isEmpty) return '';
    return s[0].toUpperCase() + s.substring(1).toLowerCase();
  }
}
```

### 4.2 Integrasi Defense-in-Depth pada Tampilan
- **Konsultasi Baru / Pilih Pasien (`lib/main/chat/select_recipient_page.dart`):**
  Nama pasien diformat secara instan saat membangun widget `ListTile`:
  `NameFormatter.formatPersonName(recipient.name)`
- **Model Deserialization (`lib/models/conversation_model.dart`):**
  Constructor `RecipientItem.fromJson` dan `OtherUser.fromJson` otomatis memanggil `NameFormatter.formatPersonName(json['name'])`.
- **Halaman Profil (`lib/profile.dart`):**
  Nama pengguna yang tampil di header dan baris informasi pribadi diformat dengan `NameFormatter.formatPersonName`.

---

## 5. HASIL PENGUJIAN OTOMATIS

### 5.1 Flutter Unit Tests (`test/name_formatter_test.dart`)
- `✓ Standardizes lowercase names to Title Case` (e.g. `anes maulida sukmara` -> `Anes Maulida Sukmara`)
- `✓ Standardizes uppercase names and irregular whitespace` (e.g. `DEDE   SUMIATI` -> `Dede Sumiati`)
- `✓ Handles mixed case names cleanly` (e.g. `aLaMsYaH fIrDaUs` -> `Alamsyah Firdaus`)
- `✓ Handles hyphenated names accurately` (e.g. `dewi-ayu lestari` -> `Dewi-Ayu Lestari`)
- `✓ Handles apostrophes accurately for both Indonesian and Western names` (`sa'diyah` -> `Sa'diyah`, `d'angelo` -> `D'Angelo`)
- `✓ Handles null, empty, and edge cases safely`

### 5.2 Laravel Feature Tests (`tests/Feature/KaderVerificationAndProfileTerritoryTest.php`)
- `✓ name standardization to title case` (Verifikasi mutator, accessor, dan helper)
