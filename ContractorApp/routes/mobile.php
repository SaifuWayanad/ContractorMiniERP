<?php

use App\Http\Controllers\Mobile\MobileAdminContractorController;
use App\Http\Controllers\Mobile\MobileAdminNotificationController;
use App\Http\Controllers\Mobile\MobileAdminRoleController;
use App\Http\Controllers\Mobile\MobileAdminUserController;
use App\Http\Controllers\Mobile\MobileAuthController;
use App\Http\Controllers\Mobile\MobileClientController;
use App\Http\Controllers\Mobile\MobileContractorController;
use App\Http\Controllers\Mobile\MobileDashboardController;
use App\Http\Controllers\Mobile\MobileExpenseController;
use App\Http\Controllers\Mobile\MobileProductController;
use App\Http\Controllers\Mobile\MobileProjectPaymentController;
use App\Http\Controllers\Mobile\MobilePurchaseController;
use App\Http\Controllers\Mobile\MobileWorkTypeController;
use App\Http\Controllers\Mobile\MobileProfileController;
use App\Http\Controllers\Mobile\MobileProjectController;
use App\Http\Controllers\Mobile\MobileWorkController;
use Illuminate\Support\Facades\Route;

/*
| Mobile API routes — consumed by the Build Mate React Native app.
| All routes except login are protected by Sanctum token auth.
*/

Route::prefix('mobile')->group(function (): void {
    // Auth
    Route::post('login', [MobileAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [MobileAuthController::class, 'logout']);

        // Profile
        Route::get('profile', [MobileProfileController::class, 'show']);
        Route::put('profile', [MobileProfileController::class, 'update']);
        Route::put('account/password', [MobileProfileController::class, 'changePassword']);
        Route::post('device-token', [MobileProfileController::class, 'updateDeviceToken']);

        // Dashboard
        Route::get('dashboard', [MobileDashboardController::class, 'index']);

        // Projects
        Route::get('projects', [MobileProjectController::class, 'index']);
        Route::post('projects', [MobileProjectController::class, 'store']);
        Route::get('projects/{project}', [MobileProjectController::class, 'show']);
        Route::put('projects/{project}', [MobileProjectController::class, 'update']);
        Route::delete('projects/{project}', [MobileProjectController::class, 'destroy']);
        Route::patch('projects/{project}/status', [MobileProjectController::class, 'updateStatus']);
        Route::patch('projects/{project}/meeting', [MobileProjectController::class, 'updateMeeting']);
        Route::patch('projects/{project}/meeting/complete', [MobileProjectController::class, 'completeMeeting']);
        Route::post('projects/{project}/meetings', [MobileProjectController::class, 'storeMeeting']);
        Route::delete('projects/{project}/meetings/{meeting}', [MobileProjectController::class, 'destroyMeeting']);
        Route::get('projects/{project}/activity', [MobileProjectController::class, 'activity']);
        // Works
        Route::get('works', [MobileWorkController::class, 'index']);
        Route::post('works', [MobileWorkController::class, 'store']);
        Route::patch('works/{work}', [MobileWorkController::class, 'update']);
        Route::delete('works/{work}', [MobileWorkController::class, 'destroy']);

        // Clients
        Route::get('clients', [MobileClientController::class, 'index']);
        Route::post('clients', [MobileClientController::class, 'store']);
        Route::get('clients/{client}', [MobileClientController::class, 'show']);
        Route::put('clients/{client}', [MobileClientController::class, 'update']);
        Route::delete('clients/{client}', [MobileClientController::class, 'destroy']);

        // Work types (project dropdown)
        Route::get('work-types', [MobileWorkTypeController::class, 'index']);
        Route::post('work-types', [MobileWorkTypeController::class, 'store']);
        Route::put('work-types/{workType}', [MobileWorkTypeController::class, 'update']);
        Route::delete('work-types/{workType}', [MobileWorkTypeController::class, 'destroy']);

        // Products
        Route::get('products', [MobileProductController::class, 'index']);
        Route::post('products', [MobileProductController::class, 'store']);
        Route::put('products/{product}', [MobileProductController::class, 'update']);
        Route::delete('products/{product}', [MobileProductController::class, 'destroy']);

        // Purchases
        Route::get('purchases', [MobilePurchaseController::class, 'index']);
        Route::post('purchases', [MobilePurchaseController::class, 'store']);
        Route::put('purchases/{purchase}', [MobilePurchaseController::class, 'update']);
        Route::delete('purchases/{purchase}', [MobilePurchaseController::class, 'destroy']);

        // Expenses
        Route::get('expenses', [MobileExpenseController::class, 'index']);
        Route::post('expenses', [MobileExpenseController::class, 'store']);
        Route::put('expenses/{expense}', [MobileExpenseController::class, 'update']);
        Route::delete('expenses/{expense}', [MobileExpenseController::class, 'destroy']);

        // Payments received
        Route::get('payments', [MobileProjectPaymentController::class, 'index']);
        Route::post('payments', [MobileProjectPaymentController::class, 'store']);
        Route::put('payments/{payment}', [MobileProjectPaymentController::class, 'update']);
        Route::delete('payments/{payment}', [MobileProjectPaymentController::class, 'destroy']);

        // Meetings (upcoming)
        Route::get('meetings', [MobileProjectController::class, 'meetings']);

        // Contractors list (for admin — used in create forms)
        Route::get('contractors', [MobileContractorController::class, 'index']);

        // Admin-only
        Route::middleware('admin')->group(function (): void {
            Route::get('admin/contractors', [MobileAdminContractorController::class, 'index']);
            Route::post('admin/contractors', [MobileAdminContractorController::class, 'store']);
            Route::get('admin/contractors/{contractor}', [MobileAdminContractorController::class, 'show']);
            Route::put('admin/contractors/{contractor}', [MobileAdminContractorController::class, 'update']);
            Route::patch('admin/contractors/{contractor}/status', [MobileAdminContractorController::class, 'toggleStatus']);

            Route::get('admin/users', [MobileAdminUserController::class, 'index']);
            Route::post('admin/users', [MobileAdminUserController::class, 'store']);
            Route::get('admin/users/{user}', [MobileAdminUserController::class, 'show']);
            Route::put('admin/users/{user}', [MobileAdminUserController::class, 'update']);

            Route::get('admin/roles', [MobileAdminRoleController::class, 'index']);
            Route::get('admin/roles/{role}', [MobileAdminRoleController::class, 'show']);

            Route::post('admin/notify', [MobileAdminNotificationController::class, 'send']);
        });
    });
});
