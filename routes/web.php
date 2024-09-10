<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PuskesmasController;
use App\Http\Controllers\HealthOfficeController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\StatusPengobatanController;

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

Route::get('/', function () {
    // return view('welcome');
    return redirect('login');
});

Route::controller(LoginController::class)->group(function () {
    Route::get('login', 'index')->name('login')->middleware('guest');
    Route::post('login', 'authenticate');
    Route::get('logout', 'logout')->name('logout');
});

Route::middleware(['auth', 'checkUserType'])->group(function () {
    // HomeController
    Route::get('home', [HomeController::class, 'index'])->name('home');
    // UserController
    Route::get('user/{id}/list', [UserController::class, 'list'])->name('user.list');
    Route::get('user/{id}/add', [UserController::class, 'create'])->name('user.add');
    Route::get('user/{id}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::get('user/{id}/show', [UserController::class, 'show'])->name('user.show');
    Route::match(['post', 'put'], 'user/save/{id?}', [UserController::class, 'save'])->name('user.save');
    Route::delete('users/{id}', [UserController::class, 'destroy'])->name('user.delete');
    // UserDetail
    Route::put('user/treatment/{id}', [UserController::class, 'updateTreatment'])->name('user.treatment');

    // PuskesmasController
    Route::get('pkm', [PuskesmasController::class, 'index'])->name('pkm');
    Route::get('pkm/add', [PuskesmasController::class, 'create'])->name('pkm.add');
    Route::get('pkm/{id}/edit', [PuskesmasController::class, 'edit'])->name('pkm.edit');
    Route::match(['post', 'put'], 'pkm/save/{id?}', [PuskesmasController::class, 'save'])->name('pkm.save');
    Route::delete('pkm/{id}', [PuskesmasController::class, 'destroy'])->name('pkm.delete');
    // HealthOfficeController
    Route::get('ho/{id}/edit', [HealthOfficeController::class, 'edit'])->name('ho.edit');
    Route::match(['post', 'put'], 'healthoffice/update/{id?}', [HealthOfficeController::class, 'update'])->name('ho.update');
    // CoordinatorController
    Route::get('coord/{id}/edit', [CoordinatorController::class, 'edit'])->name('coord.edit');
    Route::match(['post', 'put'], 'coordinator/update/{id?}', [CoordinatorController::class, 'update'])->name('coord.update');
    // // PatientController
    Route::get('patient/{id}/edit', [PatientController::class, 'edit'])->name('patient.edit');
    Route::match(['post', 'put'], 'patient/update/{id?}', [PatientController::class, 'update'])->name('patient.update');
    // StatusPengobatanController
    Route::get('status', [StatusPengobatanController::class, 'index'])->name('status');
    Route::match(['post', 'put'], 'status/save', [StatusPengobatanController::class, 'save'])->name('status.save');
    Route::delete('status/{id}', [StatusPengobatanController::class, 'destroy'])->name('status.delete');
});
