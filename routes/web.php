<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PuskesmasController;
use App\Http\Controllers\PjtbController;
use App\Http\Controllers\PasienController;
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

Route::middleware('auth')->group(function () {
    // HomeController
    Route::get('home', [HomeController::class, 'index'])->name('home');
    // UserController
    Route::get('user/{id}/list', [UserController::class, 'list'])->name('user.list');
    Route::get('user/{id}//add', [UserController::class, 'create'])->name('user.add');

    // Route::get('users', [UserController::class, 'index'])->name('users');
    // Route::get('user/add', [UserController::class, 'create'])->name('user.add');
    Route::get('user/{id}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::match(['post', 'put'], 'user/save/{id?}', [UserController::class, 'save'])->name('user.save');
    Route::delete('users/{id}', [UserController::class, 'destroy'])->name('user.delete');
    Route::get('user/{id}/profile', [UserController::class, 'show'])->name('user.show');
    // PuskesmasController
    Route::get('pkm', [PuskesmasController::class, 'index'])->name('pkm');
    Route::get('pkm/add', [PuskesmasController::class, 'create'])->name('pkm.add');
    Route::get('pkm/{id}/edit', [PuskesmasController::class, 'edit'])->name('pkm.edit');
    Route::match(['post', 'put'], 'pkm/save/{id?}', [PuskesmasController::class, 'save'])->name('pkm.save');
    Route::delete('pkm/{id}', [PuskesmasController::class, 'destroy'])->name('pkm.delete');
    //ChangeOption
    Route::post('get-address', [PuskesmasController::class, 'getAddress'])->name('pkm.address');
    Route::post('get-region', [PuskesmasController::class, 'getRegion'])->name('pkm.region');
    // PjtbController
    Route::get('pjtb', [PjtbController::class, 'index'])->name('pjtb');
    Route::get('pjtb/add', [PjtbController::class, 'create'])->name('pjtb.add');
    Route::get('pjtb/{id}/edit', [PjtbController::class, 'edit'])->name('pjtb.edit');
    Route::get('pjtb/{id}/show', [PjtbController::class, 'show'])->name('pjtb.show');
    Route::match(['post', 'put'], 'pjtb/save/{id?}', [PjtbController::class, 'save'])->name('pjtb.save');
    Route::delete('pjtb/{id}', [PjtbController::class, 'destroy'])->name('pjtb.delete');
    // PasienController
    Route::get('patient', [PasienController::class, 'index'])->name('patient');
    Route::get('patient', [PasienController::class, 'index'])->name('patient');
    Route::get('patient/add', [PasienController::class, 'create'])->name('patient.add');
    Route::get('patient/{id}/edit', [PasienController::class, 'edit'])->name('patient.edit');
    Route::get('patient/{id}/show', [PasienController::class, 'show'])->name('patient.show');
    Route::match(['post', 'put'], 'patient/save/{id?}', [PasienController::class, 'save'])->name('patient.save');
    Route::delete('patient/{id}', [PasienController::class, 'destroy'])->name('patient.delete');
    // StatusPengobatanController
    Route::get('status', [StatusPengobatanController::class, 'index'])->name('status');
    Route::match(['post', 'put'], 'status/save', [StatusPengobatanController::class, 'save'])->name('status.save');
    Route::delete('status/{id}', [StatusPengobatanController::class, 'destroy'])->name('status.delete');
});
