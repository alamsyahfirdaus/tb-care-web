# LAPORAN PENGEMBANGAN: AKSES BROADCAST PENGINGAT DI APPBAR SECARA PERMANEN
**Sistem Aplikasi TB Care (Role Kader, Dokter, Perawat, Petugas TB, Farmasi, Laboratorium)**  
*Dokumen Arsitektur Navigasi & Aksi Pengingat Massal*

---

## 1. Latar Belakang & Masalah
Sebelumnya, akses ke halaman Pengingat Massal Minum Obat (`BulkReminderPage`) memiliki perilaku dinamis yang menimbulkan tumpang-tindih visual:
1. Jika daftar percakapan **kosong**, ikon megaphone (`Icons.campaign`) muncul di AppBar.
2. Jika daftar percakapan **sudah ada**, ikon di AppBar disembunyikan dan dipindahkan ke Floating Action Button (FAB ganda di kanan bawah: `FloatingActionButton.extended` bertuliskan *"Kirim Pengingat"* yang menumpuk di atas tombol chat).
3. Penumpukan dua FAB di pojok kanan bawah memakan ruang layar cukup besar dan menghalangi kartu percakapan paling bawah.

---

## 2. Solusi Desain Baru

1. **Akses Pengingat Dipindahkan Permanen ke AppBar:**
   - Ikon megaphone / broadcast (`Icons.campaign`, warna putih dengan tooltip *"Kirim Pengingat Minum Obat"*) diletakkan secara **permanen** pada `actions` di AppBar Konsultasi untuk seluruh role staf/kesehatan (`widget.isStaff`).
   - Ikon ini selalu dapat diakses baik saat percakapan masih kosong maupun saat sudah ada percakapan.

2. **Penyederhanaan Floating Action Button (Single FAB):**
   - Tombol ganda `FloatingActionButton.extended` bertuliskan *"Kirim Pengingat"* **dihapus sepenuhnya**.
   - Di pojok kanan bawah kini hanya ada **satu FAB standar** (Single FAB) dengan ikon chat dan tooltip **"Mulai Percakapan Baru"**.
   - Ketika berada dalam mode pilih percakapan (*selection mode*), FAB otomatis disembunyikan dan digantikan oleh bilah aksi hapus di bagian bawah.

---

## 3. Implementasi Kode (`ConversationListPage.dart`)

File [conversation_list_page.dart](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/chat/conversation_list_page.dart#L355-L370):

```dart
PreferredSizeWidget _buildAppBar() {
  if (_isSelectionMode) {
    return AppBar( ... );
  }

  return AppBar(
    toolbarHeight: 56.0,
    title: const Text('Konsultasi'),
    actions: [
      // Ikon broadcast SELALU muncul untuk staf/kader
      if (widget.isStaff)
        IconButton(
          icon: const Icon(Icons.campaign, color: Colors.white),
          tooltip: 'Kirim Pengingat Minum Obat',
          onPressed: () async {
            final refresh = await Navigator.push(
              context,
              MaterialPageRoute(builder: (context) => const BulkReminderPage()),
            );
            if (refresh == true) {
              _loadConversations();
            }
          },
        ),
      PopupMenuButton<String>( ... ),
    ],
  );
}
```

Dan pada FAB [conversation_list_page.dart](file:///d:/My%20Projects/AplikasiTBCare/tb-care-mobile/lib/main/chat/conversation_list_page.dart#L860-L875):

```dart
Widget? _buildFloatingActionButton() {
  // KONDISI A: Belum ada percakapan atau sedang loading/error/mode pilih -> Jangan tampilkan FAB
  if (_isSelectionMode || !_hasConversations || _isLoading || _errorMessage != null) {
    return null;
  }

  // KONDISI B: Sudah ada percakapan -> Tampilkan satu FAB "Mulai Percakapan Baru"
  return FloatingActionButton(
    heroTag: 'new_chat_fab',
    backgroundColor: AppColors.primary,
    foregroundColor: Colors.white,
    tooltip: 'Mulai Percakapan Baru',
    onPressed: _navigateToSelectRecipient,
    child: const Icon(Icons.chat),
  );
}
```

---

## 4. Hasil Pengujian Otomatis
```text
✓ When conversations is empty: AppBar shows Broadcast icon, FAB is hidden
✓ When conversations has items: AppBar shows Broadcast icon permanently, single FAB Mulai Percakapan Baru is shown
✓ FAB Kirim Pengingat is removed (findsNothing)
```
Seluruh pengujian lolos 100%.
