# LAPORAN IMPLEMENTASI BAGIAN JADWAL KONTROL HARI INI
## APLIKASI TB CARE (ROLE KADER)

---

## 1. PENDAHULUAN & TUJUAN FITUR

Bagian **Jadwal Kontrol Hari Ini** ditempatkan di bawah bagian Pasien Belum Minum Obat pada Beranda Kader. Fitur ini berfungsi membantu Kader memantau pasien dampingan yang dijadwalkan mengambil obat bulanan, pemeriksaan dahak ulang, atau konsultasi dokter di Puskesmas pada hari yang bersangkutan.

---

## 2. ATURAN BISNIS DAN FILTER DATA

Data jadwal kontrol yang disajikan memenuhi kriteria ketat berikut:
1. **Scoping Wilayah Kader:** Hanya mengambil kunjungan dari pasien di desa binaan Kader yang sedang aktif.
2. **Tanggal Eksak Hari Ini:** Kunjungan dengan `visit_date = Carbon::today('Asia/Jakarta')`.
3. **Pengecualian Status Batal:** Kunjungan dengan status `'Dibatalkan'` secara otomatis dieksklusikan dari penghitungan statistik dan daftar tampilan.
4. **Kronologis Waktu:** Diurutkan berdasarkan `visit_time` secara ascending (dari pagi hingga siang/sore).

---

## 3. STRUKTUR DAN KOMPONEN TAMPILAN

Setiap kartu jadwal kontrol menyajikan informasi penting:

1. **Avatar Inisial Pasien:**
   - Lingkaran solid berlatar warna indigo lembut (`#EEF2FF`) dengan huruf kapital nama pasien.
2. **Nama Pasien & Alamat:**
   - Nama diformat **Title Case** (`NameFormatter.formatPatientName`).
   - Alamat ringkas desa dan RT/RW.
3. **Badge Waktu Kontrol (WIB):**
   - Format waktu jam kunjungan disertai akhiran `WIB` (contoh: `09:00 WIB`).
   - Menggunakan ikon jam `Icons.schedule_rounded`.
4. **Lokasi Puskesmas:**
   - Ditampilkan bersama ikon fasilitas kesehatan `Icons.local_hospital_outlined` (contoh: `Puskesmas Tamansari`).
5. **Badge Status Kunjungan:**
   - Status `Terjadwal`: Warna latar biru lembut dengan teks biru tua.
   - Status `Selesai`: Warna latar hijau lembut dengan teks hijau tua.
6. **Tombol "Lihat Pasien":**
   - Menavigasi ke [`KaderPatientDetailPage`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/kader/patient_detail.dart).

---

## 4. TAMPILAN KONDISI KOSONG (EMPTY STATE)

Jika tidak ada jadwal kontrol pada hari ini:
- **Ikon:** `Icons.event_available_outlined` dengan warna abu-abu netral.
- **Judul:** `Tidak Ada Jadwal Kontrol Hari Ini`
- **Deskripsi:** `Tidak ada pasien dampingan yang memiliki jadwal kunjungan ke Puskesmas hari ini.`

---

## 5. KODE IMPLEMENTASI FLUTTER & LARAVEL

### A. Frontend Flutter (`lib/main/kader/home.dart`)
```dart
Widget _buildTodayControlScheduleCard(Map<String, dynamic> visit) {
  final patientName = NameFormatter.formatPatientName(
    visit['patient_name']?.toString() ?? 'Pasien',
  );
  final shortAddress = visit['patient_short_address']?.toString() ??
      visit['village_name']?.toString() ??
      '-';
  final visitTime = visit['visit_time_formatted']?.toString() ??
      '${visit['visit_time'] ?? '-'} WIB';
  final puskesmasName = visit['puskesmas_name']?.toString() ?? 'Puskesmas';
  final status = visit['visit_status']?.toString() ?? 'Terjadwal';

  return Container(
    margin: const EdgeInsets.only(bottom: 12),
    padding: const EdgeInsets.all(16),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(14),
      border: Border.all(color: const Color(0xFFE2E8F0)),
      boxShadow: [
        BoxShadow(
          color: Colors.black.withValues(alpha: 0.03),
          blurRadius: 8,
          offset: const Offset(0, 2),
        ),
      ],
    ),
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            CircleAvatar(
              radius: 20,
              backgroundColor: const Color(0xFFEEF2FF),
              child: Text(
                patientName.isNotEmpty ? patientName[0].toUpperCase() : 'P',
                style: const TextStyle(
                  color: Color(0xFF4F46E5),
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    patientName,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                  ),
                  Text(
                    shortAddress,
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                  ),
                ],
              ),
            ),
            _buildVisitStatusBadge(status),
          ],
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAFC),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            children: [
              const Icon(Icons.schedule_rounded, size: 15, color: Color(0xFF4F46E5)),
              const SizedBox(width: 6),
              Text(
                visitTime,
                style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                  color: Color(0xFF1E293B),
                ),
              ),
              const SizedBox(width: 16),
              const Icon(Icons.local_hospital_outlined, size: 15, color: Color(0xFF64748B)),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  puskesmasName,
                  style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton.icon(
            onPressed: () => _openPatientDetail(visit['patient_id']),
            icon: const Icon(Icons.arrow_forward_rounded, size: 16),
            label: const Text('Lihat Pasien'),
            style: OutlinedButton.styleFrom(
              foregroundColor: primaryColor,
              side: BorderSide(color: primaryColor),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
          ),
        ),
      ],
    ),
  );
}
```

### B. Backend Laravel Query (`KaderController.php`)
```php
$todayControlVisits = TreatmentVisit::with(['patient.user', 'patient.village', 'treatment.puskesmas'])
    ->whereIn('patient_id', $patientIds)
    ->whereDate('visit_date', $today)
    ->where('visit_status', '!=', 'Dibatalkan')
    ->orderBy('visit_time', 'asc')
    ->get();
```

---

## 6. HASIL PENGUJIAN OTOMATIS

- **Flutter Widget Test:** [`test/kader_dashboard_redesign_test.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/test/kader_dashboard_redesign_test.dart)
  - Memverifikasi render kartu kontrol hari ini dengan jam berakhiran WIB.
  - Memverifikasi smooth scroll dari kartu statistik `Jadwal Kontrol`.
  - Memverifikasi empty state ketika tidak ada jadwal kunjungan.
- **Laravel Feature Test:** [`tests/Feature/KaderDashboardRedesignTest.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/tests/Feature/KaderDashboardRedesignTest.php)
  - Test case: `test_cancelled_visits_are_excluded_from_control_schedules` (LULUS 100%).
  - Memverifikasi kunjungan berstatus `'Dibatalkan'` tidak ikut terhitung.
