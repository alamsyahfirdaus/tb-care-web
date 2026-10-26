<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AppVersionController extends Controller
{
    /**
     * Tampilkan daftar rilis versi aplikasi mobile.
     */
    public function index()
    {
        $versions = AppVersion::with('creator')
            ->orderByDesc('version_code')
            ->paginate(15);

        $latestActive = AppVersion::getLatestActive('android');

        return view('admin.app_versions.index', compact('versions', 'latestActive'))->with([
            'title'        => 'Manajemen Versi Aplikasi Mobile',
            'pageTitle'    => 'Pembaruan Aplikasi TB Care',
            'pageSubtitle' => 'Kelola rilis APK Android, konfigurasi pembaruan otomatis, dan catatan rilis.',
        ]);
    }

    /**
     * Tampilkan form publikasi versi baru.
     */
    public function create()
    {
        $latest = AppVersion::orderByDesc('version_code')->first();
        $suggestedVersionCode = $latest ? $latest->version_code + 1 : 1;
        $suggestedMinVersionCode = $latest ? $latest->min_supported_version_code : 1;

        return view('admin.app_versions.create', compact('latest', 'suggestedVersionCode', 'suggestedMinVersionCode'))->with([
            'title'        => 'Rilis Versi Baru TB Care Mobile',
            'pageTitle'    => 'Unggah & Rilis Versi Baru',
            'pageSubtitle' => 'Publikasikan file APK rilis baru atau tautan unduhan untuk pengguna Android.',
        ]);
    }

    /**
     * Simpan rilis versi baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'platform'                   => 'required|string|in:android',
            'version_name'               => 'required|string|max:50',
            'version_code'               => 'required|integer|min:1',
            'min_supported_version_code' => 'required|integer|min:1',
            'title'                      => 'nullable|string|max:150',
            'release_notes'              => 'nullable|string',
            'apk_file'                   => 'nullable|file|max:102400', // max 100MB
            'download_url'               => 'nullable|url|max:500',
            'sha256_checksum'            => 'nullable|string|max:64',
            'is_force_update'            => 'nullable|boolean',
            'is_active'                  => 'nullable|boolean',
        ], [
            'version_name.required'               => 'Nama versi wajib diisi (contoh: 1.0.1).',
            'version_code.required'               => 'Version code (build number) wajib diisi.',
            'min_supported_version_code.required' => 'Version code minimum yang didukung wajib diisi.',
            'apk_file.max'                        => 'Ukuran file APK maksimal 100 MB.',
            'download_url.url'                    => 'Format URL unduhan tidak valid.',
        ]);

        $apkPath = null;
        $fileSize = null;
        $sha256 = $validated['sha256_checksum'] ?? null;

        // Tangani upload file APK
        if ($request->hasFile('apk_file')) {
            $file = $request->file('apk_file');
            $extension = strtolower($file->getClientOriginalExtension());

            if ($extension !== 'apk') {
                return back()->withInput()->withErrors(['apk_file' => 'Hanya file dengan ekstensi .apk yang diperbolehkan.']);
            }

            $fileName = 'tbcare-v' . $validated['version_name'] . '-b' . $validated['version_code'] . '-' . time() . '.apk';
            $apkPath = $file->storeAs('apk_releases', $fileName, 'public');
            $fileSize = $file->getSize();

            // Hitung SHA-256 checksum secara otomatis dari file yang diunggah
            $realPath = $file->getRealPath();
            if (file_exists($realPath)) {
                $sha256 = hash_file('sha256', $realPath);
            }
        }

        // Pastikan ada salah satu: file APK terunggah atau URL unduhan eksternal
        if (!$apkPath && empty($validated['download_url'])) {
            return back()->withInput()->withErrors(['apk_file' => 'Silakan unggah file APK atau masukkan URL unduhan eksternal.']);
        }

        $appVersion = AppVersion::create([
            'platform'                   => $validated['platform'],
            'version_name'               => trim($validated['version_name']),
            'version_code'               => (int) $validated['version_code'],
            'min_supported_version_code' => (int) $validated['min_supported_version_code'],
            'title'                      => $validated['title'] ?? ('Pembaruan TB Care v' . $validated['version_name']),
            'release_notes'              => $validated['release_notes'],
            'apk_path'                   => $apkPath,
            'download_url'               => $validated['download_url'] ?? null,
            'file_size'                  => $fileSize,
            'sha256_checksum'            => $sha256,
            'is_force_update'            => $request->boolean('is_force_update'),
            'is_active'                  => $request->boolean('is_active', true),
            'published_at'               => now(),
            'created_by'                 => Auth::id(),
        ]);

        ActivityLog::log(
            'Rilis Versi Aplikasi',
            'Versi Aplikasi',
            "Merilis versi baru TB Care v{$appVersion->version_name} (Build {$appVersion->version_code})"
        );

        return redirect()->route('app-versions.index')->with('success', "Versi v{$appVersion->version_name} berhasil dirilis.");
    }

    /**
     * Tampilkan form edit versi.
     */
    public function edit($id)
    {
        $version = AppVersion::findOrFail($id);

        return view('admin.app_versions.edit', compact('version'))->with([
            'title'        => 'Edit Versi Aplikasi: v' . $version->version_name,
            'pageTitle'    => 'Edit Versi v' . $version->version_name,
            'pageSubtitle' => 'Perbarui informasi catatan rilis, batas minimum, dan status pembaruan.',
        ]);
    }

    /**
     * Perbarui data versi.
     */
    public function update(Request $request, $id)
    {
        $version = AppVersion::findOrFail($id);

        $validated = $request->validate([
            'version_name'               => 'required|string|max:50',
            'min_supported_version_code' => 'required|integer|min:1',
            'title'                      => 'nullable|string|max:150',
            'release_notes'              => 'nullable|string',
            'download_url'               => 'nullable|url|max:500',
            'sha256_checksum'            => 'nullable|string|max:64',
            'apk_file'                   => 'nullable|file|max:102400',
        ]);

        if ($request->hasFile('apk_file')) {
            $file = $request->file('apk_file');
            $extension = strtolower($file->getClientOriginalExtension());
            if ($extension !== 'apk') {
                return back()->withInput()->withErrors(['apk_file' => 'Hanya file dengan format .apk yang diperbolehkan.']);
            }

            // Hapus file lama jika ada
            if ($version->apk_path && Storage::disk('public')->exists($version->apk_path)) {
                Storage::disk('public')->delete($version->apk_path);
            }

            $fileName = 'tbcare-v' . $validated['version_name'] . '-b' . $version->version_code . '-' . time() . '.apk';
            $version->apk_path = $file->storeAs('apk_releases', $fileName, 'public');
            $version->file_size = $file->getSize();
            $version->sha256_checksum = hash_file('sha256', $file->getRealPath());
        }

        $version->version_name = trim($validated['version_name']);
        $version->min_supported_version_code = (int) $validated['min_supported_version_code'];
        $version->title = $validated['title'];
        $version->release_notes = $validated['release_notes'];
        if (!empty($validated['download_url'])) {
            $version->download_url = $validated['download_url'];
        }
        if (!empty($validated['sha256_checksum'])) {
            $version->sha256_checksum = $validated['sha256_checksum'];
        }
        $version->is_force_update = $request->boolean('is_force_update');
        $version->is_active = $request->boolean('is_active');
        $version->save();

        ActivityLog::log(
            'Perbarui Versi Aplikasi',
            'Versi Aplikasi',
            "Memperbarui konfigurasi versi v{$version->version_name} (Build {$version->version_code})"
        );

        return redirect()->route('app-versions.index')->with('success', "Versi v{$version->version_name} berhasil diperbarui.");
    }

    /**
     * Ubah status aktif/nonaktif rilis versi.
     */
    public function toggleActive($id)
    {
        $version = AppVersion::findOrFail($id);
        $version->is_active = !$version->is_active;
        $version->save();

        $statusStr = $version->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::log(
            'Ubah Status Rilis Versi',
            'Versi Aplikasi',
            "Versi v{$version->version_name} {$statusStr}."
        );

        return back()->with('success', "Versi v{$version->version_name} berhasil {$statusStr}.");
    }

    /**
     * Hapus rilis versi dan file APK fisiknya.
     */
    public function destroy($id)
    {
        $version = AppVersion::findOrFail($id);

        if ($version->apk_path && Storage::disk('public')->exists($version->apk_path)) {
            Storage::disk('public')->delete($version->apk_path);
        }

        $name = $version->version_name;
        $code = $version->version_code;
        $version->delete();

        ActivityLog::log(
            'Hapus Versi Aplikasi',
            'Versi Aplikasi',
            "Menghapus rilis TB Care v{$name} (Build {$code})"
        );

        return redirect()->route('app-versions.index')->with('success', "Versi v{$name} berhasil dihapus.");
    }
}
