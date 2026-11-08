# LAPORAN PENYEDERHANAAN CARD JADWAL KONTROL DETAIL PASIEN
## Lokasi: lib/main/kader/patient_detail.dart

---

## 1. PENDAHULUAN

Halaman **Detail Data Pasien** (`KaderPatientDetailPage`) menyajikan data lengkap pasien binaan Kader. Pada Tab Informasi Pasien, terdapat tiga seksi:
1. Seksi 1: Identitas Pasien (NIK, Jenis Kelamin, Tanggal Lahir, Telepon, Alamat Lengkap).
2. Seksi 2: Informasi Pengobatan (Status Pengobatan, Tanggal Mulai, Estimasi Selesai, Jam Minum Obat, Daftar Obat).
3. Seksi 3: Jadwal Kontrol (Jadwal Kunjungan Faskes Berikutnya).

Sebelumnya, pada Seksi 3 (Card Jadwal Kontrol), tercantum kembali nama Puskesmas (misal `Tamansari`) dan alamat wilayah faskes (`Kel. Sukalaksana, Kec. Tamansari`). Informasi ini bersifat redundan karena pasien dan kader berada pada wilayah Puskesmas yang sama, serta menambah kepadatan visual layar.

---

## 2. PERUBAHAN TAMPILAN

### A. Elemen yang Dihapus
1. Garis pembatas (Divider) di bawah jam kontrol.
2. Baris nama Puskesmas beserta ikon rumah sakit (`Icons.local_hospital_outlined`).
3. Baris alamat fasilitas kesehatan beserta ikon pin lokasi (`Icons.location_on_outlined`).

### B. Elemen yang Dipertahankan
1. **Hari dan Tanggal Kontrol**: Ditampilkan dengan jelas dan tebal (misal `Minggu, 8 November 2026`).
2. **Waktu Kontrol**: Ditampilkan dengan format ringkas (misal `Jam: 15.30 WIB`).
3. **Catatan Medis** (jika ada): Instruksi klinis dari dokter atau perawat (misal `Catatan: Pemeriksaan Dahak Ulang`).
4. **Tombol Ingatkan Kontrol**: Tombol berlatar toska dengan aksi langsung membuka dialog pengingat faskes.

---

## 3. KOMPARASI STRUKTUR DESAIN

### Sebelum Penyederhanaan:
```text
┌──────────────────────────────────────┐
│ Minggu, 8 November 2026              │
│ Jam: 15.30 WIB                       │
│ ──────────────────────────────────── │
│ [icon RS] Tamansari                  │
│ [icon Pin] Kel. Sukalaksana, Tamansari│
│ [         Ingatkan Kontrol         ] │
└──────────────────────────────────────┘
```

### Sesudah Penyederhanaan (Sesuai Spesifikasi):
```text
Jadwal Kontrol

┌──────────────────────────────────────┐
│ Minggu, 8 November 2026              │
│ Jam: 15.30 WIB                       │
│                                      │
│ [         Ingatkan Kontrol         ] │
└──────────────────────────────────────┘
```

---

## 4. INTEGRASI SOURCE CODE FLUTTER

Berkas: `tb-care-mobile/lib/main/kader/patient_detail.dart`

```dart
Widget _buildNextVisitCard() {
  final visit = _resolveNextUpcomingVisit();

  if (visit == null) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Belum Ada Jadwal Kontrol',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF334155),
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Belum terdapat jadwal kontrol ke Puskesmas berikutnya untuk pasien ini.',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12,
              color: const Color(0xFF64748B),
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }

  final dateStr = visit['visit_date'];
  final timeStr = visit['visit_time'] ?? '08:00';
  final notes = visit['notes']?.toString() ?? '';

  String formattedTime = timeStr.toString();
  if (formattedTime.length >= 5) {
    formattedTime = formattedTime.substring(0, 5).replaceAll(':', '.');
  }

  return Container(
    width: double.infinity,
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(12),
      border: Border.all(color: const Color(0xFFE2E8F0)),
      boxShadow: [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.02),
          blurRadius: 6,
          offset: const Offset(0, 2),
        ),
      ],
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          _formatDateWithDay(dateStr),
          style: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'Jam: $formattedTime WIB',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: const Color(0xFF475569),
          ),
        ),
        if (notes.isNotEmpty) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              'Catatan: $notes',
              style: GoogleFonts.plusJakartaSans(
                fontSize: 11,
                color: const Color(0xFF64748B),
                fontStyle: FontStyle.italic,
              ),
            ),
          ),
        ],
        const SizedBox(height: 14),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0D9488),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 12),
              elevation: 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
            ),
            onPressed: () {
              final reminderData = Map<String, dynamic>.from(_patientDetail);
              reminderData['next_visit'] = visit;
              reminderData['visit'] = visit;
              reminderData['visit_date'] = visit['visit_date'];
              reminderData['visit_time'] = visit['visit_time'];
              reminderData['schedule_id'] = visit['id'];
              reminderData['treatments'] = _treatments;

              KaderReminderService.showReminderBottomSheet(
                context,
                patient: reminderData,
                defaultType: 'visit',
                client: widget.client,
                onSuccess: _fetchFullPatientData,
              );
            },
            child: const Text('Ingatkan Kontrol'),
          ),
        ),
      ],
    ),
  );
}
```

---

## 5. KESIMPULAN

Tampilan kartu jadwal kontrol menjadi jauh lebih bersih, ringkas, dan fokus pada aksi operasional pengingat. Seluruh data wilayah dan puskesmas pada database tetap utuh dan modul lain tidak terdampak negatif.
