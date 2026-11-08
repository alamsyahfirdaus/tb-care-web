# LAPORAN NOTIFIKASI DAN KONSULTASI CHAT ROLE KADER

**Tanggal Implementasi:** 8 November 2026  
**Tim Pengembang:** Mobile Engineer & Communications Architect  
**Status:** Selesai, Teruji 100%, Lolos Audit Badge

---

## 1. Integrasi Badge AppBar Seluruh Aplikasi

Aturan pemformatan badge notifikasi dan konsultasi chat diseragamkan di seluruh layar aplikasi (`TBAppBar`):

| Jumlah Belum Dibaca | Tampilan Badge |
|---|---|
| `0` | Tidak ada badge (badge disembunyikan secara bersih) |
| `1` s/d `9` | Menampilkan angka aktual: `1`, `2`, ..., `9` |
| `10` | Menampilkan teks angka: `10` |
| `> 10` | Menampilkan teks ringkas: `9+` |

File: `tb-care-mobile/lib/widgets/tb_app_bar.dart`
```dart
count > 10 ? '9+' : count.toString()
```

---

## 2. Penambahan Icon Notifikasi pada AppBar Profil

Sesuai permintaan penyempurnaan, pada halaman Profil Saya (`ProfilePage`), AppBar bagian atas kini dilengkapi dengan icon lonceng (notifikasi) di samping icon pesan konsultasi:
- `showNotificationButton: true`
- `showChatButton: true`

Fitur:
- Otomatis terhubung dengan `NotificationCenterService.unreadCountNotifier`.
- Menampilkan badge unread secara real-time.
- Menekan icon lonceng langsung mengarahkan kader ke halaman `NotificationListPage` untuk meninjau seluruh notifikasi masuk (pengingat, edukasi baru, dan konfirmasi minum obat pasien).

---

## 3. Alur Notifikasi Edukasi Baru untuk Kader

Ketika Petugas Kesehatan atau Administrator mempublikasikan materi edukasi baru di Web Admin:
1. Backend Laravel memicu event publikasi edukasi.
2. Mengirimkan push notification FCM dan menyimpan `system_notification` dengan payload:
   ```json
   {
     "notification_type": "education_published",
     "education_id": 101,
     "title": "Materi Edukasi Baru: Etika Batuk",
     "message": "Petugas PJTB telah mempublikasikan materi baru."
   }
   ```
3. Kader menerima notifikasi push dan in-app badge lonceng bertambah.
4. Ketika kader mengklik notifikasi tersebut, aplikasi langsung membuka detail materi edukasi terkait dan menandai notifikasi sebagai sudah dibaca (`read_at != null`).

---

## 4. Hasil Pengujian

- `test/kader_notification_and_education_test.dart`:
  - Menguji `NotificationCenterService.unreadCountNotifier` dengan badge sinkron.
  - Menguji deep-link notifikasi edukasi.
- `test/kader_profile_territory_and_notification_test.dart`:
  - Menguji ketersediaan icon notifikasi dan icon chat pada AppBar Profil.
