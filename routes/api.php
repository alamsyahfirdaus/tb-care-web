<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\EducationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PuskesmasController;
use App\Http\Controllers\Api\SubdistrictController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\TreatmentVisitController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Autentikasi Awal
Route::post('/login', [AuthController::class, 'login']);

// Registrasi User Baru (Pasien & Petugas)
Route::prefix('register')->group(function () {
    Route::post('/patient', [AuthController::class, 'registerPatient']);   // Registrasi pasien TB
    Route::post('/officer', [AuthController::class, 'registerOfficer']);   // Registrasi petugas (PJTB / Kader)
    Route::get('/roles', [AuthController::class, 'getOfficerRoles']);      // Ambil daftar peran untuk petugas
});

// Referensi Wilayah (tanpa login)
Route::get('/puskesmas', [PuskesmasController::class, 'index']);
Route::get('/subdistricts', [SubdistrictController::class, 'index']);

// Route yang membutuhkan autentikasi (sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profil Pengguna
    Route::prefix('profile')->group(function () {
        Route::get('/show', [ProfileController::class, 'show']);      // Lihat profil
        Route::put('/update', [ProfileController::class, 'update']);  // Perbarui profil
    });

    // Pengobatan Pasien
    Route::prefix('treatments')->group(function () {
        Route::match(['post', 'put'], '/store', [TreatmentController::class, 'store']); // Simpan/update pengobatan
        Route::get('/{id}/show', [TreatmentController::class, 'show']);                // Detail pengobatan
        Route::delete('/{id}/delete', [TreatmentController::class, 'destroy']);        // Hapus pengobatan
        Route::get('/types', [TreatmentController::class, 'treatmentTypeOption']);     // Ambil jenis pengobatan
        Route::match(['post', 'put'], '/status', [TreatmentController::class, 'updateTreatmentStatus']); // Update status
        Route::post('/proof', [TreatmentController::class, 'submitMedicationProof']);  // Upload bukti minum obat
        Route::get('/{id}/history', [TreatmentController::class, 'medicationHistory']); // Riwayat minum obat
        Route::get('/{id}/visits', [TreatmentController::class, 'getVisitsByTreatment']); // Daftar kunjungan dari pengobatan
        Route::put('/verify', [TreatmentController::class, 'verifyMedicationProof']); // Verifikasi bukti minum obat
    });

    // Kunjungan Pasien (Home Visit atau Faskes)
    Route::prefix('visits')->group(function () {
        Route::match(['post', 'put'], '/store', [TreatmentVisitController::class, 'store']); // Simpan / update kunjungan
        Route::delete('/{id}/delete', [TreatmentVisitController::class, 'destroy']);         // Hapus kunjungan
    });

    // Materi Edukasi
    Route::prefix('education')->group(function () {
        Route::get('/', [EducationController::class, 'index']);                  // Daftar edukasi
        Route::get('/{id}/show', [EducationController::class, 'show']);          // Detail edukasi
        Route::match(['post', 'put'], '/store', [EducationController::class, 'store']); // Simpan / update edukasi
        Route::delete('/{id}/delete', [EducationController::class, 'destroy']);  // Hapus edukasi
        Route::put('/{id}/publish', [EducationController::class, 'togglePublish']); // Ubah status publikasi
    });

    // Konsultasi dan Balasan
    Route::prefix('consultations')->group(function () {
        Route::get('/', [ConsultationController::class, 'index']);                      // Daftar konsultasi
        Route::match(['post', 'put'], '/store', [ConsultationController::class, 'store']); // Simpan / update konsultasi
        Route::delete('/{id}/delete', [ConsultationController::class, 'destroy']);      // Hapus konsultasi
        Route::match(['post', 'put'], '/reply', [ConsultationController::class, 'saveReply']); // Simpan / update balasan
        Route::delete('/{id}/reply', [ConsultationController::class, 'deleteReply']);   // Hapus balasan
        Route::get('/recipients', [ConsultationController::class, 'getRecipients']); // Ambil daftar penerima untuk konsultasi
    });

    // Data Pasien TB
    Route::prefix('patients')->group(function () {
        Route::match(['get', 'post'], '/', [PatientController::class, 'index']);           // Pencarian atau daftar pasien
        Route::match(['post', 'put'], '/store', [PatientController::class, 'store']);      // Simpan / update data pasien
        Route::get('/{id}/show', [PatientController::class, 'show']);                      // Detail pasien
        Route::delete('/{id}/delete', [PatientController::class, 'destroy']);              // Hapus data pasien
        Route::get('/{id}/treatments', [PatientController::class, 'treatmentHistory']);    // Riwayat pengobatan pasien
        Route::get('/adherence', [PatientController::class, 'treatmentAdherence']);        // Tingkat kepatuhan minum obat
    });

    // (Optional) Skrining TB — aktifkan jika diperlukan
    // Route::prefix('screening')->group(function () {
    //     Route::get('/questions', [ScreeningController::class, 'questions']);  // Pertanyaan skrining
    //     Route::post('/submit', [ScreeningController::class, 'submit']);      // Submit jawaban skrining
    // });
});
