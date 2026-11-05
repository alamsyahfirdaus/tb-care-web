# LAPORAN INTEGRASI REST API (API INTEGRATION REPORT)
**TB Care Mobile & Laravel Backend Communication Specification**
**Tanggal Audit:** 06 November 2026

---

## 1. IKHTISAR ARSITEKTUR KOMUNIKASI

Komunikasi antara aplikasi mobile Flutter (`tb-care-mobile`) dan backend Laravel (`tb-care-web`) menggunakan protokol RESTful JSON API melalui HTTPS dengan autentikasi berbasis bearer token Laravel Sanctum.

- **Base URL Produksi:** `https://tbcare.umtas.ac.id/api`
- **Base URL Development / Pengujian:** `http://10.0.2.2:8000/api` (AVD) / `http://127.0.0.1:8000/api` (Device via ADB reverse)
- **Header Standar:**
  ```http
  Accept: application/json
  Authorization: Bearer <sanctum_token>
  Content-Type: application/json (atau multipart/form-data untuk file/foto)
  ```
- **Proteksi Akses Global:** Middleware `auth:sanctum` digabungkan dengan middleware `active` yang secara ketat memverifikasi bahwa akun pengguna bersangkutan tidak berstatus nonaktif (`is_active = true`).

---

## 2. DAFTAR LENGKAP ENDPOINT REST API AKTUAL & HASIL UJI

### A. Autentikasi & Akun Pengguna
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/login` | POST | Publik | `username` / `phone`, `password`, `fcm_token` | `{token, user, role}` | **PASS (200)** |
| `/api/register` | POST | Publik | `name`, `nik`, `phone`, `puskesmas_id`, `subdistrict_id`, `village_id`, `treatment_start_date`, `rt`, `rw` | `{message, token, user}` | **PASS (201)** |
| `/api/register/roles` | POST | Publik | - | `{data: roles}` | **PASS (200)** |
| `/api/me` | GET | Sanctum + Active | - | `{user, role, details}` | **PASS (200)** |
| `/api/logout` | POST | Sanctum + Active | - | `{message: "Logout berhasil"}` | **PASS (200)** |

### B. Profil & FCM Token
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/profile/show` | GET | Sanctum + Active | - | `{success, data: user_profile}` | **PASS (200)** |
| `/api/profile/update` | PUT | Sanctum + Active | `name`, `phone`, `gender`, `date_of_birth`, dll | `{message, user}` | **PASS (200)** |
| `/api/profile/fcm-token` | POST | Sanctum + Active | `fcm_token` | `{message: "FCM token updated"}` | **PASS (200)** |

### C. Master Data Wilayah & Fasilitas Kesehatan (Publik)
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/puskesmas` | GET | Publik | - | `{data: [puskesmas]}` (51 faskes) | **PASS (200)** |
| `/api/provinces` | GET | Publik | - | `{data: [provinces]}` | **PASS (200)** |
| `/api/provinces/{id}/districts`| GET | Publik | `id` (province_id) | `{data: [districts]}` | **PASS (200)** |
| `/api/districts` | GET | Publik | - | `{data: [districts]}` | **PASS (200)** |
| `/api/districts/{id}/subdistricts`| GET | Publik | `id` (district_id) | `{data: [subdistricts]}` | **PASS (200)** |
| `/api/subdistricts` | GET | Publik | - | `{data: [subdistricts]}` | **PASS (200)** |
| `/api/subdistricts/{id}/villages` | GET | Publik | `id` (subdistrict_id) | `{data: [villages]}` | **PASS (200)** |
| `/api/villages/search` | GET | Publik | `keyword` / `q` | `{data: [matched_villages]}` | **PASS (200)** |

### D. Skrining TB Mandiri
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/screening/categories` | GET | Publik | - | `{message, data: categories}` | **PASS (200)** |
| `/api/screening/questions` | POST | Publik | `category_id` | `{message, data: questions}` | **PASS (200)** |
| `/api/screening/submit` | POST | Publik | `answers: [...]`, `person_name`, dll | `{message, risk_level, score}` | **PASS (200)** |
| `/api/screening/{id}` | GET | Publik / Auth | `id` | `{data: screening_detail}` | **PASS (200)** |

### E. Manajemen Pasien & Pemantauan Pengobatan
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/patient/home` | GET | Sanctum + Active | - | `{data: patient_summary, treatment}` | **PASS (200)** |
| `/api/patients` | GET/POST | Sanctum + Active | `search`, `page`, `status` | `{data: [patients], meta}` | **PASS (200)** |
| `/api/patients/store` | POST/PUT | Sanctum (PJTB/Admin) | Data pasien & NIK 16 digit | `{message, data}` (Kader -> 403) | **PASS (201/403)** |
| `/api/patients/{id}/show` | GET | Sanctum + Active | `id` | `{data: patient_full_detail}` | **PASS (200)** |
| `/api/patients/{id}/medication-schedule` | GET | Sanctum + Active | `id` | `{success, data: schedule}` | **PASS (200)** |
| `/api/patients/{id}/medication-schedule` | POST | Sanctum (Pasien/PJTB)| `reminder_time` (HH:mm) | `{success, data}` (Kader -> 403) | **PASS (200/403)** |
| `/api/patients/{id}/treatments` | GET | Sanctum + Active | `id` | `{data: [treatments]}` | **PASS (200)** |
| `/api/patients/adherence` | GET | Sanctum + Active | `patient_id` (opsional) | `{total_expected, taken, percentage}`| **PASS (200)** |

### F. Konfirmasi Minum Obat & Riwayat Harian
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/treatments/proof` | POST | Sanctum (Pasien) | `patient_treatment_id`, `photo` (file), `note` | `{message: "Bukti berhasil diunggah"}` | **PASS (200)** |
| `/api/treatments/{id}/history` | GET | Sanctum + Active | `id` | `{data: [medication_records]}` | **PASS (200)** |
| `/api/treatments/verify` | PUT | Sanctum (Petugas) | `medication_record_id`, `status` | `{message: "Verifikasi berhasil"}` | **PASS (200)** |
| `/api/treatments/status` | POST | Sanctum (PJTB/Admin) | `id`, `treatment_status` | `{message, data}` (Kader -> 403) | **PASS (200/403)** |

### G. Live Chat Privat (Konsultasi)
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/conversations` | GET | Sanctum + Active | - | `{data: [active_conversations]}` | **PASS (200)** |
| `/api/conversations/recipients`| GET | Sanctum + Active | - | `{data: [eligible_contacts]}` | **PASS (200)** |
| `/api/conversations/{id}/messages`| GET | Sanctum + Active | `since_id` (opsional polling) | `{data: [messages]}` | **PASS (200)** |
| `/api/conversations/{id}/messages`| POST | Sanctum + Active | `message`, `attachment` (file opsional) | `{data: message_object}` | **PASS (201)** |
| `/api/conversations/{id}/read` | POST | Sanctum + Active | `id` | `{message: "Telah dibaca"}` | **PASS (200)** |
| `/api/conversations/bulk-hide` | POST | Sanctum + Active | `conversation_ids: [...]` | `{message: "Berhasil disembunyikan"}` | **PASS (200)** |
| `/api/messages/{id}` | PUT | Sanctum (Pengirim) | `message` (edit konten) | `{message, data}` (Bukan pengirim -> 403) | **PASS (200/403)** |
| `/api/messages/{id}` | DELETE | Sanctum (Pengirim) | `id` (soft delete) | `{message: "Pesan dihapus"}` | **PASS (200)** |

### H. Pengingat Minum Obat Massal (Bulk Reminder)
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/reminders/recipients` | GET | Sanctum (Petugas/Kader) | - | `{data: [patients_with_territory]}` | **PASS (200)** |
| `/api/reminders/bulk-send` | POST | Sanctum (Petugas/Kader) | `patient_ids: [...]`, `message` | `{success_count, failed_count}` | **PASS (200)** |

### I. Media Edukasi TB
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/education` | GET | Sanctum + Active | `type` (image/video), `limit` | `{data: [materials]}` | **PASS (200)** |
| `/api/education/{id}/show` | GET | Sanctum + Active | `id` | `{data: material_detail}` | **PASS (200)** |
| `/api/education/store` | POST/PUT | Sanctum (Petugas/Admin)| `title`, `description`, `type`, `file` | `{message, data}` | **PASS (201)** |

### J. Penyajian File Gambar Publik & Pemeriksaan Versi
| Endpoint | Method | Autentikasi | Parameter Utama | Response Format | Status Uji |
| :--- | :---: | :---: | :--- | :--- | :---: |
| `/api/image/{filename}` | GET | Publik / Auth | `filename` | Raw image stream (`Content-Type: image/*`)| **PASS (200)** |
| `/api/mobile/app-version` | GET | Publik | `platform=android` | `{latest_version, update_available, ...}` | **PASS (200)** |

---

## 3. VERIFIKASI KEAMANAN API & INTEGRITAS INTEGRASI

1. **Proteksi IDOR (Insecure Direct Object References):** Seluruh controller memvalidasi relasi data (`isAccessibleBy`). Pasien A tidak dapat melihat pengobatan, riwayat obat, jadwal, atau chat Pasien B. Percobaan akses silang mengembalikan `403 Forbidden`.
2. **Penanganan Token Kedaluwarsa:** Client Flutter secara otomatis menangani status `401 Unauthorized` melalui interceptor dan mengarahkan pengguna kembali ke halaman Login dengan membersihkan session lokal secara bersih.
3. **Penyajian Gambar:** URL gambar yang dihasilkan oleh backend secara cerdas dinormalisasi oleh `Connection.resolveImageUrl()` pada Flutter sehingga diarahkan ke endpoint `/api/image/{filename}` yang terbebas dari restriksi CORS dan Cloudflare challenge.
