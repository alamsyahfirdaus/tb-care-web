# LAPORAN PERBAIKAN DAN STANDARISASI REMINDER API
## Endpoint: POST /api/kader/reminders/send

---

## 1. PENDAHULUAN

Sistem pengiriman pengingat 1-on-1 dari Kader ke Pasien menggunakan endpoint `/api/kader/reminders/send`. Dokumen ini mencatat standarisasi kontrak API, format request dan response, mekanisme pencatatan data, serta integrasi Flutter UI.

---

## 2. SPESIFIKASI ENDPOINT API

- **URL**: `/api/kader/reminders/send`
- **Method**: `POST`
- **Autentikasi**: Bearer Token (Sanctum)
- **Role yang Diizinkan**: Kader Puskesmas (user_type_id: 3), Petugas Kesehatan (user_type_id: 1)
- **Role Dilarang**: Pasien (user_type_id: 2) -> HTTP 403 Forbidden

### A. Parameter Request (JSON Body)

| Parameter | Tipe | Wajib | Keterangan & Validasi |
|---|---|---|---|
| `patient_id` | Integer | Ya | ID Pasien (tabel `patients.id`). Harus terdaftar dan berada di bawah wilayah kerja Kader (Anti-IDOR). |
| `type` | String | Ya | Jenis pengingat. Nilai yang diizinkan: `'medication'` atau `'visit'`. |
| `message` | String | Ya | Isi pesan pengingat yang ditulis/diedit oleh Kader. Maksimal 1000 karakter. |
| `schedule_id` | Integer | Tidak | ID Kunjungan kontrol yang dituju (tabel `treatment_visits.id`). Opsional. |

### B. Format Response Berhasil (HTTP 200 OK)

```json
{
  "success": true,
  "message": "Pengingat kontrol berhasil dikirim kepada pasien.",
  "status": "sent",
  "error_reason": null,
  "data": {
    "reminder_id": 482,
    "patient_id": 105,
    "type": "visit",
    "delivery_status": "sent",
    "status": "sent",
    "error_reason": null,
    "reminder_record": {
      "id": 482,
      "sender_id": 14,
      "patient_id": 105,
      "patient_user_id": 88,
      "type": "visit",
      "title": "Pengingat Jadwal Kunjungan TB",
      "message": "Pengingat jadwal kontrol...",
      "status": "sent",
      "channel": "fcm",
      "created_at": "2026-11-08T08:30:00.000000Z"
    }
  }
}
```

### C. Penjelasan Status Kanal Transmisi (`delivery_status`)

Backend membedakan status pemrosesan permintaan (*request processing*) dengan status kanal transmisi perangkat (*delivery channel*):

1. `'sent'`: Push notification FCM berhasil dikirim dan diterima oleh gateway Firebase.
2. `'no_token'`: Pasien belum menginstal/login di aplikasi atau belum memiliki token FCM. Pesan tetap tersimpan di riwayat dan in-app notification center.
3. `'failed'`: Gateway FCM mengembalikan error/token kadaluarsa. Detail kegagalan dicatat pada field `error_reason`.

Pada semua kondisi di atas, karena pengingat telah berhasil dicatat pada basis data dan pesan live chat telah dikirimkan, backend mengembalikan `success: true` sehingga dialog dapat ditutup secara normal dan data monitoring terbarui.

---

## 3. IMPLEMENTASI KODE LARAVEL BACKEND

Berkas: `tb-care-web/app/Http/Controllers/Api/KaderController.php`

```php
public function sendReminder(Request $request)
{
    if ($errorResponse = $this->authorizeKaderAccess()) {
        return $errorResponse;
    }

    $user = Auth::user();

    $validator = Validator::make($request->all(), [
        'patient_id'  => 'required|exists:patients,id',
        'type'        => 'required|in:medication,visit',
        'message'     => 'required|string|max:1000',
        'schedule_id' => 'nullable|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal: ' . $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422);
    }

    $patient = Patient::with(['user', 'puskesmas'])->find($request->patient_id);
    if (!$patient->isAccessibleBy($user)) {
        return response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki wewenang mengirim pengingat ke pasien di luar wilayah binaan Anda.',
        ], 403);
    }

    // Pemrosesan FCM, Live Chat, Riwayat PatientReminder, dan In-App Notification
    ...

    $successMessage = ($request->type === 'visit')
        ? 'Pengingat kontrol berhasil dikirim kepada pasien.'
        : 'Pengingat minum obat berhasil dikirim kepada pasien.';

    return response()->json([
        'success'      => true,
        'message'      => $successMessage,
        'status'       => $status,
        'error_reason' => $errorReason,
        'data'         => [
            'reminder_id'     => $reminderRecord?->id,
            'patient_id'      => (int) $patient->id,
            'type'            => $request->type,
            'delivery_status' => $status,
            'status'          => $status,
            'error_reason'    => $errorReason,
        ],
    ], 200);
}
```

---

## 4. INTEGRASI AUTO-CLOSE & ERROR HANDLING DI FLUTTER

Berkas: `tb-care-mobile/lib/services/kader_reminder_service.dart`

```dart
// 1. Saat tombol ditekan: Loading aktif dan cegah double submission
setState(() => _isSending = true);

final result = await KaderReminderService.sendReminder(
  patientId: widget.patientId,
  type: _selectedType,
  message: text,
  scheduleId: widget.scheduleId,
  client: widget.client,
);

if (!context.mounted) return;

final bool success = result['success'] == true;
final String message = result['message'] ?? '';

// 2. Jika Berhasil:
if (success) {
  navigator.pop(); // Auto-close bottom sheet
  if (widget.onSuccess != null) {
    widget.onSuccess!(); // Refresh data induk
  }
  widget.rootMessenger.showSnackBar(
    SnackBar(content: Text(message), backgroundColor: const Color(0xFF2E7D32)),
  );
} 
// 3. Jika Gagal:
else {
  setState(() => _isSending = false); // Re-enable tombol
  // Modal tetap terbuka, pesan yang diketik TIDAK hilang!
  widget.rootMessenger.showSnackBar(
    SnackBar(content: Text(message), backgroundColor: const Color(0xFFC62828)),
  );
}
```

---

## 5. HASIL VERIFIKASI

- Dialog otomatis tertutup segera setelah backend mengonfirmasi `success: true`.
- SnackBar sukses berwarna hijau dengan pesan informatif dari backend tampil mengambang pada layar.
- Jika ada kesalahan validasi atau jaringan, modal tetap terbuka dan teks tidak terhapus.
