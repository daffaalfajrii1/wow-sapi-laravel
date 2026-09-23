<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CattleApiController;
use App\Http\Controllers\Api\V1\FarmerApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('auth/resend-otp', [AuthController::class, 'resendOtp']);
        Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::put('auth/profile', [AuthController::class, 'profile']);
        Route::put('auth/password', [AuthController::class, 'password']);

        Route::get('dashboard', [FarmerApiController::class, 'dashboard']);
        Route::get('notifications', [FarmerApiController::class, 'notifications']);
        Route::post('notifications/read', [FarmerApiController::class, 'markNotifications']);
        Route::get('examinations', [FarmerApiController::class, 'examinations']);
        Route::get('vaccinations', [FarmerApiController::class, 'vaccinations']);

        Route::get('lookups/breeds', [CattleApiController::class, 'breeds']);
        Route::get('lookups/vaccines', [CattleApiController::class, 'vaccines']);

        Route::get('cattle', [CattleApiController::class, 'index']);
        Route::post('cattle', [CattleApiController::class, 'store']);
        Route::get('cattle/{cattle}', [CattleApiController::class, 'show']);
        Route::put('cattle/{cattle}', [CattleApiController::class, 'update']);
        Route::post('cattle/{cattle}', [CattleApiController::class, 'update']);
        Route::delete('cattle/{cattle}', [CattleApiController::class, 'destroy']);
        Route::get('cattle/{cattle}/timeline', [CattleApiController::class, 'timeline']);
        Route::get('cattle/{cattle}/report', [FarmerApiController::class, 'report']);
        Route::get('cattle/{cattle}/report.pdf', [FarmerApiController::class, 'reportPdf']);

        Route::post('cattle/{cattle}/ai/weight', [CattleApiController::class, 'aiWeight']);
        Route::post('cattle/{cattle}/ai/lumpy', [CattleApiController::class, 'aiLumpy']);
        Route::post('cattle/{cattle}/ai/combined', [CattleApiController::class, 'aiCombined']);
        Route::post('cattle/{cattle}/ai/health', [CattleApiController::class, 'saveLumpyHealth']);
        Route::get('cattle/{cattle}/ai/history', [CattleApiController::class, 'aiHistory']);
        Route::delete('ai-examinations/{exam}', [CattleApiController::class, 'destroyExamination']);

        Route::get('cattle/{cattle}/weights', [CattleApiController::class, 'weights']);
        Route::post('cattle/{cattle}/weights', [CattleApiController::class, 'storeWeight']);
        Route::get('cattle/{cattle}/bcs', [CattleApiController::class, 'bcs']);
        Route::post('cattle/{cattle}/bcs', [CattleApiController::class, 'storeBcs']);
        Route::get('cattle/{cattle}/health', [CattleApiController::class, 'health']);
        Route::post('cattle/{cattle}/health', [CattleApiController::class, 'storeHealth']);
        Route::post('cattle/{cattle}/health/ai', [CattleApiController::class, 'storeHealthFromAi']);
        Route::put('health/{record}', [CattleApiController::class, 'updateHealth']);
        Route::delete('health/{record}', [CattleApiController::class, 'destroyHealth']);
        Route::post('health/{record}/ai', [CattleApiController::class, 'updateHealthWithAi']);
        Route::get('cattle/{cattle}/vaccinations', [CattleApiController::class, 'vaccinations']);
        Route::post('cattle/{cattle}/vaccination-schedules', [CattleApiController::class, 'storeSchedule']);
        Route::put('vaccination-schedules/{schedule}', [CattleApiController::class, 'updateSchedule']);
        Route::delete('vaccination-schedules/{schedule}', [CattleApiController::class, 'destroySchedule']);
        Route::post('vaccination-schedules/{schedule}/complete', [CattleApiController::class, 'completeSchedule']);
        Route::put('vaccination-records/{record}', [CattleApiController::class, 'updateVaccinationRecord']);
        Route::delete('vaccination-records/{record}', [CattleApiController::class, 'destroyVaccinationRecord']);
        Route::get('cattle/{cattle}/reproduction', [CattleApiController::class, 'reproduction']);
        Route::post('cattle/{cattle}/reproduction', [CattleApiController::class, 'storeReproduction']);
        Route::put('reproduction/{record}', [CattleApiController::class, 'updateReproduction']);
        Route::delete('reproduction/{record}', [CattleApiController::class, 'destroyReproduction']);
        Route::get('cattle/{cattle}/feeds', [CattleApiController::class, 'feeds']);
        Route::post('cattle/{cattle}/feeds', [CattleApiController::class, 'storeFeed']);
        Route::put('feeds/{feed}', [CattleApiController::class, 'updateFeed']);
        Route::delete('feeds/{feed}', [CattleApiController::class, 'destroyFeed']);
        Route::get('cattle/{cattle}/mortality', [CattleApiController::class, 'mortality']);
        Route::post('cattle/{cattle}/mortality', [CattleApiController::class, 'storeMortality']);

        Route::post('device-tokens', [CattleApiController::class, 'storeDeviceToken']);
        Route::delete('device-tokens/{id}', [CattleApiController::class, 'destroyDeviceToken']);
    });
});
