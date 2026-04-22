<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\User\ProfileController;
use App\Http\Controllers\API\Organization\OrganizationProfileController;
use App\Http\Controllers\API\Admin\UserManagementController;
use App\Http\Controllers\API\DonationRequest\DonationRequestController;
use App\Http\Controllers\API\Donor\DonorActionController;
use App\Http\Controllers\API\Donor\DonorController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'version' => '1.0.0',
        'timestamp' => now(),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me',              [AuthController::class, 'me']);
        Route::post('/logout',         [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

Route::middleware(['auth:api', 'role:user'])
    ->prefix('user')
    ->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
    });

Route::middleware(['auth:api', 'role:organization'])
    ->prefix('organization')
    ->group(function () {
        Route::get('/profile', [OrganizationProfileController::class, 'show']);
        Route::put('/profile', [OrganizationProfileController::class, 'update']);
    });

Route::middleware(['auth:api', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/users',                    [UserManagementController::class, 'index']);
        Route::put('/users/{id}/activate',      [UserManagementController::class, 'activate']);
        Route::put('/users/{id}/deactivate',    [UserManagementController::class, 'deactivate']);
    });

Route::middleware('auth:api')->group(function () {
    Route::get('/donors',      [DonorController::class, 'index']);
    Route::get('/donors/{id}', [DonorController::class, 'show']);
});

Route::middleware('auth:api')->prefix('donation-requests')->group(function () {
    Route::post('/',                        [DonationRequestController::class, 'store']);
    Route::get('/{id}',                     [DonationRequestController::class, 'show']);
    Route::delete('/{id}',                  [DonationRequestController::class, 'destroy']);
    Route::get('/{id}/acceptances',         [DonationRequestController::class, 'acceptances']);
    Route::post('/{id}/confirm-payment',    [DonationRequestController::class, 'confirmPayment']);
    Route::get('/{id}/donors-revealed',     [DonationRequestController::class, 'donorsRevealed']);
    Route::post('/{id}/complete',           [DonationRequestController::class, 'complete']);
    Route::post('/{id}/report',             [DonationRequestController::class, 'report']);
});

Route::middleware(['auth:api', 'role:user'])
    ->prefix('my')
    ->group(function () {
        Route::get('/incoming-requests',              [DonorActionController::class, 'incomingRequests']);
        Route::get('/incoming-requests/{id}',         [DonorActionController::class, 'incomingRequestShow']);
        Route::post('/incoming-requests/{id}/accept', [DonorActionController::class, 'accept']);
        Route::post('/incoming-requests/{id}/reject', [DonorActionController::class, 'reject']);
        Route::post('/incoming-requests/{id}/confirm-donated', [DonorActionController::class, 'confirmDonated']);
    });
