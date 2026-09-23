<?php

use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\CattleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'landing'])->name('landing');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/verify-otp', [OtpVerificationController::class, 'notice'])->name('otp.notice');
Route::post('/verify-otp', [OtpVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('otp.verify');
Route::post('/verify-otp/resend', [OtpVerificationController::class, 'resend'])->middleware('throttle:5,1')->name('otp.resend');
Route::get('/otp', fn () => redirect()->route('otp.notice'));

$cattle = function () {
    Route::get('search', [ModuleController::class, 'search'])->name('search');
    Route::get('notifications', [ModuleController::class, 'notifications'])->name('notifications.index');
    Route::post('notifications/read', [ModuleController::class, 'markNotifications'])->name('notifications.read');
    Route::get('profil', [ModuleController::class, 'profile'])->name('profile.show');
    Route::put('profil', [ModuleController::class, 'updateProfile'])->name('profile.save');
    Route::put('profil/password', [ModuleController::class, 'updatePassword'])->name('profile.password');
    Route::get('pengaturan', [ModuleController::class, 'settings'])->name('settings');
    Route::get('pemeriksaan-ai', [ModuleController::class, 'examinations'])->name('examinations.index');
    Route::get('kesehatan', [ModuleController::class, 'health'])->name('health.index');
    Route::get('vaksinasi', [ModuleController::class, 'vaccinations'])->name('vaccinations.index');
    Route::post('vaksinasi/{schedule}/complete', [ModuleController::class, 'completeSchedule'])->name('vaccinations.complete');
    Route::get('pakan', [ModuleController::class, 'feeds'])->name('feeds.index');
    Route::get('reproduksi', [ModuleController::class, 'reproduction'])->name('reproduction.index');
    Route::get('laporan', [ModuleController::class, 'reports'])->name('reports.index');
    Route::get('laporan/{cattle}/pdf', [ModuleController::class, 'reportPdf'])->name('reports.pdf');
    Route::get('cattle/{cattle}/laporan.pdf', [CattleController::class, 'reportPdf'])->name('cattle.report.pdf');
    Route::get('bobot', [ModuleController::class, 'weights'])->name('weights.index');
    Route::get('bcs', [ModuleController::class, 'bcs'])->name('bcs.index');

    Route::resource('cattle', CattleController::class);
    Route::post('cattle/{cattle}/ai/weight', [CattleController::class, 'scanWeight'])->name('cattle.ai.weight');
    Route::post('cattle/{cattle}/ai/lumpy', [CattleController::class, 'scanLumpy'])->name('cattle.ai.lumpy');
    Route::post('cattle/{cattle}/ai/combined', [CattleController::class, 'scanCombined'])->name('cattle.ai.combined');
    Route::post('cattle/{cattle}/ai/health', [CattleController::class, 'saveLumpyHealth'])->name('cattle.ai.health');
    Route::post('cattle/{cattle}/weights', [CattleController::class, 'storeWeight'])->name('cattle.weights.store');
    Route::post('cattle/{cattle}/bcs', [CattleController::class, 'storeBcs'])->name('cattle.bcs.store');
    Route::post('cattle/{cattle}/health', [CattleController::class, 'storeHealth'])->name('cattle.health.store');
    Route::post('cattle/{cattle}/health/ai', [CattleController::class, 'storeHealthFromAi'])->name('cattle.health.ai');
    Route::put('health/{record}', [CattleController::class, 'updateHealth'])->name('cattle.health.update');
    Route::delete('health/{record}', [CattleController::class, 'destroyHealth'])->name('cattle.health.destroy');
    Route::post('health/{record}/ai', [CattleController::class, 'updateHealthWithAi'])->name('cattle.health.ai-update');
    Route::delete('ai-examinations/{exam}', [CattleController::class, 'destroyExamination'])->name('cattle.ai.destroy');
    Route::post('cattle/{cattle}/vaccination-schedules', [CattleController::class, 'storeSchedule'])->name('cattle.schedules.store');
    Route::put('vaccination-schedules/{schedule}', [CattleController::class, 'updateSchedule'])->name('cattle.schedules.update');
    Route::delete('vaccination-schedules/{schedule}', [CattleController::class, 'destroySchedule'])->name('cattle.schedules.destroy');
    Route::put('vaccination-records/{record}', [CattleController::class, 'updateVaccinationRecord'])->name('cattle.vaccination-records.update');
    Route::delete('vaccination-records/{record}', [CattleController::class, 'destroyVaccinationRecord'])->name('cattle.vaccination-records.destroy');
    Route::post('cattle/{cattle}/feeds', [CattleController::class, 'storeFeed'])->name('cattle.feeds.store');
    Route::put('feeds/{feed}', [CattleController::class, 'updateFeed'])->name('cattle.feeds.update');
    Route::delete('feeds/{feed}', [CattleController::class, 'destroyFeed'])->name('cattle.feeds.destroy');
    Route::post('cattle/{cattle}/reproduction', [CattleController::class, 'storeReproduction'])->name('cattle.reproduction.store');
    Route::put('reproduction/{record}', [CattleController::class, 'updateReproduction'])->name('cattle.reproduction.update');
    Route::delete('reproduction/{record}', [CattleController::class, 'destroyReproduction'])->name('cattle.reproduction.destroy');
    Route::post('cattle/{cattle}/mortality', [CattleController::class, 'storeMortality'])->name('cattle.mortality.store');
};

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () use ($cattle) {
    Route::get('dashboard', [DashboardController::class, 'admin'])->name('dashboard');
    $cattle();
    Route::get('peternak', [ModuleController::class, 'farmers'])->name('farmers.index');
    Route::get('peternak/{farmer}', [ModuleController::class, 'farmerShow'])->name('farmers.show');
    Route::get('kematian', [ModuleController::class, 'mortality'])->name('mortality.index');
    Route::get('master/ras', [ModuleController::class, 'breeds'])->name('breeds.index');
    Route::post('master/ras', [ModuleController::class, 'storeBreed'])->name('breeds.store');
    Route::put('master/ras/{breed}', [ModuleController::class, 'updateBreed'])->name('breeds.update');
    Route::get('master/vaksin', [ModuleController::class, 'vaccines'])->name('vaccines.index');
    Route::post('master/vaksin', [ModuleController::class, 'storeVaccine'])->name('vaccines.store');
    Route::put('master/vaksin/{vaccine}', [ModuleController::class, 'updateVaccine'])->name('vaccines.update');
    Route::get('pengguna', [ModuleController::class, 'users'])->name('users.index');
    Route::post('pengguna', [ModuleController::class, 'storeUser'])->name('users.store');
});

Route::middleware(['auth', 'verified', 'role:peternak'])->prefix('peternak')->name('peternak.')->group(function () use ($cattle) {
    Route::get('dashboard', [DashboardController::class, 'peternak'])->name('dashboard');
    $cattle();
});

require __DIR__.'/auth.php';
