<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PuskesmasController;
use App\Http\Controllers\HealthOfficeController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PatientTreatmentController;
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

Route::get('/', [LoginController::class, 'index'])->name('login')->middleware('guest');

Route::controller(LoginController::class)->group(function () {
    Route::get('login', 'index')->name('login')->middleware('guest');
    Route::post('login', 'authenticate');
    Route::get('logout', 'logout')->name('logout');
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

        //PatientTreatmentController
        Route::get('treatments', [PatientTreatmentController::class, 'index'])->name('treatments');
        Route::prefix('treatment')->group(function () {
            Route::get('{id}/edit', [PatientTreatmentController::class, 'edit'])->name('treatment.edit');
            Route::get('{id}/show', [PatientTreatmentController::class, 'show'])->name('treatment.show');
            Route::match(['post', 'put'], 'save/{id?}', [PatientTreatmentController::class, 'save'])->name('treatment.save');
            Route::delete('{id}', [PatientTreatmentController::class, 'destroy'])->name('treatment.delete');
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
    });
});
