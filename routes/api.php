<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\BulkReminderController;
use App\Http\Controllers\Api\EducationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\PuskesmasController;
use App\Http\Controllers\Api\ScreeningController;
use App\Http\Controllers\Api\OfficerController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SubdistrictController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\TreatmentVisitController;
use App\Http\Controllers\Api\CloseContactController;
use App\Http\Controllers\Api\AppVersionApiController;
use App\Http\Controllers\Api\NotificationController;

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

Route::middleware(['auth:sanctum', 'active'])->get('/image-bytes/{filename}', function ($filename) {
    $path = public_path('images/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
});

// Autentikasi Awal
Route::post('/login', [AuthController::class, 'login']);

// Registrasi User Baru (Pasien)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/roles', [AuthController::class, 'getOfficerRoles']);

// Referensi Wilayah (tanpa login)
Route::get('/puskesmas', [PuskesmasController::class, 'index']);
Route::get('/provinces', [RegionController::class, 'provinces']);
Route::get('/provinces/{id}/districts', [RegionController::class, 'districtsByProvince']);
Route::get('/districts', [RegionController::class, 'districts']);
Route::get('/districts/{id}/subdistricts', [RegionController::class, 'subdistrictsByDistrict']);
Route::get('/subdistricts', [SubdistrictController::class, 'index']);
Route::get('/subdistricts/{id}/villages', [SubdistrictController::class, 'getVillages']);
Route::get('/villages/search', [RegionController::class, 'searchVillages']);
Route::get('/villages', [RegionController::class, 'villages']);

// Skrining
Route::prefix('screening')->group(function () {
    // Ambil daftar kategori usia (misalnya: <15 tahun, ≥15 tahun)
    Route::get('/categories', [ScreeningController::class, 'getAgeCategories']);
    // Ambil daftar pertanyaan berdasarkan kategori usia
    Route::post('/questions', [ScreeningController::class, 'getQuestions']);
    // Kirim jawaban untuk proses skrining
    Route::post('/submit', [ScreeningController::class, 'submitAnswers']);
    // Riwayat skrining mandiri / kontak serumah untuk pengguna
    Route::get('/history', [ScreeningController::class, 'history']);
    // Detail data skrining
    Route::get('/{id}', [ScreeningController::class, 'show']);
});

// Pemeriksaan & Unduhan Versi Aplikasi Mobile (Publik)
Route::get('/mobile/app-version', [AppVersionApiController::class, 'checkVersion']);
Route::get('/mobile/app-version/download/{id?}', [AppVersionApiController::class, 'downloadApk']);

// Route yang membutuhkan autentikasi (sanctum & akun aktif)
Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // CEK TOKEN / AUTO LOGIN
    Route::get('/me', [AuthController::class, 'me']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profil Pengguna
    Route::prefix('profile')->group(function () {
        Route::get('/show', [ProfileController::class, 'show']);      // Lihat profil
        Route::put('/update', [ProfileController::class, 'update']);  // Perbarui profil
        Route::post('/fcm-token', [ProfileController::class, 'updateFcmToken']); // Update token FCM
    });

    // Wilayah Binaan Kader
    Route::get('/officer/kader-areas', [OfficerController::class, 'getKaderAreas']);

    // Pengobatan Pasien
    Route::prefix('treatments')->group(function () {
        Route::get('/medications', [TreatmentController::class, 'patientMedications']); // Daftar obat pasien
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
    Route::get('/patient/visits', [TreatmentVisitController::class, 'patientVisits']); // Daftar jadwal kunjungan pasien terautentikasi
    Route::prefix('visits')->group(function () {
        Route::get('/{id}', [TreatmentVisitController::class, 'show']);                      // Detail kunjungan
        Route::match(['post', 'put'], '/store', [TreatmentVisitController::class, 'store']); // Simpan / update kunjungan
        Route::match(['post', 'patch'], '/{id}/cancel', [TreatmentVisitController::class, 'cancelVisit']); // Batalkan jadwal kunjungan
        Route::delete('/{id}/delete', [TreatmentVisitController::class, 'destroy']);         // Hapus kunjungan
    });

    // Materi Edukasi
    Route::prefix('education')->group(function () {
        Route::get('/', [EducationController::class, 'index']);                  // Daftar edukasi
        Route::get('/{id}/show', [EducationController::class, 'show']);          // Detail edukasi
        Route::match(['post', 'put'], '/store', [EducationController::class, 'store']); // Simpan / update edukasi
        Route::delete('/{id}/delete', [EducationController::class, 'destroy']);  // Hapus edukasi
        Route::delete('/{id}', [EducationController::class, 'destroy']);         // RESTful alias hapus edukasi
        Route::put('/{id}/publish', [EducationController::class, 'togglePublish']); // Ubah status publikasi
    });

    // Backward compatibility aliases mapping /consultations to Live Chat ConversationController
    Route::get('/consultations', [ConversationController::class, 'index']);
    Route::get('/consultations/recipients', [ConversationController::class, 'getRecipients']);

    // Live Chat (Percakapan Privat Dua Arah)
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);
        Route::get('/recipients', [ConversationController::class, 'getRecipients']);
        Route::get('/unread-count', [ConversationController::class, 'unreadCount']);
        Route::post('/', [ConversationController::class, 'store']);
        Route::post('/bulk-hide', [ConversationController::class, 'bulkHide']);
        Route::post('/messages', function (\Illuminate\Http\Request $request) {
            return app(ConversationController::class)->sendMessage(0, $request);
        });
        Route::get('/{id}', [ConversationController::class, 'show']);
        Route::get('/{id}/messages', [ConversationController::class, 'getMessages']);
        Route::post('/{id}/messages', [ConversationController::class, 'sendMessage']);
        Route::post('/{id}/read', [ConversationController::class, 'markAsRead']);
        Route::delete('/{id}', [ConversationController::class, 'destroy']);
        Route::put('/messages/{id}', [ConversationController::class, 'updateMessage']);
        Route::delete('/messages/{id}', [ConversationController::class, 'deleteMessage']);
    });

    // Direct message edit and delete endpoints (Section 9)
    Route::put('/messages/{id}', [ConversationController::class, 'updateMessage']);
    Route::delete('/messages/{id}', [ConversationController::class, 'deleteMessage']);

    // Pengingat Minum Obat Massal (Bulk Medication Reminder)
    Route::prefix('reminders')->group(function () {
        Route::get('/recipients', [BulkReminderController::class, 'getRecipients']);
        Route::post('/bulk-send', [BulkReminderController::class, 'sendBulk']);
    });

    // In-App Notifications & Pusat Notifikasi
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Modul & Dashboard Khusus Kader TB
    Route::prefix('kader')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\KaderController::class, 'dashboard']);
        Route::get('/patients', [\App\Http\Controllers\Api\KaderController::class, 'dashboard']);
        Route::get('/reminders', [\App\Http\Controllers\Api\KaderController::class, 'reminders']);
        Route::post('/reminders/send', [\App\Http\Controllers\Api\KaderController::class, 'sendReminder']);
        Route::get('/patients/{id}/history', [\App\Http\Controllers\Api\KaderController::class, 'patientHistory']);
        Route::post('/patients/{id}/verify-medication', [\App\Http\Controllers\Api\KaderController::class, 'verifyMedication']);
    });

    // Dashboard / Beranda Pasien Terpadu
    Route::get('/patient/home', [PatientController::class, 'home']);
    Route::get('/patient/medications', [TreatmentController::class, 'patientMedications']);

    // Data Pasien TB
    Route::prefix('patients')->group(function () {
        Route::get('/home', [PatientController::class, 'home']);
        Route::get('/{id}/home', [PatientController::class, 'home']);
        Route::match(['get', 'post'], '/', [PatientController::class, 'index']);           // Pencarian atau daftar pasien
        Route::match(['post', 'put'], '/store', [PatientController::class, 'store']);      // Simpan / update data pasien
        Route::get('/{id}/show', [PatientController::class, 'show'])->whereNumber('id');                      // Detail pasien
        Route::get('/{id}', [PatientController::class, 'show'])->whereNumber('id');
        Route::delete('/{id}/delete', [PatientController::class, 'destroy'])->whereNumber('id');              // Hapus data pasien
        Route::delete('/{id}', [PatientController::class, 'destroy'])->whereNumber('id');
        Route::get('/{id}/treatments', [PatientController::class, 'treatmentHistory']);    // Riwayat pengobatan pasien
        Route::get('/adherence', [PatientController::class, 'treatmentAdherence']);        // Tingkat kepatuhan minum obat
        Route::get('/{id}/medication-schedule', [PatientController::class, 'getMedicationSchedule']); // Jadwal minum obat
        Route::match(['post', 'put'], '/{id}/medication-schedule', [PatientController::class, 'saveMedicationSchedule']); // Simpan/update jadwal minum obat
        Route::post('/{id}/medication-schedule/store', [PatientController::class, 'storeMedicationSchedule']);
        Route::match(['post', 'put'], '/medication-schedule', [PatientController::class, 'saveMedicationSchedule']);
    });

    // Kontak Erat / Anggota Serumah Pasien
    Route::prefix('contacts')->group(function () {
        Route::get('/count', [CloseContactController::class, 'count']);
        Route::get('/', [CloseContactController::class, 'index']);
        Route::post('/', [CloseContactController::class, 'store']);
        Route::get('/{id}', [CloseContactController::class, 'show']);
        Route::match(['put', 'post'], '/{id}', [CloseContactController::class, 'update']);
        Route::delete('/{id}', [CloseContactController::class, 'destroy']);
    });
});

// Endpoint akses gambar publik (dapat diakses dengan atau tanpa auth sanctum)
Route::get('/image/{filename}', function ($filename) {
    // 1. Cek di public/images (lokasi utama upload bukti minum obat & edukasi)
    $path = public_path('images/' . $filename);
    if (file_exists($path)) {
        return response()->file($path, [
            'Access-Control-Allow-Origin' => '*'
        ]);
    }

    // 2. Fallback cek di storage/app/public/education
    $storageEduPath = storage_path('app/public/education/' . $filename);
    if (file_exists($storageEduPath)) {
        return response()->file($storageEduPath, [
            'Access-Control-Allow-Origin' => '*'
        ]);
    }

    // 3. Fallback cek di storage/app/public
    $storagePath = storage_path('app/public/' . $filename);
    if (file_exists($storagePath)) {
        return response()->file($storagePath, [
            'Access-Control-Allow-Origin' => '*'
        ]);
    }

    abort(404);
});
