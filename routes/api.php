<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\QrCodeController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\RestaurantProfileController;
use App\Http\Controllers\Api\PublicMenuController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\TechnicalReportController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PlanEnquiryController;
use App\Http\Middleware\CheckTrialStatus;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/
Route::post('/plan-enquiry', [PlanEnquiryController::class, 'store']);
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/google', [AuthController::class, 'googleLogin']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/verify-admin-otp', [AuthController::class, 'verifyAdminOtp']);
    Route::post('/resend-admin-otp', [AuthController::class, 'resendAdminOtp']);
    Route::post('/create-admin-password', [AuthController::class, 'createAdminPassword']);
});

Route::prefix('public')->group(function () {
    Route::get('/menu', [PublicMenuController::class, 'getPublicMenu']);
    Route::get('/menu/{identifier}', [PublicMenuController::class, 'getPublicMenu']);
    Route::get('/menu/{restaurantSlug}/item/{itemSlug}', [PublicMenuController::class, 'getMenuItemDetails']);
    Route::get('/qr/{code}', [PublicMenuController::class, 'resolveQrCode']);
    Route::post('/reviews', [ReviewController::class, 'storeCustomerReview']);
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
    });

    // Subscription & Plans Routes
    Route::prefix('subscription')->group(function () {
        Route::get('/plans', [SubscriptionController::class, 'getPlans']);
        Route::post('/upgrade', [SubscriptionController::class, 'upgrade']);
    });

    /*
    |--------------------------------------------------------------------------
    | Super Admin Routes (RBAC)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super_admin')->prefix('super-admin')->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard']);
        
        // Admin Accounts Management
        Route::get('/admins', [SuperAdminController::class, 'listAdmins']);
        Route::post('/admins', [SuperAdminController::class, 'storeAdmin']);
        Route::put('/admins/{id}/status', [SuperAdminController::class, 'updateAdminStatus']);
        Route::put('/admins/{id}/reset-password', [SuperAdminController::class, 'resetAdminPassword']);
        Route::delete('/admins/{id}', [SuperAdminController::class, 'deleteAdmin']);

        // Trial Management Module
        Route::get('/trials', [SuperAdminController::class, 'listTrials']);
        Route::post('/trials/{id}/extend', [SuperAdminController::class, 'extendTrial']);
        Route::post('/trials/{id}/end', [SuperAdminController::class, 'endTrial']);
        Route::post('/trials/{id}/convert-to-paid', [SuperAdminController::class, 'convertToPaid']);
        Route::post('/trials/{id}/activate', [SuperAdminController::class, 'activateAccount']);
        Route::post('/trials/{id}/suspend', [SuperAdminController::class, 'suspendAccount']);

        // Technical Reports
        Route::get('/technical-reports', [SuperAdminController::class, 'listTechnicalReports']);
        Route::put('/technical-reports/{id}/reply', [SuperAdminController::class, 'replyTechnicalReport']);

        // Audit Logs & Security
        Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs']);
        Route::get('/login-logs', [SuperAdminController::class, 'loginLogs']);
    });

    /*
    |--------------------------------------------------------------------------
    | Restaurant Admin Routes (RBAC + Trial Protection)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin', CheckTrialStatus::class])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'dashboard']);

        // Categories – Full multi-level system
        Route::get('/categories',                    [CategoryController::class, 'index']);
        Route::get('/categories/tree',               [CategoryController::class, 'tree']);
        Route::get('/categories/stats',              [CategoryController::class, 'stats']);
        Route::post('/categories',                   [CategoryController::class, 'store']);
        Route::patch('/categories/order',            [CategoryController::class, 'updateOrder']);
        Route::patch('/categories/bulk-status',      [CategoryController::class, 'bulkStatus']);
        Route::post('/categories/bulk-delete',       [CategoryController::class, 'bulkDelete']);
        Route::get('/categories/{id}',               [CategoryController::class, 'show']);
        Route::put('/categories/{id}',               [CategoryController::class, 'update']);
        Route::delete('/categories/{id}',            [CategoryController::class, 'destroy']);
        Route::post('/categories/{id}/restore',      [CategoryController::class, 'restore']);

        // Menu Items
        Route::get('/menu-items', [MenuItemController::class, 'index']);
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::put('/menu-items/{id}', [MenuItemController::class, 'update']);
        Route::post('/menu-items/{id}/duplicate', [MenuItemController::class, 'duplicate']);
        Route::delete('/menu-items/{id}', [MenuItemController::class, 'destroy']);
        Route::post('/menu-items/bulk-delete', [MenuItemController::class, 'bulkDelete']);
        Route::post('/menu-items/bulk-availability', [MenuItemController::class, 'bulkAvailability']);
        Route::post('/menu-items/bulk-status', [MenuItemController::class, 'bulkStatus']);

        // Availability Schedules
        Route::get('/availability', [AvailabilityController::class, 'index']);
        Route::post('/availability', [AvailabilityController::class, 'store']);
        Route::put('/availability/{id}/toggle', [AvailabilityController::class, 'toggleSchedule']);

        // QR Codes
        Route::get('/qr-codes', [QrCodeController::class, 'index']);
        Route::post('/qr-codes/generate', [QrCodeController::class, 'generate']);
        Route::post('/qr-codes/{id}/regenerate', [QrCodeController::class, 'regenerate']);

        // Reviews Management
        Route::get('/reviews', [ReviewController::class, 'index']);
        Route::post('/reviews/{id}/reply', [ReviewController::class, 'reply']);
        Route::put('/reviews/{id}/toggle-status', [ReviewController::class, 'toggleStatus']);

        // Gallery
        Route::get('/gallery', [GalleryController::class, 'index']);
        Route::post('/gallery', [GalleryController::class, 'store']);
        Route::delete('/gallery/{id}', [GalleryController::class, 'destroy']);

        // Restaurant Profile
        Route::get('/profile', [RestaurantProfileController::class, 'show']);
        Route::put('/profile', [RestaurantProfileController::class, 'update']);

        // Reports & Analytics
        Route::get('/reports', [ReportController::class, 'analyticsReport']);

        // Submit Technical Report to Super Admin
        Route::get('/technical-reports', [TechnicalReportController::class, 'index']);
        Route::post('/technical-reports', [TechnicalReportController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | Customer / User Routes (RBAC)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:user,admin,super_admin')->prefix('user')->group(function () {
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites/toggle/{menuItemId}', [FavoriteController::class, 'toggle']);
        Route::post('/reviews', [ReviewController::class, 'storeCustomerReview']);
    });
});
