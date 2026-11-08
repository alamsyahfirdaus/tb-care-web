# LAPORAN PENGEMBANGAN: TEMPLATE PENGINGAT DINAMIS BERDASARKAN WAKTU
**Sistem Aplikasi TB Care (Pengingat Minum Obat & Jadwal Kontrol Puskesmas)**  
*Dokumen Spesifikasi Format Pesan & Logika Waktu*

---

## 1. Latar Belakang & Masalah
Sebelumnya, template pengingat minum obat dan jadwal kontrol menggunakan pembuka statis:
`"Assalamualaikum. Bapak/Ibu {nama_pasien},"`

Kelemahan pendekatan lama:
1. Kurang fleksibel dan tidak mencerminkan konteks waktu pengiriman pesan yang aktual.
2. Tidak selaras dengan standardisasi sapaan dinamis waktu yang telah diterapkan pada Dashboard aplikasi TB Care (`Selamat Pagi`, `Selamat Siang`, `Selamat Sore`, `Selamat Malam`).
3. Pemilihan beberapa template berupa chip (`[ Opsi 1 ] [ Opsi 2 ] [ Opsi 3 ]`) pada pengingat massal menambah beban kognitif kader saat harus mengirim pengingat dengan cepat.

---

## 2. Aturan Pembagian Waktu Sapaan Dinamis
Menggunakan zona waktu lokal Indonesia (`Asia/Jakarta` / Waktu Indonesia Barat):

| Rentang Waktu | Teks Sapaan | Format Kalimat Pembuka |
| :--- | :--- | :--- |
| **00:00 – 10:59** | `Selamat pagi` | `Selamat pagi, {Nama Pasien}.` |
| **11:00 – 14:59** | `Selamat siang` | `Selamat siang, {Nama Pasien}.` |
| **15:00 – 17:59** | `Selamat sore` | `Selamat sore, {Nama Pasien}.` |
| **18:00 – 23:59** | `Selamat malam` | `Selamat malam, {Nama Pasien}.` |

---

## 3. Format Baku Template Pesan Baru

### A. Pengingat Minum Obat Individu (`KaderReminderService.dart`)
```text
Selamat {sapaan}, {Nama Pasien}.

Pengingat jadwal minum obat TB hari ini pukul {HH:mm} WIB.

Mohon segera minum obat sesuai jadwal dan lakukan konfirmasi minum obat melalui aplikasi TB Care.

Terima kasih telah mengikuti pengobatan secara teratur.
```

### B. Pengingat Jadwal Kontrol Individu (`KaderReminderService.dart`)
```text
Selamat {sapaan}, {Nama Pasien}.

Pengingat jadwal kontrol pengobatan TB dengan rincian sebagai berikut:

Hari/Tanggal: {Hari}, {DD} {Bulan} {YYYY}
Waktu: {HH:mm} WIB

Mohon hadir sesuai jadwal yang telah ditentukan.

Terima kasih.
```

### C. Pengingat Minum Obat Massal / Broadcast (`BulkReminderPage.dart`)
Pada pengingat massal yang dikirimkan ke banyak pasien sekaligus:
```text
Selamat {sapaan}. Jangan lupa untuk meminum obat TB hari ini sesuai anjuran dan jadwal pengobatan Anda. Mohon konfirmasi minum obat melalui aplikasi TB Care. Terima kasih telah tertib menjalani pengobatan.
```
*Tombol **"Reset Template"** disediakan agar kader dapat mengembalikan teks pesan ke template dinamis ini dengan satu kali klik jika sebelumnya sempat mengubah atau mengedit teks pesan.*

---

## 4. Implementasi Kode

### A. Helper Sapaan Waktu (`GreetingHelper.dart`)
File [greeting_helper.dart](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/services/greeting_helper.dart#L32-L65):
```dart
static String getTimeGreeting({DateTime? time}) {
  final now = time ?? DateTime.now();
  final hour = now.hour;

  if (hour < 11) {
    return 'Selamat pagi';
  } else if (hour < 15) {
    return 'Selamat siang';
  } else if (hour < 18) {
    return 'Selamat sore';
  } else {
    return 'Selamat malam';
  }
}

static String getSalutation(String name, {DateTime? time}) {
  final greeting = getTimeGreeting(time: time);
  final cleanName = name.trim();
  if (cleanName.isEmpty) {
    return '$greeting.';
  }
  return '$greeting, $cleanName.';
}
```

### B. Integrasi Bottom Sheet Pengingat (`KaderReminderService.dart`)
File [kader_reminder_service.dart](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/services/kader_reminder_service.dart#L370-L405):
```dart
String _getDefaultTemplate(String type) {
  final salutation = GreetingHelper.getSalutation(widget.patientName);
  if (type == 'visit') {
    if (!widget.hasValidVisit) {
      return 'Jadwal kontrol ke Puskesmas belum tersedia untuk pasien ini.';
    }
    final visitTimeText = widget.formattedVisitTime.contains('WIB')
        ? widget.formattedVisitTime
        : '${widget.formattedVisitTime} WIB';

    return "$salutation\n\n"
        "Pengingat jadwal kontrol pengobatan TB dengan rincian sebagai berikut:\n\n"
        "Hari/Tanggal: ${widget.formattedVisitDate}\n"
        "Waktu: $visitTimeText\n\n"
        "Mohon hadir sesuai jadwal yang telah ditentukan.\n\n"
        "Terima kasih.";
  } else {
    final medTimeText = widget.formattedMedTime.contains('WIB')
        ? widget.formattedMedTime
        : '${widget.formattedMedTime} WIB';

    return "$salutation\n\n"
        "Pengingat jadwal minum obat TB hari ini pukul $medTimeText.\n\n"
        "Mohon segera minum obat sesuai jadwal dan lakukan konfirmasi minum obat melalui aplikasi TB Care.\n\n"
        "Terima kasih telah mengikuti pengobatan secara teratur.";
  }
}
```

---

## 5. Hasil Pengujian Unit & Widget
Semua skenario pengujian batas waktu (boundary testing: 00:00, 10:59, 11:00, 14:59, 15:00, 17:59, 18:00, 23:59) serta rendering otomatis modal pengingat berhasil lolos uji 100%.
