# LAPORAN PENYEMPURNAAN AKSES BROADCAST PENGINGAT
## Halaman Konsultasi (Role Kader & Seluruh Role Petugas)

---

## 1. LATAR BELAKANG DAN MASALAH

Pada halaman **Konsultasi** (`ConversationListPage`), staf kesehatan (Kader dan Petugas Medis) memiliki fitur untuk mengirimkan pesan pengingat masal / broadcast kepada seluruh pasien binaannya.

Namun, sebelumnya terjadi inkonsistensi dan redundansi visual:
- Terdapat ikon *campaign/broadcast* (toa pengeras suara) di bagian `AppBar`.
- Secara bersamaan, terdapat `FloatingActionButton` bertuliskan `Kirim Pengingat` atau ikon pesan di pojok kanan bawah layar.

Keberadaan dua tombol untuk fungsi yang sama dalam satu layar membingungkan pengguna dan membuat antarmuka terasa padat.

---

## 2. KETENTUAN LOGIKA BARU

Diterapkan aturan kemunculan berbasis status daftar percakapan (`_hasConversations`):

```text
                                  ┌───────────────────────────────┐
                                  │   Apakah percakapan kosong?   │
                                  └───────────────┬───────────────┘
                                                  │
                         ┌────────────────────────┴────────────────────────┐
                         ▼ YA                                              ▼ TIDAK
         ┌───────────────────────────────┐                 ┌───────────────────────────────┐
         │  Tampilkan ikon Broadcast     │                 │ Sembunyikan ikon Broadcast    │
         │  di AppBar (Icons.campaign)   │                 │ di AppBar                     │
         └───────────────┬───────────────┘                 └───────────────┬───────────────┘
                         │                                                 │
                         ▼                                                 ▼
         ┌───────────────────────────────┐                 ┌───────────────────────────────┐
         │  Layar menampilkan banner     │                 │ Tampilkan Floating Action     │
         │  empty state (belum ada chat) │                 │ Button (FAB) "Kirim Pengingat"│
         └───────────────────────────────┘                 └───────────────────────────────┘
```

1. **Kondisi Percakapan Kosong (`conversations.isEmpty`)**:
   - Ikon Broadcast (`Icons.campaign`) **ditampilkan** pada `AppBar`.
   - Hal ini memastikan Kader/Petugas tetap memiliki akses mudah untuk memicu pengingat masal pertama kali meskipun belum pernah melakukan percakapan 1-on-1 dengan pasien mana pun.
   - Layar menampilkan empty state yang bersih tanpa tombol mengambang yang menutupi instruksi.
2. **Kondisi Percakapan Terisi (`conversations.isNotEmpty`)**:
   - Ikon Broadcast pada `AppBar` **disembunyikan**.
   - Kader/Petugas mengakses fitur pengingat melalui `FloatingActionButton` (FAB) `Kirim Pengingat` di pojok kanan bawah layar.

---

## 3. IMPLEMENTASI KODE

Berkas: `tb-care-mobile/lib/main/chat/conversation_list_page.dart`

```dart
AppBar _buildAppBar() {
  return AppBar(
    title: Text(_isSelectionMode ? 'Pilih Percakapan' : 'Konsultasi'),
    backgroundColor: const Color(0xFF2563EB),
    actions: [
      if (!_isSelectionMode) ...[
        // Hanya muncul jika isStaff bernilai true DAN daftar percakapan KOSONG
        if (widget.isStaff && !_hasConversations)
          IconButton(
            icon: const Icon(Icons.campaign, color: Colors.white),
            tooltip: 'Kirim Pengingat Masal',
            onPressed: () => _openBulkReminderModal(),
          ),
        // Menu 3 titik untuk tindakan tambahan
        PopupMenuButton<String>(
          ...
        ),
      ],
    ],
  );
}
```

---

## 4. CAKUPAN PENGGUNA (ROLE)

Pembaruan ini diterapkan secara terpusat pada widget `ConversationListPage`, sehingga mencakup:
1. **Role Kader** (yang memiliki wilayah tugas kelurahan/desa).
2. **Seluruh Role Petugas Medis**:
   - Dokter Puskesmas
   - Perawat Puskesmas
   - Petugas TB (PJ TB)
   - Petugas Farmasi / Apoteker
   - Petugas Laboratorium
   - Administrator Puskesmas

Semua role staf menikmati kenyamanan navigasi yang seragam dan intuitif tanpa redundansi tombol di layar.
