<?php

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectPaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\WorkTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectLocationController;
use App\Http\Controllers\ProjectMeetingController;
use App\Http\Controllers\ProjectStatusController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\WorkStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| Guests: sign in and password reset.
*/
Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', fn (Request $request) => redirect($request->user()->homeRoute()))->name('home');

    Route::get('account/password', [AccountPasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('account/password', [AccountPasswordController::class, 'update'])->name('account.password.update');

    /*
    | Contractor portal. Every query here is scoped to the signed-in user's contractor.
    */
    Route::middleware('contractor')->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');
        Route::get('dashboard/project-trend', [DashboardController::class, 'trend'])->middleware('can:dashboard.view')->name('dashboard.trend');

        Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::resource('clients', ClientController::class);
        Route::post('clients/quick', [ClientController::class, 'quickStore'])->name('clients.quick');
        Route::resource('projects', ProjectController::class);
        Route::patch('projects/{project}/status', ProjectStatusController::class)->name('projects.status');
        Route::patch('projects/{project}/meeting', [ProjectController::class, 'updateMeeting'])->name('projects.meeting');
        Route::post('projects/{project}/meetings', [ProjectMeetingController::class, 'store'])->name('projects.meetings.store');
        Route::delete('projects/{project}/meetings/{meeting}', [ProjectMeetingController::class, 'destroy'])->name('projects.meetings.destroy');
        Route::get('locations', [ProjectLocationController::class, 'index'])->name('locations.index');
        Route::get('locations/create', [ProjectLocationController::class, 'chooseProject'])->name('locations.create');
        Route::get('projects/{project}/locations', [ProjectLocationController::class, 'forProject'])->name('projects.locations.index');
        Route::resource('projects.locations', ProjectLocationController::class)->shallow()->except('index');

        Route::resource('works', WorkController::class);
        Route::patch('works/{work}/status', WorkStatusController::class)->name('works.status');

        Route::post('products/quick', [ProductController::class, 'quickStore'])->name('products.quick-store');
        Route::resource('products', ProductController::class)->except('show');
        Route::resource('purchases', PurchaseController::class)->except('show');
        Route::resource('expenses', ExpenseController::class)->except('show');
        Route::resource('payments', ProjectPaymentController::class)->except('show');

        Route::get('reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding');
        Route::get('reports/customer-outstanding', [ReportController::class, 'customerOutstanding'])->name('reports.customer-outstanding');

        Route::resource('visits', VisitController::class)->except('show');
        Route::get('reports/customer-outstanding', [ReportController::class, 'customerOutstanding'])->name('reports.customer-outstanding');

        Route::resource('visits', \App\Http\Controllers\VisitController::class)->except('show');

        Route::get('work-types', [WorkTypeController::class, 'index'])->name('work-types.index');
        Route::post('work-types', [WorkTypeController::class, 'store'])->name('work-types.store');
        Route::post('work-types/quick', [WorkTypeController::class, 'quickStore'])->name('work-types.quick');
        Route::put('work-types/{workType}', [WorkTypeController::class, 'update'])->name('work-types.update');
        Route::delete('work-types/{workType}', [WorkTypeController::class, 'destroy'])->name('work-types.destroy');
    });

    /*
    | Admin portal. Internal users only; access within it is controlled by permissions.
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('dashboard', [Admin\DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');

        Route::resource('contractors', Admin\ContractorController::class);
        Route::patch('contractors/{contractor}/status', Admin\ContractorStatusController::class)->name('contractors.status');

        Route::get('clients', [Admin\ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/create', [Admin\ClientController::class, 'create'])->name('clients.create');
        Route::post('clients', [Admin\ClientController::class, 'store'])->name('clients.store');
        Route::post('clients/quick', [Admin\ClientController::class, 'quickStore'])->name('clients.quick');
        Route::get('clients/{client}', [Admin\ClientController::class, 'show'])->name('clients.show');
        Route::get('clients/{client}/edit', [Admin\ClientController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{client}', [Admin\ClientController::class, 'update'])->name('clients.update');
        Route::delete('clients/{client}', [Admin\ClientController::class, 'destroy'])->name('clients.destroy');
        Route::get('projects', [Admin\ProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/create', [Admin\ProjectController::class, 'create'])->name('projects.create');
        Route::post('projects', [Admin\ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}', [Admin\ProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/edit', [Admin\ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('projects/{project}', [Admin\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [Admin\ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::get('locations', Admin\ProjectLocationController::class)->name('locations.index');
        Route::get('works', Admin\WorkController::class)->name('works.index');

        Route::resource('users', Admin\UserController::class)->except('show');
        Route::patch('users/{user}/status', Admin\UserStatusController::class)->name('users.status');

        Route::resource('roles', Admin\RoleController::class)->except('show');
        Route::get('permissions', Admin\PermissionController::class)->name('permissions.index');

        Route::get('activity', Admin\ActivityController::class)->name('activity.index');

        Route::get('notifications', [Admin\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications', [Admin\NotificationController::class, 'send'])->name('notifications.send');
        Route::delete('notifications/devices', [Admin\NotificationController::class, 'clearDevices'])->name('notifications.devices.clear');
        Route::delete('notifications/devices/{user}', [Admin\NotificationController::class, 'removeDevice'])->name('notifications.devices.remove');

        Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});
