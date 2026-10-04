<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PuskesmasController;
use App\Http\Controllers\HealthOfficeController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\EducationalMaterialController;
use App\Http\Controllers\MedicationRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientTreatmentController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\TreatmentTypeController;
use App\Http\Controllers\VillageController;
use App\Http\Controllers\KaderAreaController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\PatientController as AdminPatientController;
use App\Http\Controllers\Admin\ScreeningController as AdminScreeningController;
use App\Http\Controllers\Admin\ExaminationController as AdminExaminationController;
use App\Http\Controllers\Admin\TreatmentController as AdminTreatmentController;
use App\Http\Controllers\Admin\CloseContactController as AdminCloseContactController;
use App\Http\Controllers\Admin\PuskesmasController as AdminPuskesmasController;
use App\Http\Controllers\Admin\RegionController as AdminRegionController;
use App\Http\Controllers\Admin\EducationController as AdminEducationController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\MasterDataController as AdminMasterDataController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/image/{filename}', function ($filename) {
    $path = public_path('images/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});

// Route::get('/', [HomeController::class, 'dashboard'])->name('dash')->middleware('guest');
Route::get('/', [HomeController::class, 'portal'])->name('portal')->middleware('guest');

Route::controller(LoginController::class)->group(function () {
    Route::get('login', 'index')->name('login')->middleware('guest');
    Route::post('login', 'authenticate');
    Route::get('logout', 'logout')->name('logout');
});

Route::controller(ScreeningController::class)->group(function () {
    Route::get('screening', 'index')->name('screening');
    Route::post('screening/process', 'process')->name('process.screening');
});

Route::middleware(['auth'])->group(function () {
    // HomeController
    Route::get('home', [HomeController::class, 'index'])->name('home');

    // MyProfile
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserController::class, 'show'])->name('profile');
        Route::get('{id}/edit', [UserController::class, 'edit'])->name('profile.edit');
    });

    Route::middleware(['checkrole:1-2-3'])->group(function () {
        // UserController
        Route::prefix('user')->group(function () {
            Route::get('{id}/list', [UserController::class, 'list'])->name('user.list');
            Route::get('{id}/add', [UserController::class, 'create'])->name('user.add');
            Route::get('{id}/edit', [UserController::class, 'edit'])->name('user.edit');
            Route::get('{id}/show', [UserController::class, 'show'])->name('user.show');
            Route::match(['post', 'put'], 'save/{id?}', [UserController::class, 'save'])->name('user.save');
            Route::delete('{id}', [UserController::class, 'destroy'])->name('user.delete');
        });

        // PuskesmasController
        Route::prefix('pkm')->group(function () {
            Route::get('/', [PuskesmasController::class, 'index'])->name('pkm');
            Route::get('{id}/edit', [PuskesmasController::class, 'edit'])->name('pkm.edit');
            Route::match(['post', 'put'], 'save/{id?}', [PuskesmasController::class, 'save'])->name('pkm.save');
            Route::delete('{id}', [PuskesmasController::class, 'destroy'])->name('pkm.delete');
        });

        // HealthOfficeController
        Route::prefix('ho')->group(function () {
            Route::get('{id}/edit', [HealthOfficeController::class, 'edit'])->name('ho.edit');
            Route::match(['post', 'put'], 'update/{id?}', [HealthOfficeController::class, 'update'])->name('ho.update');
        });

        // CoordinatorController
        Route::prefix('coord')->group(function () {
            Route::get('{id}/edit', [CoordinatorController::class, 'edit'])->name('coord.edit');
            Route::match(['post', 'put'], 'update/{id?}', [CoordinatorController::class, 'update'])->name('coord.update');
        });

        // PatientController
        Route::get('patients', [PatientController::class, 'index'])->name('patients');
        Route::prefix('patient')->group(function () {
            Route::get('{id}/edit', [PatientController::class, 'edit'])->name('patient.edit');
            Route::match(['post', 'put'], 'update/{id?}', [PatientController::class, 'update'])->name('patient.update');
        });

        // TreatmentTypeController
        Route::get('trtypes', [TreatmentTypeController::class, 'index'])->name('trtypes');
        Route::prefix('trtypes')->group(function () {
            Route::get('{id}/edit', [TreatmentTypeController::class, 'edit'])->name('trtype.edit');
            Route::match(['post', 'put'], 'save/{id?}', [TreatmentTypeController::class, 'save'])->name('trtype.save');
            Route::delete('{id}', [TreatmentTypeController::class, 'destroy'])->name('trtype.delete');
        });

        // PatientTreatmentController
        Route::prefix('treatment')->group(function () {
            Route::get('{id}/edit', [PatientTreatmentController::class, 'edit'])->name('treatment.edit');
            Route::get('{id}/show', [PatientTreatmentController::class, 'show'])->name('treatment.show');
            Route::match(['post', 'put'], 'save/{id?}', [PatientTreatmentController::class, 'save'])->name('treatment.save');
            Route::delete('{id}', [PatientTreatmentController::class, 'destroy'])->name('treatment.delete');
        });

        // EducationalMaterialController
        Route::get('materials', [EducationalMaterialController::class, 'index'])->name('materials');
        Route::prefix('material')->group(function () {
            Route::get('{id}/edit', [EducationalMaterialController::class, 'edit'])->name('material.edit');
            Route::get('{id}/show', [EducationalMaterialController::class, 'show'])->name('material.show');
            Route::match(['post', 'put'], 'save/{id?}', [EducationalMaterialController::class, 'save'])->name('material.save');
            Route::delete('{id}', [EducationalMaterialController::class, 'destroy'])->name('material.delete');
        });

        // VillageController
        Route::get('villages', [VillageController::class, 'index'])->name('village');
        Route::prefix('village')->group(function () {
            Route::get('{id}/edit', [VillageController::class, 'edit'])->name('village.edit');
            Route::match(['post', 'put'], 'save/{id?}', [VillageController::class, 'save'])->name('village.save');
            Route::delete('{id}', [VillageController::class, 'destroy'])->name('village.delete');
        });

        // KaderAreaController
        Route::prefix('kader-area')->group(function () {
            Route::post('save', [KaderAreaController::class, 'store'])->name('kader-area.store');
            Route::delete('{id}', [KaderAreaController::class, 'destroy'])->name('kader-area.delete');
            Route::get('villages/{subdistrictId}', [KaderAreaController::class, 'getVillages'])->name('kader-area.villages');
        });
    });

    Route::middleware(['checkrole:1-2'])->group(function () {
        // PuskesmasController
        Route::prefix('pkm')->group(function () {
            Route::get('/', [PuskesmasController::class, 'index'])->name('pkm');
            Route::get('{id}/edit', [PuskesmasController::class, 'edit'])->name('pkm.edit');
            Route::match(['post', 'put'], 'save/{id?}', [PuskesmasController::class, 'save'])->name('pkm.save');
            Route::delete('{id}', [PuskesmasController::class, 'destroy'])->name('pkm.delete');
        });
    });

    Route::middleware(['checkrole:1-2-3-4'])->group(function () {
        // UpdateProfile
        Route::match(['post', 'put'], 'user/save/{id?}', [UserController::class, 'save'])->name('user.save');
        // MedicationRecordController
        // Route::get('medlogs', [MedicationRecordController::class, 'index'])->name('medlogs');
        // Route::prefix('medlog')->group(function () {
        //     Route::get('{id}/edit', [MedicationRecordController::class, 'edit'])->name('medlog.edit');
        //     Route::get('{id}/show', [MedicationRecordController::class, 'show'])->name('medlog.show');
        //     Route::match(['post', 'put'], 'save/{id?}', [MedicationRecordController::class, 'save'])->name('medlog.save');
        //     Route::delete('{id}', [MedicationRecordController::class, 'destroy'])->name('medlog.delete');
        // });
    });

    Route::middleware(['checkrole:1-2-3-4'])->group(function () {
        // PatientTreatmentController
        Route::get('treatments', [PatientTreatmentController::class, 'index'])->name('treatments');
        Route::get('treatments', [PatientTreatmentController::class, 'index'])->name('treatments');
        Route::match(['get', 'post'], 'take-medicine', [PatientTreatmentController::class, 'takeMedicine'])->name('take.medicine');
    });
});

/*
|--------------------------------------------------------------------------
| TB CARE ADMIN ROUTES (ADMINLTE 3.2.0 REBUILD)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    // 1. Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [AdminDashboardController::class, 'index']);

    // 2. Pengguna (Users)
    Route::resource('users', AdminUserController::class);

    // 3. Data Pasien (Patients)
    Route::resource('patients', AdminPatientController::class);

    // 4. Skrining TB
    Route::get('screenings', [AdminScreeningController::class, 'index'])->name('screenings.index');
    Route::get('screenings/action-needed', function() {
        return redirect()->route('admin.screenings.index', ['status' => 'Perlu Tindak Lanjut']);
    })->name('screenings.action_needed');
    Route::get('screenings/{id}', [AdminScreeningController::class, 'show'])->name('screenings.show');
    Route::patch('screenings/{id}/status', [AdminScreeningController::class, 'updateStatus'])->name('screenings.update_status');
    Route::patch('screenings/{id}/status-hyphen', [AdminScreeningController::class, 'updateStatus'])->name('screenings.update-status');

    // 5. Pemeriksaan Klinis
    Route::resource('examinations', AdminExaminationController::class);

    // 6. Pengobatan & Monitoring
    Route::get('treatments', [AdminTreatmentController::class, 'index'])->name('treatments.index');
    Route::get('treatments/monitoring', [AdminTreatmentController::class, 'monitoring'])->name('treatments.monitoring');
    Route::patch('treatments/{id}/status', [AdminTreatmentController::class, 'updateStatus'])->name('treatments.update_status');
    Route::post('treatments/verify-medicine/{id}', [AdminTreatmentController::class, 'verifyMedication'])->name('treatments.verify-medicine');

    // 7. Kontak Erat
    Route::resource('contacts', AdminCloseContactController::class);

    // 8. Fasilitas Kesehatan / Puskesmas
    Route::resource('puskesmas', AdminPuskesmasController::class);

    // 9. Wilayah
    Route::get('regions', [AdminRegionController::class, 'index'])->name('regions.index');
    Route::post('regions/subdistricts', [AdminRegionController::class, 'storeSubdistrict'])->name('regions.subdistricts.store');
    Route::post('regions/villages', [AdminRegionController::class, 'storeVillage'])->name('regions.villages.store');
    Route::delete('regions/subdistricts/{id}', [AdminRegionController::class, 'destroySubdistrict'])->name('regions.subdistricts.destroy');
    Route::delete('regions/villages/{id}', [AdminRegionController::class, 'destroyVillage'])->name('regions.villages.destroy');
    Route::get('regions/ajax/districts', [AdminRegionController::class, 'getDistricts'])->name('regions.ajax.districts');
    Route::get('regions/ajax/subdistricts', [AdminRegionController::class, 'getSubdistricts'])->name('regions.ajax.subdistricts');
    Route::get('regions/ajax/villages', [AdminRegionController::class, 'getVillages'])->name('regions.ajax.villages');

    // 10. Edukasi
    Route::resource('education', AdminEducationController::class);
    Route::patch('education/{id}/toggle-publish', [AdminEducationController::class, 'togglePublish'])->name('education.toggle-publish');

    // 11. Notifikasi
    Route::resource('notifications', AdminNotificationController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('notifications-consultations', function() {
        return redirect()->route('admin.notifications.index', ['type' => 'Konsultasi']);
    })->name('notifications.consultations');

    // 12. Laporan
    Route::get('reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('reports/print', [AdminReportController::class, 'print'])->name('reports.print');
    Route::get('reports/export', [AdminReportController::class, 'exportCsv'])->name('reports.export');

    // 13. Analitik
    Route::get('analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');

    // 14. Master Data
    Route::get('master', [AdminMasterDataController::class, 'index'])->name('master.index');
    Route::get('master/questions', function() {
        return redirect()->route('admin.master.index', ['tab' => 'questions']);
    })->name('master.questions');
    Route::get('master/treatment-types', function() {
        return redirect()->route('admin.master.index', ['tab' => 'treatments']);
    })->name('master.treatment_types');
    Route::get('master/categories', function() {
        return redirect()->route('admin.master.index', ['tab' => 'questions']);
    })->name('master.categories');
    Route::put('master/questions/{id}', [AdminMasterDataController::class, 'updateQuestion'])->name('master.questions.update');
    Route::post('master/treatments', [AdminMasterDataController::class, 'storeTreatmentType'])->name('master.treatments.store');

    // 15. Log Aktivitas
    Route::get('activity-logs', [AdminActivityLogController::class, 'index'])->name('activity_logs.index');
    Route::get('activity-logs-alias', [AdminActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::post('activity-logs/clear', [AdminActivityLogController::class, 'clearOld'])->name('activity-logs.clear');

    // 16. Pengaturan
    Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::get('settings/profile', function() {
        return redirect()->route('admin.settings.index', ['tab' => 'profile']);
    })->name('settings.profile');
    Route::get('settings/roles', function() {
        return redirect()->route('admin.settings.index', ['tab' => 'roles']);
    })->name('settings.roles');
    Route::get('settings/system', function() {
        return redirect()->route('admin.settings.index', ['tab' => 'system']);
    })->name('settings.system');
    Route::put('settings/profile', [AdminSettingController::class, 'updateProfile'])->name('settings.profile.update');
    Route::post('settings/system', [AdminSettingController::class, 'updateSystem'])->name('settings.system.update');
});
