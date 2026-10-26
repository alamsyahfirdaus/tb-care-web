<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\ScreeningController;
use App\Http\Controllers\Admin\ExaminationController;
use App\Http\Controllers\Admin\TreatmentController;
use App\Http\Controllers\Admin\CloseContactController;
use App\Http\Controllers\Admin\PuskesmasController;
use App\Http\Controllers\Admin\RegionController;
use App\Http\Controllers\Admin\EducationController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\KaderAreaController;
use App\Http\Controllers\Admin\AppVersionController;

/*
|--------------------------------------------------------------------------
| 1. Route Publik
|--------------------------------------------------------------------------
| Dapat diakses oleh umum (guest) tanpa login.
*/

// Penyajian gambar publik yang aman
Route::get('/image/{filename}', function ($filename) {
    $path = public_path('images/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->name('public.image');

// Portal Beranda Publik
Route::get('/', [HomeController::class, 'portal'])->name('portal')->middleware('guest');

/*
|--------------------------------------------------------------------------
| 2. Route Autentikasi
|--------------------------------------------------------------------------
| Mengatur siklus login dan logout web dashboard.
| Method logout mendukung GET dan POST untuk kompatibilitas tautan & form.
*/
Route::controller(LoginController::class)->group(function () {
    Route::get('login', 'index')->name('login')->middleware('guest');
    Route::post('login', 'authenticate');
    Route::match(['get', 'post'], 'logout', 'logout')->name('logout');
});

/*
|--------------------------------------------------------------------------
| 3. Route Skrining Publik (Fallback / Aliases)
|--------------------------------------------------------------------------
| Alur skrining mandiri dilakukan melalui aplikasi mobile (API).
| Akses web ke URL skrining publik dialihkan secara aman ke modul skrining.
*/
Route::get('screening', function () {
    return redirect()->route('screenings.index');
})->name('screening');

Route::post('screening/process', function () {
    return redirect()->route('screenings.index');
})->name('process.screening');

/*
|--------------------------------------------------------------------------
| 4. Route Redirect Berdasarkan Autentikasi
|--------------------------------------------------------------------------
*/
Route::get('home', [HomeController::class, 'index'])->name('home')->middleware(['auth', 'web.admin']);

/*
|--------------------------------------------------------------------------
| 5. Route Dashboard
|--------------------------------------------------------------------------
| Mengarahkan pengguna terotentikasi (Administrator & Petugas) ke dashboard operasional.
| Dilindungi oleh middleware web.admin (menolak Pasien) dan checkrole:1-3.
*/
Route::get('dashboard', [DashboardController::class, 'index'])
    ->name('dashboard')
    ->middleware(['auth', 'web.admin', 'checkrole:1-3']);

/*
|--------------------------------------------------------------------------
| 6. Route Operasional yang Memerlukan Login (Administrator & Seluruh Petugas)
|--------------------------------------------------------------------------
| Pengelolaan data utama yang dibatasi oleh hak akses dan cakupan wilayah.
| Petugas mencakup Dinkes Provinsi, Dinkes Kota/Kab, PJTB Puskesmas, dan Kader.
*/
Route::middleware(['auth', 'web.admin', 'checkrole:1-3'])->group(function () {
    // A. Pasien
    Route::resource('patients', PatientController::class);

    // B. Skrining TB
    Route::get('screenings', [ScreeningController::class, 'index'])->name('screenings.index');
    Route::get('screenings/action-needed', function () {
        return redirect()->route('screenings.index', ['status' => 'Perlu Tindak Lanjut']);
    })->name('screenings.action_needed');
    Route::get('screenings/{id}', [ScreeningController::class, 'show'])->name('screenings.show');
    Route::patch('screenings/{id}/status', [ScreeningController::class, 'updateStatus'])->name('screenings.update_status');
    Route::delete('screenings/{id}', [ScreeningController::class, 'destroy'])->name('screenings.destroy');

    // C. Pemeriksaan Klinis
    Route::resource('examinations', ExaminationController::class);

    // D. Pengobatan & Pemantauan Minum Obat
    Route::get('treatments', [TreatmentController::class, 'index'])->name('treatments.index');
    Route::get('treatments/monitoring', [TreatmentController::class, 'monitoring'])->name('treatments.monitoring');
    Route::patch('treatments/{id}/status', [TreatmentController::class, 'updateStatus'])->name('treatments.update_status');
    Route::post('treatments/visits', [TreatmentController::class, 'storeVisit'])->name('treatments.store_visit');
    Route::match(['post', 'patch'], 'treatments/verify-medication/{id}', [TreatmentController::class, 'verifyMedication'])->name('treatments.verify_medication');
    Route::delete('treatments/{id}', [TreatmentController::class, 'destroy'])->name('treatments.destroy');

    // E. Kontak Erat
    Route::resource('contacts', CloseContactController::class);

    // F. Pengaturan Profil Pengguna
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::get('settings/profile', function () {
        return redirect()->route('settings.index', ['tab' => 'profile']);
    })->name('settings.profile');
    Route::get('settings/roles', function () {
        return redirect()->route('settings.index', ['tab' => 'roles']);
    })->name('settings.roles');
    Route::get('settings/system', function () {
        return redirect()->route('settings.index', ['tab' => 'system']);
    })->name('settings.system');
    Route::put('settings/profile', [SettingController::class, 'updateProfile'])->name('settings.profile.update');
    Route::post('settings/system', [SettingController::class, 'updateSystem'])->name('settings.system.update');
});

/*
|--------------------------------------------------------------------------
| 7. Route Manajemen Petugas (Role 1-3: Administrator & Petugas)
|--------------------------------------------------------------------------
| Modul fasilitas kesehatan, edukasi, laporan, analitik, dan binaan kader.
*/
Route::middleware(['auth', 'web.admin', 'checkrole:1-3'])->group(function () {
    // Fasilitas Kesehatan / Puskesmas
    Route::resource('puskesmas', PuskesmasController::class);

    // Materi Edukasi
    Route::resource('education', EducationController::class);
    Route::patch('education/{id}/toggle-publish', [EducationController::class, 'togglePublish'])->name('education.toggle-publish');

    // Notifikasi Sistem & Konsultasi
    Route::resource('notifications', NotificationController::class);
    Route::get('notifications-consultations', function () {
        return redirect()->route('notifications.index', ['type' => 'Konsultasi']);
    })->name('notifications.consultations');

    // Laporan & Ekspor Data
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/print', [ReportController::class, 'print'])->name('reports.print');
    Route::get('reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');

    // Analitik
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Wilayah Binaan Kader (Kader Areas - Dikelola oleh Admin & PJTB Puskesmas)
    Route::post('kader-areas', [KaderAreaController::class, 'store'])->name('kader-areas.store');
    Route::delete('kader-areas/{id}', [KaderAreaController::class, 'destroy'])->name('kader-areas.destroy');
    Route::get('kader-areas/villages/{subdistrictId}', [KaderAreaController::class, 'getVillages'])->name('kader-areas.villages');
});

/*
|--------------------------------------------------------------------------
| 8. Route Khusus Administrator (Role 1: Super Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'web.admin', 'checkrole:1'])->group(function () {
    // Manajemen Pengguna
    Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle_status');
    Route::resource('users', UserController::class);

    // Master Data
    Route::get('master', [MasterDataController::class, 'index'])->name('master.index');
    Route::get('master/questions', function () {
        return redirect()->route('master.index', ['tab' => 'questions']);
    })->name('master.questions');
    Route::get('master/treatment-types', function () {
        return redirect()->route('master.index', ['tab' => 'treatments']);
    })->name('master.treatment_types');
    Route::get('master/categories', function () {
        return redirect()->route('master.index', ['tab' => 'questions']);
    })->name('master.categories');
    Route::put('master/questions/{id}', [MasterDataController::class, 'updateQuestion'])->name('master.questions.update');
    Route::post('master/treatments', [MasterDataController::class, 'storeTreatmentType'])->name('master.treatments.store');
    Route::put('master/treatments/{id}', [MasterDataController::class, 'updateTreatmentType'])->name('master.treatments.update');
    Route::delete('master/treatments/{id}', [MasterDataController::class, 'destroyTreatmentType'])->name('master.treatments.destroy');

    // Log Aktivitas
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');
    Route::post('activity-logs/clear', [ActivityLogController::class, 'clearOld'])->name('activity_logs.clear');

    // Master Wilayah (Kelola Kecamatan & Desa)
    Route::get('regions', [RegionController::class, 'index'])->name('regions.index');
    Route::post('regions/subdistricts', [RegionController::class, 'storeSubdistrict'])->name('regions.subdistricts.store');
    Route::put('regions/subdistricts/{id}', [RegionController::class, 'updateSubdistrict'])->name('regions.subdistricts.update');
    Route::delete('regions/subdistricts/{id}', [RegionController::class, 'destroySubdistrict'])->name('regions.subdistricts.destroy');
    Route::post('regions/villages', [RegionController::class, 'storeVillage'])->name('regions.villages.store');
    Route::put('regions/villages/{id}', [RegionController::class, 'updateVillage'])->name('regions.villages.update');
    Route::delete('regions/villages/{id}', [RegionController::class, 'destroyVillage'])->name('regions.villages.destroy');

    // Manajemen Versi Aplikasi Mobile
    Route::post('app-versions/{id}/toggle-active', [AppVersionController::class, 'toggleActive'])->name('app-versions.toggle_active');
    Route::resource('app-versions', AppVersionController::class);
});

/*
|--------------------------------------------------------------------------
| 9. Route Tindakan & AJAX Pendukung (Role 1-3)
|--------------------------------------------------------------------------
| Digunakan untuk kebutuhan cascading dropdown di formulir pasien, skrining, dll.
*/
Route::middleware(['auth', 'web.admin', 'checkrole:1-3'])->prefix('regions/ajax')->name('regions.ajax.')->group(function () {
    Route::get('districts', [RegionController::class, 'getDistricts'])->name('districts');
    Route::get('subdistricts', [RegionController::class, 'getSubdistricts'])->name('subdistricts');
    Route::get('villages', [RegionController::class, 'getVillages'])->name('villages');
});

/*
|--------------------------------------------------------------------------
| 10. Redirect Kompatibilitas URL Lama (/admin/*)
|--------------------------------------------------------------------------
| Mengarahkan URL lama ber-prefix /admin ke URL kanonis baru untuk mencegah 404 pada bookmark.
*/
Route::prefix('admin')->middleware(['auth', 'web.admin'])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('dashboard', fn () => redirect()->route('dashboard'));
    Route::any('{any}', function ($any) {
        $target = url($any);
        $query = request()->getQueryString();
        if ($query) {
            $target .= '?' . $query;
        }
        return redirect($target);
    })->where('any', '.*');
});
