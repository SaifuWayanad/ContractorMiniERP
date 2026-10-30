<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Same paths as the ASP.NET API (base URL is .../api).
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::apiResource('customers', CustomerController::class)->parameters(['customers' => 'customer']);
    Route::get('customers/{customer}/projects', [CustomerController::class, 'projects']);

    Route::apiResource('locations', LocationController::class)->parameters(['locations' => 'location']);

    Route::apiResource('projects', ProjectController::class)->parameters(['projects' => 'project']);
    Route::get('projects/{project}/summary', [ProjectController::class, 'summary']);

    Route::apiResource('products', ProductController::class)->parameters(['products' => 'product']);
    Route::apiResource('purchases', PurchaseController::class)->parameters(['purchases' => 'purchase']);
    Route::apiResource('expenses', ExpenseController::class)->parameters(['expenses' => 'expense']);
    Route::apiResource('payments', PaymentController::class)->parameters(['payments' => 'payment']);

    Route::get('reports/outstanding', [ReportController::class, 'outstanding']);
    Route::get('reports/outstanding/{project}/transactions', [ReportController::class, 'projectTransactions']);

    Route::middleware('admin')->group(function (): void {
        // Registered before the {user} resource so "contractors" is not read as an id.
        Route::get('users/contractors', [UserController::class, 'contractors']);
        Route::apiResource('users', UserController::class)->parameters(['users' => 'user']);
    });
});
