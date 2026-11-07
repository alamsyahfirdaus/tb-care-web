# MATRIKS PEMETAAN AKTUAL FITUR ROLE KADER
**Ekosistem:** TB Care Mobile (Flutter) & TB Care Backend (Laravel 11)  
**Terakhir Diperbarui:** 7 November 2026  
**Status Pengujian:** 100% Terverifikasi (218 Tests Backend & 207 Tests Mobile Lulus)

---

## 1. TABEL MATRIKS INTEGRASI MOBILE & BACKEND

Berikut adalah matriks pemetaan aktual antara halaman/komponen Flutter Mobile dengan endpoint API dan controller Laravel Backend yang digunakan oleh **Role Kader**:

| No | Modul / Fitur | Flutter Page / Widget | HTTP Method | API Endpoint | Laravel Controller & Action | Format Request | Format Response | Status Akhir |
|:--:|:---|:---|:---:|:---|:---|:---|:---|:---:|
| **1** | **Ringkasan Beranda Kader** | `KaderHomePage` (`lib/main/kader/home.dart`) | `GET` | `/api/kader/summary` | `Api\KaderDashboardController@summary` | Header: `Bearer <token>` | JSON: `status: true, data: { total_patients, adherents, non_adherents, need_reminder }` | **BERFUNGSI** |
| **2** | **Aktivitas Terbaru Pasien Binaan** | `KaderHomePage` (`lib/main/kader/home.dart`) | `GET` | `/api/kader/activities` | `Api\KaderDashboardController@activities` | Header: `Bearer <token>` | JSON: `status: true, data: [ { id, patient_name, type, time, description } ]` | **BERFUNGSI** |
| **3** | **Daftar Pasien Binaan (Scoped Wilayah)** | `KaderPatientPage` (`lib/main/kader/patient.dart`) | `GET` | `/api/kader/patients` | `Api\KaderPatientController@index` | Header: `Bearer <token>`, Query: `search, status, village_id, sort` | JSON: `status: true, data: [ { id, name, nik, village_id, village_name, adherence_status, last_intake } ]` | **BERFUNGSI** |
| **4** | **Detail Pasien Binaan** | `KaderPatientDetailPage` (`lib/main/kader/patient_detail.dart`) | `GET` | `/api/kader/patients/{id}` | `Api\KaderPatientController@show` | Header: `Bearer <token>`, URL Param: `id` | JSON: `status: true, data: { patient_profile, emergency_contacts, pmo_info, treatment_phase }` | **BERFUNGSI** |
| **5** | **Riwayat Minum Obat Pasien** | Tab Riwayat pada `KaderPatientDetailPage` | `GET` | `/api/kader/patients/{id}/medications` | `Api\KaderPatientController@medicationHistory` | Header: `Bearer <token>`, Query: `month, year` | JSON: `status: true, data: [ { date, status, proof_photo_url, confirmed_at, note } ]` | **BERFUNGSI** |
| **6** | **Kirim Pengingat Obat Tunggal** | `KaderReminderService` (Modal Bottom Sheet) | `POST` | `/api/kader/reminders/send` | `Api\PatientReminderController@send` | Header: `Bearer <token>`, Body JSON: `{ patient_id, reminder_type, message }` | JSON: `status: true, message: "Pengingat berhasil dikirim", data: { reminder_id, sent_at }` | **BERFUNGSI** |
| **7** | **Kirim Pengingat Obat Massal (Bulk)** | `BulkReminderPage` (`lib/main/kader/bulk_reminder.dart`) | `POST` | `/api/kader/reminders/bulk` | `Api\PatientReminderController@bulkSend` | Header: `Bearer <token>`, Body JSON: `{ patient_ids: [1, 2, 3], message }` | JSON: `status: true, message: "X pengingat berhasil dikirim", total_sent: X` | **BERFUNGSI** |
| **8** | **Riwayat Pengingat Terkirim** | `KaderPatientDetailPage` | `GET` | `/api/kader/patients/{id}/reminders` | `Api\PatientReminderController@history` | Header: `Bearer <token>`, URL Param: `id` | JSON: `status: true, data: [ { id, message, sent_at, channel, status } ]` | **BERFUNGSI** |
| **9** | **Pusat Notifikasi (List & Filter)** | `NotificationListPage` (`lib/main/notifications/notification_list_page.dart`) | `GET` | `/api/notifications` | `Api\NotificationController@index` | Header: `Bearer <token>`, Query: `type, unread_only, search, limit` | JSON: `success: true, data: [ { id, type, title, message, is_read, read_at, payload } ], unread_count: X` | **BERFUNGSI** |
| **10** | **Badge Hitung Notifikasi Belum Dibaca** | `TBAppBar` & `NotificationCenterService` | `GET` | `/api/notifications/unread-count` | `Api\NotificationController@unreadCount` | Header: `Bearer <token>` | JSON: `success: true, unread_count: X` | **BERFUNGSI** |
| **11** | **Tandai Notifikasi Dibaca (Tunggal)** | `NotificationListPage` (On Tap) | `POST` | `/api/notifications/{id}/read` | `Api\NotificationController@markAsRead` | Header: `Bearer <token>`, URL Param: `id` | JSON: `success: true, message: "Notifikasi ditandai sebagai sudah dibaca."` | **BERFUNGSI** |
| **12** | **Tandai Semua Notifikasi Dibaca** | `NotificationListPage` (Aksi Header) | `POST` | `/api/notifications/mark-all-read` | `Api\NotificationController@markAllAsRead` | Header: `Bearer <token>` | JSON: `success: true, message: "Semua notifikasi berhasil ditandai sebagai sudah dibaca."` | **BERFUNGSI** |
| **13** | **Hapus Notifikasi** | `NotificationListPage` (Swipe Dismiss) | `DELETE` | `/api/notifications/{id}` | `Api\NotificationController@destroy` | Header: `Bearer <token>`, URL Param: `id` | JSON: `success: true, message: "Notifikasi berhasil dihapus."` | **BERFUNGSI** |
| **14** | **Daftar Materi Edukasi (Published Only)** | `EducationPage` (`lib/main/pasien/education.dart`) | `GET` | `/api/education` | `Api\EducationController@index` | Header: `Bearer <token>` | JSON: `data: [ { id, title_material, material_type, is_publish, is_read, published_at, photo } ]` | **BERFUNGSI** |
| **15** | **Detail Materi Edukasi & Auto-Read** | `MateriDetailPage` (`lib/main/pasien/materi_detail.dart`) | `GET` | `/api/education/{id}` | `Api\EducationController@show` | Header: `Bearer <token>`, URL Param: `id` | JSON: `data: { id, title_material, description, photo, video_url, is_read: true, author_name }` | **BERFUNGSI** |
| **16** | **Daftar Percakapan Konsultasi (Chat)** | `ConversationListPage` (`lib/main/chat/conversation_list_page.dart`) | `GET` | `/api/conversations` | `Api\ConversationController@index` | Header: `Bearer <token>` | JSON: `status: true, data: [ { id, recipient_name, last_message, unread_messages, updated_at } ]` | **BERFUNGSI** |
| **17** | **Kirim & Terima Pesan Konsultasi** | `LiveChatPage` (`lib/main/chat/live_chat_page.dart`) | `POST` | `/api/conversations/{id}/messages` | `Api\ConversationController@sendMessage` | Header: `Bearer <token>`, Body JSON: `{ message, attachment }` | JSON: `status: true, data: { id, message, sender_id, created_at }` | **BERFUNGSI** |
| **18** | **Profil Kader & Wilayah Binaan** | `ProfilePage` (`lib/main/profil/profile.dart`) | `GET` | `/api/profile` | `Api\ProfileController@show` | Header: `Bearer <token>` | JSON: `status: true, data: { id, name, officer_type_id: 4, puskesmas_name, village_assignments }` | **BERFUNGSI** |
| **19** | **Pembaruan Token FCM (Push Notif)** | `NotificationService` (`lib/services/notification_service.dart`) | `POST` | `/api/profile/fcm-token` | `Api\ProfileController@updateFcmToken` | Header: `Bearer <token>`, Body JSON: `{ fcm_token }` | JSON: `status: true, message: "FCM token berhasil diperbarui"` | **BERFUNGSI** |
| **20** | **Akses Gambar Bukti Minum Obat & Media** | Image Widget (Mobile) via `Connection.resolveImageUrl` | `GET` | `/api/image/{filename}` | `Api\ImageController@show` | Header: `Bearer <token>` (atau direct secure proxy stream) | Binary Stream: `image/jpeg` atau `image/png` (Bypass Cloudflare 403) | **BERFUNGSI** |

---

## 2. KOREKSI DAN PENGUATAN RBAC ENDPOINT

Berikut adalah audit perlindungan otorisasi terhadap endpoint yang dilarang bagi Role Kader:

| No | Endpoint Terlarang Bagi Kader | HTTP Method | Expected Behavior | Actual Response | Status Proteksi |
|:--:|:---|:---:|:---|:---:|:---:|
| 1 | `/api/education/create` | `POST` | Ditolak (Hanya Admin / Petugas) | `403 Forbidden` | **AMAN & TERVALIDASI** |
| 2 | `/api/education/{id}/edit` | `PUT/POST` | Ditolak (Hanya Admin / Petugas) | `403 Forbidden` | **AMAN & TERVALIDASI** |
| 3 | `/api/education/{id}/delete` | `DELETE` | Ditolak (Hanya Admin / Petugas) | `403 Forbidden` | **AMAN & TERVALIDASI** |
| 4 | `/api/education/{draft_id}` | `GET` | Ditolak jika materi berstatus draf | `403 Forbidden` | **AMAN & TERVALIDASI** |
| 5 | `/admin/*` (Web Admin) | `ANY` | Ditolak oleh `EnsureWebAdminRole` | `403 Forbidden` / Redirect | **AMAN & TERVALIDASI** |
| 6 | `/api/kader/patients/{outside_id}` | `GET` | Ditolak jika pasien di luar wilayah binaan | `404 / 403 Scoped Query` | **AMAN & TERVALIDASI** |

---

## 3. KONSISTENSI DATA & PENANGANAN SESI

1. **Format Tanggal dan Jam:** Seluruh respons backend dinormalisasi menggunakan Timezone `Asia/Jakarta` (WIB) dengan format ISO-8601 (`toIso8601String()`).
2. **Sesi Kedaluwarsa (HTTP 401):** Ditangani otomatis oleh `SessionManager.checkResponse()` di mobile untuk membersihkan SharedPreferences dan mengarahkan pengguna kembali ke halaman Login secara mulus tanpa crash.
3. **Akun Dinonaktifkan (HTTP 403):** Ditangani secara khusus oleh `SessionManager` untuk memunculkan dialog resmi penonaktifan akun oleh administrator dan membatalkan jadwal alarm lokal.
