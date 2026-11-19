<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\VehicleController;
use App\Http\Controllers\Api\V1\OperatorController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaxRateController;
use App\Http\Controllers\Api\V1\InventoryOpeningController;
use App\Http\Controllers\Api\V1\InventoryBalanceController;
use App\Http\Controllers\Api\V1\DispatchController;
use App\Http\Controllers\Api\V1\ReturnController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\LossController;
use App\Http\Controllers\Api\V1\DamageController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\CustomerReportController;
use App\Http\Controllers\Api\V1\ProjectClosureController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\UserController;

Route::prefix('v1')->group(function () {

    // Auth
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        // Users
        Route::apiResource('users', UserController::class);

        // Masters
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('locations', LocationController::class);
        Route::apiResource('vehicles', VehicleController::class);
        Route::apiResource('operators', OperatorController::class);
        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('tax-rates', TaxRateController::class);

        // Inventory
        Route::apiResource('inventory/openings', InventoryOpeningController::class);
        Route::post('inventory/openings/{opening}/post', [InventoryOpeningController::class, 'post']);
        Route::post('inventory/openings/{opening}/cancel', [InventoryOpeningController::class, 'cancel']);
        Route::get('inventory/balances', [InventoryBalanceController::class, 'index']);
        Route::get('inventory/movements', [InventoryBalanceController::class, 'movements']);

        // Dispatches
        Route::apiResource('dispatches', DispatchController::class);
        Route::post('dispatches/{dispatch}/submit', [DispatchController::class, 'submit']);
        Route::post('dispatches/{dispatch}/approve', [DispatchController::class, 'approve']);
        Route::post('dispatches/{dispatch}/post', [DispatchController::class, 'post']);
        Route::post('dispatches/{dispatch}/cancel', [DispatchController::class, 'cancel']);

        // Returns
        Route::apiResource('returns', ReturnController::class);
        Route::post('returns/{return}/submit', [ReturnController::class, 'submit']);
        Route::post('returns/{return}/approve', [ReturnController::class, 'approve']);
        Route::post('returns/{return}/post', [ReturnController::class, 'post']);
        Route::post('returns/{return}/cancel', [ReturnController::class, 'cancel']);

        // Transfers
        Route::apiResource('transfers', TransferController::class);
        Route::post('transfers/{transfer}/submit', [TransferController::class, 'submit']);
        Route::post('transfers/{transfer}/approve', [TransferController::class, 'approve']);
        Route::post('transfers/{transfer}/post', [TransferController::class, 'post']);
        Route::post('transfers/{transfer}/cancel', [TransferController::class, 'cancel']);

        // Losses
        Route::apiResource('losses', LossController::class);
        Route::post('losses/{loss}/submit', [LossController::class, 'submit']);
        Route::post('losses/{loss}/approve', [LossController::class, 'approve']);
        Route::post('losses/{loss}/post', [LossController::class, 'post']);
        Route::post('losses/{loss}/cancel', [LossController::class, 'cancel']);

        // Damages
        Route::apiResource('damages', DamageController::class);
        Route::post('damages/{damage}/submit', [DamageController::class, 'submit']);
        Route::post('damages/{damage}/approve', [DamageController::class, 'approve']);
        Route::post('damages/{damage}/post', [DamageController::class, 'post']);

        // Billing
        Route::get('billing/uninvoiced-returns', [InvoiceController::class, 'uninvoicedReturns']);
        Route::post('invoices/preview', [InvoiceController::class, 'preview']);
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('invoices/{invoice}/post', [InvoiceController::class, 'post']);
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
        Route::apiResource('credit-notes', InvoiceController::class);

        // Payments
        Route::apiResource('payments', PaymentController::class);
        Route::post('payments/{payment}/post', [PaymentController::class, 'post']);
        Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel']);
        Route::post('payments/{payment}/allocations', [PaymentController::class, 'allocate']);

        // Customer 360
        Route::get('customers/{customer}/360', [CustomerReportController::class, 'show360']);
        Route::get('customers/{customer}/equipment', [CustomerReportController::class, 'equipment']);
        Route::get('customers/{customer}/financial-summary', [CustomerReportController::class, 'financialSummary']);

        // Project operations
        Route::get('projects/{project}/outstanding', [ProjectClosureController::class, 'outstanding']);
        Route::get('projects/{project}/transactions', [ProjectClosureController::class, 'transactions']);
        Route::post('projects/{project}/closure/initiate', [ProjectClosureController::class, 'initiate']);
        Route::get('projects/{project}/closure/reconciliation', [ProjectClosureController::class, 'reconciliation']);
        Route::post('projects/{project}/closure/preview', [ProjectClosureController::class, 'preview']);
        Route::post('projects/{project}/closure/approve', [ProjectClosureController::class, 'approve']);
        Route::post('projects/{project}/closure/complete', [ProjectClosureController::class, 'complete']);
        Route::get('projects/{project}/closure/statement', [ProjectClosureController::class, 'statement']);

        // Reports
        Route::get('reports/project-outstanding', [ReportController::class, 'projectOutstanding']);
        Route::get('reports/customer-outstanding', [ReportController::class, 'customerOutstanding']);
        Route::get('reports/item-movement', [ReportController::class, 'itemMovement']);
        Route::get('reports/available-inventory', [ReportController::class, 'availableInventory']);
        Route::get('reports/inventory-by-location', [ReportController::class, 'inventoryByLocation']);
        Route::get('reports/uninvoiced-returns', [ReportController::class, 'uninvoicedReturns']);
        Route::get('reports/invoice-register', [ReportController::class, 'invoiceRegister']);
        Route::get('reports/customer-financial-outstanding', [ReportController::class, 'customerFinancialOutstanding']);
        Route::get('reports/lost-equipment', [ReportController::class, 'lostEquipment']);
        Route::get('reports/damaged-equipment', [ReportController::class, 'damagedEquipment']);
        Route::get('reports/project-closures', [ReportController::class, 'projectClosures']);
        Route::get('reports/rental-revenue', [ReportController::class, 'rentalRevenue']);
    });
});
