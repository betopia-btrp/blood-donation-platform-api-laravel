<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\User\ProfileController;
use App\Http\Controllers\API\Organization\OrganizationProfileController;
use App\Http\Controllers\API\Admin\UserManagementController;
use App\Http\Controllers\API\Admin\AdminDashboardController;
use App\Http\Controllers\API\Admin\AdminEventController;
use App\Http\Controllers\API\Admin\AdminDonationRequestController;
use App\Http\Controllers\API\Admin\AdminPaymentController;
use App\Http\Controllers\API\Admin\AdminReportController;
use App\Http\Controllers\API\Dashboard\OrgDashboardController;
use App\Http\Controllers\API\Dashboard\UserDashboardController;
use App\Http\Controllers\API\DonationRequest\DonationRequestController;
use App\Http\Controllers\API\Donor\DonorActionController;
use App\Http\Controllers\API\Donor\DonorController;
use App\Http\Controllers\API\Organization\EventController;
use App\Http\Controllers\API\Event\EventDiscoveryController;
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
        Route::get('/me',               [AuthController::class, 'me']);
        Route::post('/logout',          [AuthController::class, 'logout']);
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

Route::get('/donors',      [DonorController::class, 'index']);
Route::get('/donors/{id}', [DonorController::class, 'show']);

Route::get('/events',      [EventDiscoveryController::class, 'index']);
Route::get('/events/{id}', [EventDiscoveryController::class, 'show']);

Route::middleware('auth:api')->group(function () {
    Route::post('/events/{id}/register',   [EventDiscoveryController::class, 'register']);
    Route::delete('/events/{id}/register', [EventDiscoveryController::class, 'cancelRegistration']);
    Route::post('/events/{id}/report',     [EventDiscoveryController::class, 'report']);
});

Route::middleware('auth:api')->prefix('donation-requests')->group(function () {
    Route::post('/',                     [DonationRequestController::class, 'store']);
    Route::get('/{id}',                  [DonationRequestController::class, 'show']);
    Route::delete('/{id}',               [DonationRequestController::class, 'destroy']);
    Route::get('/{id}/acceptances',      [DonationRequestController::class, 'acceptances']);
    Route::post('/{id}/confirm-payment', [DonationRequestController::class, 'confirmPayment']);
    Route::get('/{id}/donors-revealed',  [DonationRequestController::class, 'donorsRevealed']);
    Route::post('/{id}/complete',        [DonationRequestController::class, 'complete']);
    Route::post('/{id}/report',          [DonationRequestController::class, 'report']);
});

Route::middleware(['auth:api', 'role:user'])
    ->prefix('my')
    ->group(function () {
        Route::get('/incoming-requests',                       [DonorActionController::class, 'incomingRequests']);
        Route::get('/incoming-requests/{id}',                  [DonorActionController::class, 'incomingRequestShow']);
        Route::post('/incoming-requests/{id}/accept',          [DonorActionController::class, 'accept']);
        Route::post('/incoming-requests/{id}/reject',          [DonorActionController::class, 'reject']);
        Route::post('/incoming-requests/{id}/confirm-donated', [DonorActionController::class, 'confirmDonated']);
    });

Route::middleware(['auth:api', 'role:user'])
    ->prefix('dashboard')
    ->group(function () {
        Route::get('/my-requests',       [UserDashboardController::class, 'myRequests']);
        Route::get('/my-requests/{id}',  [UserDashboardController::class, 'myRequestShow']);
        Route::get('/incoming-requests', [UserDashboardController::class, 'incomingRequests']);
        Route::get('/my-events',         [UserDashboardController::class, 'myEvents']);
        Route::get('/my-donations',      [UserDashboardController::class, 'myDonations']);
        Route::get('/stats',             [UserDashboardController::class, 'stats']);
    });

Route::middleware(['auth:api', 'role:organization'])
    ->prefix('dashboard/org')
    ->group(function () {
        Route::get('/events',                    [OrgDashboardController::class, 'myEvents']);
        Route::get('/events/{id}',               [OrgDashboardController::class, 'myEventShow']);
        Route::get('/stats',                     [OrgDashboardController::class, 'stats']);
        Route::post('/events',                   [EventController::class, 'store']);
        Route::put('/events/{id}',               [EventController::class, 'update']);
        Route::delete('/events/{id}',            [EventController::class, 'destroy']);
        Route::get('/events/{id}/registrations', [EventController::class, 'registrations']);
        Route::put('/events/{id}/attendance',    [EventController::class, 'updateAttendance']);
    });

Route::middleware(['auth:api', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/stats', [AdminDashboardController::class, 'stats']);

        Route::get('/users',                     [UserManagementController::class, 'index']);
        Route::get('/users/{id}',                [UserManagementController::class, 'show']);
        Route::put('/users/{id}/activate',       [UserManagementController::class, 'activate']);
        Route::put('/users/{id}/deactivate',     [UserManagementController::class, 'deactivate']);
        Route::delete('/users/{id}',             [UserManagementController::class, 'destroy']);
        Route::put('/users/{id}/approve-org',    [UserManagementController::class, 'approveOrg']);
        Route::put('/users/{id}/reject-org',     [UserManagementController::class, 'rejectOrg']);

        Route::get('/events',              [AdminEventController::class, 'index']);
        Route::get('/events/{id}',         [AdminEventController::class, 'show']);
        Route::put('/events/{id}/approve', [AdminEventController::class, 'approve']);
        Route::put('/events/{id}/cancel',  [AdminEventController::class, 'cancel']);

        Route::get('/donation-requests',      [AdminDonationRequestController::class, 'index']);
        Route::get('/donation-requests/{id}', [AdminDonationRequestController::class, 'show']);

        Route::get('/payments',      [AdminPaymentController::class, 'index']);
        Route::get('/payments/{id}', [AdminPaymentController::class, 'show']);

        Route::get('/reports',             [AdminReportController::class, 'index']);
        Route::get('/reports/{id}',        [AdminReportController::class, 'show']);
        Route::put('/reports/{id}/review', [AdminReportController::class, 'review']);
        Route::put('/reports/{id}/resolve', [AdminReportController::class, 'resolve']);
    });
