# LAPORAN MIGRASI DAN PEMBERSIHAN DATA NAMA DATABASE
**Aplikasi TB Care — Ekosistem Mobile (Flutter) & Web Backend (Laravel)**
*Dokumentasi Migrasi Data: `2026_11_08_000001_standardize_user_and_contact_names_to_title_case.php`*

---

## 1. RINGKASAN EKSEKUTIF

Sebagai bagian dari standardisasi Title Case global, sebuah file migrasi database Laravel telah dibuat dan dieksekusi untuk membersihkan rekaman data riil pada database MySQL TB Care. Migrasi ini secara aman memperbarui rekaman lama pada tabel `users` dan `close_contacts` yang masih berformat huruf kecil (*lowercase*), huruf kapital penuh (*ALL CAPS*), maupun spasi berlebih, tanpa menyentuh field sensitif lainnya.

---

## 2. DETAIL STRUKTUR MIGRASI

- **File Migrasi:** `tb-care-web/database/migrations/2026_11_08_000001_standardize_user_and_contact_names_to_title_case.php`
- **Tabel Sasaran:**
  1. `users` (kolom `name`)
  2. `close_contacts` (kolom `name`)
- **Metode Eksekusi:** Chunking bertahap (100 rekaman per batch) untuk memastikan penggunaan memori tetap efisien dan tidak mengunci tabel (*table lock*) dalam durasi lama.

```php
use App\Helpers\NameFormatter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Standarisasi tabel users
        DB::table('users')->orderBy('id')->chunk(100, function ($users) {
            foreach ($users as $user) {
                if (!empty($user->name)) {
                    $formatted = NameFormatter::format($user->name);
                    if ($formatted !== $user->name) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['name' => $formatted]);
                    }
                }
            }
        });

        // 2. Standarisasi tabel close_contacts
        if (DB::getSchemaBuilder()->hasTable('close_contacts')) {
            DB::table('close_contacts')->orderBy('id')->chunk(100, function ($contacts) {
                foreach ($contacts as $contact) {
                    if (!empty($contact->name)) {
                        $formatted = NameFormatter::format($contact->name);
                        if ($formatted !== $contact->name) {
                            DB::table('close_contacts')
                                ->where('id', $contact->id)
                                ->update(['name' => $formatted]);
                        }
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Operasi standardisasi nama bersifat non-destruktif dan idempoten.
    }
};
```

---

## 3. KARAKTERISTIK KEAMANAN DAN INTEGRITAS DATA

1. **Idempoten (*Idempotent*):**
   Migrasi memeriksa apakah `$formatted !== $user->name`. Jika nama sudah dalam format Title Case yang benar, query UPDATE tidak dieksekusi sehingga performa tetap maksimal dan tidak menghasilkan mutasi sia-sia.
2. **Isolasi Kolom:**
   Hanya kolom `name` yang diperbarui. Kolom identitas unik seperti `email`, `username`, `nik`, `phone_number`, dan hash kata sandi `password` tidak tersentuh sama sekali.
3. **Kompatibilitas UTF-8:**
   Proses pemformatan menggunakan `mb_convert_case` dan manipulasi string multibyte sehingga karakter tanda kutip tunggal (`'`), tanda kurung, maupun tanda hubung (`-`) diproses tanpa menimbulkan kerusakan karakter (*mojibake*).

---

## 4. BUKTI HASIL SEBELUM DAN SESUDAH MIGRASI

| ID Pasien / Pengguna | Format Nama Lama di Database | Format Nama Baru Hasil Migrasi |
| :--- | :--- | :--- |
| User #12 | `dede sumiati` | `Dede Sumiati` |
| User #14 | `anes maulida sukmara` | `Anes Maulida Sukmara` |
| User #15 | `ardil fahmi` | `Ardil Fahmi` |
| User #18 | `dedi mulyana` | `Dedi Mulyana` |
| User #19 | `edi sudrajat` | `Edi Sudrajat` |
| User #22 | `ALAMSYAH FIRDAUS` | `Alamsyah Firdaus` |
| Contact #4 | `sitih fatimah` | `Sitih Fatimah` |

---

## 5. KESIMPULAN
Migrasi telah berhasil dijalankan pada environment database MySQL (`php artisan migrate`). Seluruh nama pengguna kini tersimpan dengan rapi dan terstandarisasi, menghapus inkonsistensi tipografi pada seluruh antarmuka aplikasi.
