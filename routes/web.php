<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CounselorPanelController as C;
use App\Http\Controllers\CounselingReportController;
use App\Http\Controllers\CompanionChatController;
use App\Http\Controllers\N8nTriageWebhookController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StudentController as S;
use App\Http\Controllers\TriageChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/konselor', [PublicController::class, 'counselors'])->name('counselors.index');
Route::get('/konselor/{profile}', [PublicController::class, 'counselor'])->whereNumber('profile')->name('counselors.show');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login']);
    Route::get('/daftar/mahasiswa', [AuthController::class, 'showRegisterStudent'])->name('register.student');
    Route::post('/daftar/mahasiswa', [AuthController::class, 'registerStudent']);
    Route::get('/daftar/konselor', [AuthController::class, 'showRegisterCounselor'])->name('register.counselor');
    Route::post('/daftar/konselor', [AuthController::class, 'registerCounselor']);
});
Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// n8n webhook (public, validated via secret header)
Route::post('/webhook/n8n/triage', [N8nTriageWebhookController::class, 'receive'])->name('webhook.n8n.triage');
Route::post('/webhook/n8n/counseling-report', [CounselingReportController::class, 'receive'])->name('webhook.n8n.counseling-report');

/* Mahasiswa */
Route::middleware(['auth', 'role:mahasiswa'])->prefix('mahasiswa')->name('student.')->group(function () {
    Route::get('/', [S::class, 'dashboard'])->name('dashboard');

    // Teman bicara AI untuk mahasiswa yang sudah masuk
    Route::get('/teman-bicara', [CompanionChatController::class, 'index'])->name('companion.index');
    Route::post('/teman-bicara', [CompanionChatController::class, 'send'])->middleware('throttle:8,1')->name('companion.send');

    // Triage kesehatan mental (pre-booking)
    Route::get('/triage', [TriageChatController::class, 'index'])->name('triage.index');
    Route::post('/triage', [TriageChatController::class, 'send'])->middleware('throttle:8,1')->name('triage.send');
    Route::post('/triage/calculate', [TriageChatController::class, 'calculate'])->name('triage.calculate');
    Route::post('/triage/complete', [TriageChatController::class, 'complete'])->name('triage.complete');
    Route::post('/triage/result', [TriageChatController::class, 'result'])->name('triage.result');

    // Chat dengan konselor (hanya setelah booking dikonfirmasi)
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/{booking}', [S::class, 'chatIndex'])->name('index');
        Route::post('/{booking}', [S::class, 'chatSend'])->name('send');
    });

    Route::post('/booking/{booking}/batal', [S::class, 'cancel'])->name('cancel');
    Route::post('/booking/{booking}/rating', [S::class, 'rate'])->name('rate');

    Route::prefix('booking')->name('booking.')->group(function () {
        Route::get('/mulai', [S::class, 'start'])->name('start');
        Route::get('/data', [S::class, 'intake'])->name('intake');
        Route::post('/data', [S::class, 'saveIntake'])->name('intake.save');
        Route::get('/konselor', [S::class, 'counselors'])->name('counselors');
        Route::post('/konselor', [S::class, 'pickCounselor'])->name('counselors.pick');
        Route::get('/jadwal', [S::class, 'schedulePage'])->name('schedule');
        Route::post('/jadwal', [S::class, 'store'])->name('store');
    });
});

/* Konselor */
Route::middleware(['auth', 'role:konselor'])->prefix('panel-konselor')->name('counselor.')->group(function () {
    Route::get('/', [C::class, 'dashboard'])->name('dashboard');
    Route::get('/booking/{booking}', [C::class, 'show'])->name('booking');
    Route::post('/booking/{booking}/konfirmasi', [C::class, 'confirm'])->name('confirm');
    Route::post('/booking/{booking}/tolak', [C::class, 'reject'])->name('reject');
    Route::post('/booking/{booking}/selesai', [C::class, 'complete'])->name('complete');
    Route::post('/booking/{booking}/rekap', [CounselingReportController::class, 'requestReport'])->name('report.request');
    Route::get('/rekap/{report}/status', [CounselingReportController::class, 'status'])->name('report.status');
    Route::get('/rekap/{report}/unduh', [CounselingReportController::class, 'download'])->name('report.download');

    // Chat dengan mahasiswa (hanya setelah booking dikonfirmasi)
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/{booking}', [C::class, 'chatIndex'])->name('index');
        Route::post('/{booking}', [C::class, 'chatSend'])->name('send');
    });

    Route::get('/jadwal', [C::class, 'availability'])->name('availability');
    Route::post('/jadwal', [C::class, 'addSlot'])->name('slot.add');
    Route::delete('/jadwal/{slot}', [C::class, 'removeSlot'])->name('slot.remove');

    Route::get('/profil', [C::class, 'profile'])->name('profile');
    Route::put('/profil', [C::class, 'updateProfile'])->name('profile.update');
    Route::post('/sertifikasi', [C::class, 'addCertification'])->name('cert.add');
    Route::delete('/sertifikasi/{id}', [C::class, 'removeCertification'])->name('cert.remove');
});

/* Admin */
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/konselor', [AdminController::class, 'counselors'])->name('counselors');
    Route::post('/konselor/{profile}/status', [AdminController::class, 'setCounselorStatus'])->name('counselors.status');
    Route::get('/pengguna', [AdminController::class, 'users'])->name('users');
    Route::post('/pengguna/{user}/aktif', [AdminController::class, 'toggleUser'])->name('users.toggle');
    Route::get('/tipe-masalah', [AdminController::class, 'problemTypes'])->name('types');
    Route::post('/tipe-masalah', [AdminController::class, 'storeProblemType'])->name('types.store');
    Route::delete('/tipe-masalah/{type}', [AdminController::class, 'destroyProblemType'])->name('types.destroy');
    Route::get('/booking', [AdminController::class, 'bookings'])->name('bookings');
});
