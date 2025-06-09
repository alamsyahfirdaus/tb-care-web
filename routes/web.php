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

Route::get('/', [HomeController::class, 'dashboard'])->name('dash')->middleware('guest');

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
