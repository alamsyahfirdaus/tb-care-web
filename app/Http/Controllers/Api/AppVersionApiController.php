<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppVersionApiController extends Controller
{
    /**
     * Endpoint Publik: Memeriksa versi aplikasi mobile.
     * GET /api/mobile/app-version
     */
    public function checkVersion(Request $request)
    {
        $platform = strtolower($request->query('platform', 'android'));
        $clientVersionCode = $request->has('version_code') ? (int) $request->query('version_code') : null;

        $latest = AppVersion::getLatestActive($platform);

        // Jika belum ada versi yang dirilis di database, berikan respons default yang aman
        if (!$latest) {
            $responsePayload = [
                'app_name' => 'TB Care',
                'platform' => $platform,
                'latest_version' => '0.1.0',
                'latest_version_code' => 1,
                'minimum_supported_version_code' => 1,
                'update_available' => false,
                'force_update' => false,
                'download_url' => null,
                'release_notes' => 'Aplikasi TB Care dalam versi stabil.',
                'file_size' => null,
                'sha256' => null,
                'published_at' => null,
            ];

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Versi aplikasi saat ini adalah yang terbaru.',
                'data' => $responsePayload,
                ...$responsePayload,
            ]);
        }

        $latestVersionCode = (int) $latest->version_code;
        $minVersionCode = (int) $latest->min_supported_version_code;

        // Tentukan apakah ada update dan apakah wajib (force update)
        if ($clientVersionCode !== null) {
            $updateAvailable = $latestVersionCode > $clientVersionCode;
            $forceUpdate = ($clientVersionCode < $minVersionCode) || (bool) $latest->is_force_update;
        } else {
            $updateAvailable = true;
            $forceUpdate = (bool) $latest->is_force_update;
        }

        $downloadUrl = $latest->resolved_download_url;

        $responsePayload = [
            'app_name' => 'TB Care',
            'platform' => $latest->platform,
            'latest_version' => $latest->version_name,
            'latest_version_code' => $latestVersionCode,
            'minimum_supported_version_code' => $minVersionCode,
            'update_available' => $updateAvailable,
            'force_update' => $forceUpdate,
            'download_url' => $downloadUrl,
            'release_notes' => $latest->release_notes ?? 'Pembaruan rutin stabilitas dan performa aplikasi.',
            'file_size' => $latest->file_size,
            'sha256' => $latest->sha256_checksum,
            'published_at' => $latest->published_at ? $latest->published_at->toIso8601String() : null,
        ];

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => $updateAvailable ? 'Tersedia versi baru aplikasi TB Care.' : 'Versi aplikasi sudah terbaru.',
            'data' => $responsePayload,
            ...$responsePayload, // Root-level keys untuk kompatibilitas ganda
        ]);
    }

    /**
     * Endpoint Publik: Mengunduh file APK rilis secara aman.
     * GET /api/mobile/app-version/download/{id?}
     */
    public function downloadApk($id = null)
    {
        if ($id) {
            $version = AppVersion::find($id);
        } else {
            $version = AppVersion::getLatestActive('android');
        }

        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'File rilis aplikasi tidak ditemukan.',
            ], 404);
        }

        // 1. Jika file disimpan di disk lokal/public server
        if ($version->apk_path) {
            $disk = Storage::disk('public');
            if ($disk->exists($version->apk_path)) {
                $fullPath = $disk->path($version->apk_path);
                $downloadFilename = 'TBCare-v' . $version->version_name . '.apk';

                return response()->download($fullPath, $downloadFilename, [
                    'Content-Type' => 'application/vnd.android.package-archive',
                    'Content-Disposition' => 'attachment; filename="' . $downloadFilename . '"',
                ]);
            }
        }

        // 2. Jika merupakan URL eksternal (CDN/Cloud/GitHub Releases)
        if ($version->download_url) {
            // Validasi skema HTTPS
            if (!str_starts_with($version->download_url, 'https://') && !str_starts_with($version->download_url, 'http://')) {
                return response()->json([
                    'success' => false,
                    'message' => 'URL unduhan tidak valid.',
                ], 400);
            }

            return redirect()->away($version->download_url);
        }

        return response()->json([
            'success' => false,
            'message' => 'Lokasi file APK belum dikonfigurasi untuk versi ini.',
        ], 404);
    }
}
