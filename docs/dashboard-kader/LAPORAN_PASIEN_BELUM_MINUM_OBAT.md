# LAPORAN IMPLEMENTASI BAGIAN PASIEN BELUM MINUM OBAT
## APLIKASI TB CARE (ROLE KADER)

---

## 1. PENDAHULUAN & TUJUAN FITUR

Bagian **Pasien Belum Minum Obat** ditempatkan langsung di bawah 4 kartu statistik pada Beranda Kader. Fitur ini dirancang sebagai *action center* prioritas tinggi harian bagi Kader kesehatan untuk segera mengetahui pasien dampingan mana saja yang belum meminum atau belum mengonfirmasi obatnya pada hari ini tanpa perlu membuka daftar pasien secara manual.

---

## 2. ATURAN BISNIS DAN KRITERIA DATA

Data pasien yang ditampilkan pada bagian ini memenuhi ketentuan bisnis berikut:
1. **Wilayah Binaan Kader (Scoping RBAC):** Pasien bertempat tinggal di desa/kelurahan yang tercatat dalam `kader_areas` milik kader yang sedang login.
2. **Pengobatan Aktif:** Pasien memiliki status pengobatan `treatment_status = 'Berjalan'`.
3. **Status Hari Ini:** Belum memiliki catatan `medication_records` yang sah pada hari ini (tanggal lokal Asia/Jakarta). Catatan dengan status `'Terlewat'` tetap diklasifikasikan sebagai belum minum obat sampai kader melakukan verifikasi lapangan.
4. **Urutan Tampilan:** Diurutkan berdasarkan waktu minum obat dari yang paling awal (`medication_time` ASC) agar pasien dengan jadwal pagi hari mendapat atensi tercepat.

---

## 3. SPESIFIKASI KOMPONEN VISUAL

Setiap kartu pasien pada bagian ini memuat elemen visual terstruktur:

1. **Avatar Inisial:**
   - Lingkaran solid warna biru lembut dengan huruf inisial kapital pertama nama pasien.
2. **Identitas Pasien:**
   - Nama lengkap yang diformat dengan **Title Case** (`NameFormatter.formatPatientName`).
   - Alamat ringkas pasien (format: `RT xx/RW xx, Desa xxx`).
3. **Badge Waktu Minum Obat:**
   - Format waktu jam dan menit disertai zona waktu `WIB` (contoh: `07:00 WIB`, `14:30 WIB`).
   - Menggunakan ikon jam `Icons.access_time_rounded`.
4. **Badge Status Peringatan:**
   - Teks: `Belum konfirmasi minum obat`.
   - Warna latar: Amber muda (`#FFFBEB`), garis batas oranye (`#FED7AA`), warna teks oranye tua (`#B45309`).
5. **Tombol Aksi "Lihat Pasien":**
   - Tombol solid dengan label `Lihat Pasien` dan ikon panah kanan `Icons.arrow_forward_rounded`.
   - Menavigasi langsung ke [`KaderPatientDetailPage`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/kader/patient_detail.dart) dengan membawa data pasien dan riwayat obat.
6. **Tombol Header "Lihat Semua":**
   - Berada di baris judul bagian.
   - Menavigasi ke Tab Pasien dan mengaktifkan filter `need_reminder`.

---

## 4. TAMPILAN KONDISI KOSONG (EMPTY STATE)

Apabila seluruh pasien dampingan telah minum obat pada hari tersebut, aplikasi menampilkan *Empty State* yang ramah dan positif:
- **Ikon:** `Icons.check_circle_outline_rounded` berwarna hijau emerald.
- **Judul:** `Semua Pasien Sudah Minum Obat`
- **Deskripsi:** `Hebat! Seluruh pasien dampingan Anda telah mengonfirmasi minum obat hari ini.`
- **Visual:** Kartu solid bersih tanpa elemen yang membingungkan kader.

---

## 5. IMPLEMENTASI TEKNIS

### A. Frontend Flutter (`lib/main/kader/home.dart`)
```dart
Widget _buildUnconfirmedPatientsSection() {
  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                width: 4,
                height: 18,
                decoration: BoxDecoration(
                  color: const Color(0xFFD97706),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 8),
              const Text(
                'Pasien Belum Minum Obat',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ],
          ),
          if (_unconfirmedPatients.isNotEmpty)
            TextButton(
              onPressed: () {
                widget.onTabSelected?.call(1);
                KaderPatientListPage.activeFilterNotifier.value = 'need_reminder';
              },
              child: const Text('Lihat Semua'),
            ),
        ],
      ),
      const SizedBox(height: 10),
      if (_unconfirmedPatients.isEmpty)
        _buildAllTakenEmptyState()
      else
        ..._unconfirmedPatients.take(5).map(_buildUnconfirmedPatientCard),
    ],
  );
}
```

### B. Backend Laravel Controller (`KaderController.php`)
```php
$unconfirmedPatients = [];
foreach ($activeTreatments as $treatment) {
    $patient = $treatment->patient;
    if (!$patient) continue;

    $takenToday = $todayMedications->has($patient->id);
    if (!$takenToday) {
        $medTime = $treatment->medication_time
            ? substr($treatment->medication_time, 0, 5)
            : '-';

        $unconfirmedPatients[] = [
            'patient_id'        => $patient->id,
            'name'              => NameFormatter::formatPatientName($patient->user->name ?? $patient->name ?? 'Pasien'),
            'nik'               => $patient->nik ?? '-',
            'village_name'      => $patient->village->name ?? '-',
            'address'           => $patient->address ?? '-',
            'rt'                => $patient->rt ?? '',
            'rw'                => $patient->rw ?? '',
            'short_address'     => trim(($patient->rt ? 'RT ' . $patient->rt : '') . ($patient->rw ? '/RW ' . $patient->rw : '') . ', ' . ($patient->village->name ?? '-'), ', '),
            'medication_time'   => $medTime,
            'medication_time_formatted' => $medTime !== '-' ? $medTime . ' WIB' : 'Waktu Belum Diatur',
            'status_label'      => 'Belum konfirmasi minum obat',
            'treatment_id'      => $treatment->id,
            'phone'             => $patient->user->phone ?? '-',
        ];
    }
}
usort($unconfirmedPatients, fn($a, $b) => strcmp($a['medication_time'], $b['medication_time']));
```

---

## 6. HASIL PENGUJIAN OTOMATIS

- **Flutter Widget Test:** [`test/kader_dashboard_redesign_test.dart`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/test/kader_dashboard_redesign_test.dart)
  - Memverifikasi render kartu pasien belum minum obat dengan badge jam WIB.
  - Memverifikasi tombol `Lihat Pasien` membuka halaman detail.
  - Memverifikasi empty state ter-render dengan teks `"Semua Pasien Sudah Minum Obat"`.
- **Laravel Feature Test:** [`tests/Feature/KaderDashboardRedesignTest.php`](file:///d:/My%20Projects/AplikasiTBCare/tb-care-web/tests/Feature/KaderDashboardRedesignTest.php)
  - Memverifikasi payload `unconfirmed_patients` memuat data lengkap dan terurut secara kronologis.
