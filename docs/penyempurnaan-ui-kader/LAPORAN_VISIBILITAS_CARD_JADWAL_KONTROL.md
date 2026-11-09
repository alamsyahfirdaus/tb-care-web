# LAPORAN IMPLEMENTASI VISIBILITAS KONDISIONAL CARD JADWAL KONTROL
## HALAMAN DETAIL DATA PASIEN (TAB INFORMASI PASIEN) — TB CARE MOBILE

---

## 1. Latar Belakang dan Tujuan

Sebelumnya, pada halaman **Detail Data Pasien → Tab Informasi Pasien** (`lib/main/kader/patient_detail.dart`), bagian **Jadwal Kontrol** selalu ditampilkan secara permanen pada bagian bawah layar. Ketika pasien belum memiliki jadwal kunjungan ke Puskesmas atau seluruh jadwalnya telah lewat/selesai, sistem menampilkan card *empty state* dengan teks *"Belum Ada Jadwal Kontrol"*, deskripsi *"Belum terdapat jadwal kontrol ke Puskesmas berikutnya untuk pasien ini."*, dan tombol *"Ingatkan Kontrol"* dalam status *disabled* (abu-abu).

Kondisi tersebut menimbulkan redundansi visual dan memakan ruang layar secara tidak efisien bagi pasien yang memang belum membutuhkan kontrol Puskesmas terdekat.

**Tujuan Implementasi:**
1. Menyembunyikan seluruh bagian Jadwal Kontrol (judul bagian, card/container, teks empty state, tombol disabled, serta jarak/whitespace berlebih) apabila pasien **belum** memiliki jadwal kontrol yang aktif dan valid.
2. Menampilkan card Jadwal Kontrol secara normal dan rapi apabila pasien **memiliki** jadwal kontrol aktif mendatang yang valid, lengkap dengan:
   - Hari dan tanggal kontrol (Bahasa Indonesia).
   - Waktu kontrol format `HH.mm WIB`.
   - Catatan pemeriksaan/tujuan kontrol (jika tersedia).
   - Tombol aktif *"Ingatkan Kontrol"* yang langsung membuka modal kirim pengingat kontrol.
3. Menjamin integritas data: Riwayat kunjungan pasien di database backend tidak dihapus atau dimutasi; penyaringan murni berlaku pada *presentation/conditional rendering logic*.

---

## 2. Kriteria Validitas Jadwal Kontrol

Jadwal kontrol dianggap **valid dan aktif** untuk ditampilkan jika memenuhi ketiga syarat berikut:
1. **Waktu Pelaksanaan Mendatang (`!_isVisitPassed`):** Tanggal kontrol (`visit_date`) dan jam kontrol (`visit_time`) belum melewati waktu saat ini (`DateTime.now()`).
2. **Status Aktif (`_isVisitActive`):** Status kunjungan bukan merupakan status terminasi/batal, yaitu tidak termasuk dalam himpunan: `['dibatalkan', 'batal', 'selesai', 'hadir']`.
3. **Data Tidak Kosong:** Nilai jadwal kontrol tidak bernilai `null` atau Map kosong.

Apabila seluruh jadwal kunjungan pasien sudah melewati waktu saat ini atau berstatus batal/selesai, maka pasien dinyatakan tidak memiliki jadwal kontrol aktif, sehingga bagian Jadwal Kontrol otomatis disembunyikan seluruhnya.

---

## 3. Rincian Modifikasi Kode

### A. File: `tb-care-mobile/lib/main/kader/patient_detail.dart`

#### 1. Validasi Status Kunjungan (`_isVisitActive`)
Menambahkan fungsi helper untuk memvalidasi apakah status jadwal kontrol masih aktif atau sudah batal/selesai:

```dart
/// Memeriksa apakah suatu jadwal kontrol masih aktif (bukan batal atau selesai)
bool _isVisitActive(Map<String, dynamic> v) {
  final status = (v['visit_status'] ?? v['status'] ?? 'Terjadwal')
      .toString()
      .toLowerCase()
      .trim();
  if (status == 'dibatalkan' ||
      status == 'batal' ||
      status == 'selesai' ||
      status == 'hadir') {
    return false;
  }
  return true;
}
```

#### 2. Penyempurnaan Resolusi Jadwal Kunjungan Terdekat (`_resolveNextUpcomingVisit`)
Memastikan `_nextVisit` dari API backend maupun daftar kunjungan di dalam `_treatments` sama-sama melewati filter `_isVisitActive` dan `!_isVisitPassed`:

```dart
Map<String, dynamic>? _resolveNextUpcomingVisit() {
  if (_nextVisit != null &&
      _nextVisit!.isNotEmpty &&
      _isVisitActive(_nextVisit!) &&
      !_isVisitPassed(_nextVisit!)) {
    return _nextVisit;
  }

  final now = DateTime.now();
  final todayStr = DateFormat('yyyy-MM-dd').format(now);

  final List<Map<String, dynamic>> candidateVisits = [];
  for (final tr in _treatments) {
    if (tr['visits'] is List) {
      for (final v in tr['visits']) {
        if (v is Map) {
          final dateStr = v['visit_date']?.toString();
          if (dateStr == null || dateStr.isEmpty) continue;

          final map = Map<String, dynamic>.from(v);
          if (!_isVisitActive(map)) continue;

          final cleanDate = dateStr.split('T').first.split(' ').first;
          if (cleanDate.compareTo(todayStr) >= 0) {
            if (!_isVisitPassed(map)) {
              candidateVisits.add(map);
            }
          }
        }
      }
    }
  }

  if (candidateVisits.isEmpty) return null;

  candidateVisits.sort((a, b) {
    final dateA = (a['visit_date'] ?? '').toString();
    final dateB = (b['visit_date'] ?? '').toString();
    final dateComp = dateA.compareTo(dateB);
    if (dateComp != 0) return dateComp;

    final timeA = (a['visit_time'] ?? '').toString();
    final timeB = (b['visit_time'] ?? '').toString();
    return timeA.compareTo(timeB);
  });

  return candidateVisits.first;
}
```

#### 3. Kondisional Rendering pada Tab Informasi Pasien (`_buildInformationTab`)
Mengondisikan seluruh Section 3 (Header Judul, Spacer, Card, dan Bottom Margin) dengan `if (upcomingVisit != null) ...`:

```dart
// Section 3: Jadwal Kontrol Puskesmas Berikutnya (Hanya tampil jika pasien memiliki jadwal kontrol aktif mendatang)
if (upcomingVisit != null) ...[
  _buildSectionHeader('Jadwal Kontrol'),
  const SizedBox(height: 8),
  _buildNextVisitCard(upcomingVisit),
  const SizedBox(height: 24),
],
```

Jika `upcomingVisit == null`, tidak ada satu pun elemen Section 3 yang dimasukkan ke dalam widget tree. Layout mengalir langsung selesai setelah Section 2.5 (Daftar Obat) atau Section 2 (Informasi Pengobatan) dengan padding natural tanpa whitespace berlebih.

#### 4. Card Jadwal Kontrol Bersih (`_buildNextVisitCard`)
Card mengembalikan `const SizedBox.shrink()` jika data kunjungan null, dan menampilkan card informatif serta tombol aktif jika ada:

```dart
Widget _buildNextVisitCard([Map<String, dynamic>? visitData]) {
  final visit = visitData ?? _resolveNextUpcomingVisit();
  if (visit == null) {
    return const SizedBox.shrink();
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
              textStyle: GoogleFonts.plusJakartaSans(
                fontWeight: FontWeight.bold,
                fontSize: 13,
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

## 4. Hasil Pengujian dan Verifikasi

### A. Pengujian Widget & Integrasi Flutter (`flutter test`)
Semua rangkaian tes unit dan widget aplikasi mobile dijalankan:
- `test/kader_patient_detail_redesign_test.dart`
  - `Tab 1 (Informasi) completely hides Jadwal Kontrol section when no upcoming visit exists` : **PASS**
  - `Tab 1 (Informasi) hides Jadwal Kontrol section when visit is cancelled (Dibatalkan)` : **PASS**
  - `Tab 1 (Informasi) simplifies address and has Section 3 titled "Jadwal Kontrol"` (saat jadwal aktif ada) : **PASS**
- `test/kader_pengingat_dan_konsultasi_refinement_test.dart`
  - `Detail Pasien hides visit schedule that has already passed` : **PASS**
- Seluruh rangkaian test suite Flutter (`37 file test, 242 tests`):
  ```text
  01:10 +242: All tests passed!
  ```

### B. Pengujian Backend Laravel (`php artisan test`)
- `KaderDashboardRedesignTest`: 6/6 tests passed.
- `KaderReminderFlowTest`: 8/8 tests passed.
- Integritas data database aman (tidak ada `DELETE` atau perubahan paksa pada data kunjungan pasien).

---

## 5. Ringkasan Status

| Skenario Pasien | Status Tampilan Bagian Jadwal Kontrol | Status Tombol Ingatkan Kontrol |
|---|---|---|
| Memiliki jadwal kontrol aktif mendatang | Ditampilkan normal (Hari/Tgl, Jam WIB, Catatan) | Aktif (Hijau Teal), membuka modal pengingat |
| Tidak memiliki jadwal kontrol sama sekali | **Disembunyikan sepenuhnya** (tidak ada header/card) | Tidak ada (disembunyikan) |
| Seluruh jadwal kontrol sudah lewat waktu (`now > dt`) | **Disembunyikan sepenuhnya** | Tidak ada (disembunyikan) |
| Jadwal berstatus `Dibatalkan` / `Batal` | **Disembunyikan sepenuhnya** | Tidak ada (disembunyikan) |
| Jadwal berstatus `Selesai` / `Hadir` | **Disembunyikan sepenuhnya** | Tidak ada (disembunyikan) |
