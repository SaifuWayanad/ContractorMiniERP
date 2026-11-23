<?php

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeePortalSettingsController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeDesignationController;
use App\Http\Controllers\EmployeeLoginController;
use App\Http\Controllers\EmployeeSalaryTypeController;
use App\Http\Controllers\EmployeeStatusOptionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProjectEmployeeController;
use App\Http\Controllers\WorkDailyWorkerController;
use App\Http\Controllers\WorkCollectionController;
use App\Http\Controllers\WorkExpenseController;
use App\Http\Controllers\WorkWorkerController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectPaymentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryPaymentController;
use App\Http\Controllers\SalaryPaymentMethodController;
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

        Route::get('employee-portal-settings', [EmployeePortalSettingsController::class, 'edit'])->name('employee-portal-settings.edit');
        Route::put('employee-portal-settings', [EmployeePortalSettingsController::class, 'update'])->name('employee-portal-settings.update');

        Route::resource('clients', ClientController::class);
        Route::post('clients/quick', [ClientController::class, 'quickStore'])->name('clients.quick');
        Route::resource('projects', ProjectController::class);
        Route::patch('projects/{project}/status', ProjectStatusController::class)->name('projects.status');
        Route::patch('projects/{project}/meeting', [ProjectController::class, 'updateMeeting'])->name('projects.meeting');
        Route::post('projects/{project}/meetings', [ProjectMeetingController::class, 'store'])->name('projects.meetings.store');
        Route::delete('projects/{project}/meetings/{meeting}', [ProjectMeetingController::class, 'destroy'])->name('projects.meetings.destroy');
        Route::get('projects/{project}/purchases/add', [ProjectController::class, 'addPurchases'])->name('projects.add-purchases');
        Route::post('projects/{project}/purchases/add', [ProjectController::class, 'storePurchases'])->name('projects.store-purchases');
        Route::get('projects/{project}/purchases/{purchase}/edit', [ProjectController::class, 'editPurchase'])->name('projects.edit-purchase');
        Route::put('projects/{project}/purchases/{purchase}', [ProjectController::class, 'updatePurchase'])->name('projects.update-purchase');
        Route::get('locations', [ProjectLocationController::class, 'index'])->name('locations.index');
        Route::get('locations/create', [ProjectLocationController::class, 'chooseProject'])->name('locations.create');
        Route::get('projects/{project}/locations', [ProjectLocationController::class, 'forProject'])->name('projects.locations.index');
        Route::resource('projects.locations', ProjectLocationController::class)->shallow()->except('index');

        Route::resource('works', WorkController::class);
        Route::patch('works/{work}/status', WorkStatusController::class)->name('works.status');

        Route::post('products/quick', [ProductController::class, 'quickStore'])->name('products.quick-store');
        Route::resource('products', ProductController::class)->except('show');
        Route::post('purchases/batch', [PurchaseController::class, 'storeBatch'])->name('purchases.storeBatch');
        Route::get('purchases/{purchase}/edit-batch', [PurchaseController::class, 'editBatch'])->name('purchases.editBatch');
        Route::put('purchases/{purchase}/update-batch', [PurchaseController::class, 'updateBatch'])->name('purchases.updateBatch');
        Route::resource('purchases', PurchaseController::class);
        Route::resource('expenses', ExpenseController::class)->except('show');
        Route::resource('payments', ProjectPaymentController::class)->except('show');

        Route::get('reports/outstanding', [ReportController::class, 'outstanding'])->name('reports.outstanding');
        Route::get('reports/customer-outstanding', [ReportController::class, 'customerOutstanding'])->name('reports.customer-outstanding');
        Route::get('reports/collections', [ReportController::class, 'collections'])->name('reports.collections');
        Route::get('reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
        Route::get('reports/employees', [ReportController::class, 'employees'])->name('reports.employees');
        Route::get('reports/employee-performance', [ReportController::class, 'employeePerformance'])->name('reports.employee-performance');

        Route::resource('employees', EmployeeController::class);
        Route::patch('employees/{employee}/status', [EmployeeController::class, 'updateStatus'])->name('employees.update-status');
        Route::get('employees/{employee}/login/create', [EmployeeLoginController::class, 'create'])->name('employees.login.create');
        Route::post('employees/{employee}/login', [EmployeeLoginController::class, 'store'])->name('employees.login.store');
        Route::delete('employees/{employee}/login', [EmployeeLoginController::class, 'destroy'])->name('employees.login.destroy');
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/calendar-view', [AttendanceController::class, 'calendarView'])->name('attendance.calendar-view');
        Route::get('attendance/calendar', [AttendanceController::class, 'calendarData'])->name('attendance.calendar');
        Route::get('attendance/mark', [AttendanceController::class, 'mark'])->name('attendance.mark');
        Route::post('attendance/mark', [AttendanceController::class, 'saveMark'])->name('attendance.save-mark');
        Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::get('salary-payments/bulk', [SalaryPaymentController::class, 'bulk'])->name('salary-payments.bulk');
        Route::post('salary-payments/bulk', [SalaryPaymentController::class, 'saveBulk'])->name('salary-payments.bulk.save');
        Route::resource('salary-payments', SalaryPaymentController::class)->except('show');
        Route::post('salary-payment-methods/quick', [SalaryPaymentMethodController::class, 'quickStore'])->name('salary-payment-methods.quick');

        Route::post('projects/{project}/employees/sync', [ProjectEmployeeController::class, 'sync'])->name('projects.employees.sync');
        Route::post('works/{work}/daily-workers', [WorkDailyWorkerController::class, 'store'])->name('works.daily-workers.store');
        Route::delete('works/{work}/daily-workers/{worker}', [WorkDailyWorkerController::class, 'destroy'])->name('works.daily-workers.destroy');
        Route::post('works/{work}/collections', [WorkCollectionController::class, 'store'])->name('works.collections.store');
        Route::delete('works/{work}/collections/{payment}', [WorkCollectionController::class, 'destroy'])->name('works.collections.destroy');
        Route::post('works/{work}/expenses', [WorkExpenseController::class, 'store'])->name('works.expenses.store');
        Route::delete('works/{work}/expenses/{expense}', [WorkExpenseController::class, 'destroy'])->name('works.expenses.destroy');
        Route::post('works/{work}/workers', [WorkWorkerController::class, 'store'])->name('works.workers.store');
        Route::delete('works/{work}/workers/{worker}', [WorkWorkerController::class, 'destroy'])->name('works.workers.destroy');

        Route::resource('visits', VisitController::class)->except('show');

        Route::get('work-types', [WorkTypeController::class, 'index'])->name('work-types.index');
        Route::post('work-types', [WorkTypeController::class, 'store'])->name('work-types.store');
        Route::post('work-types/quick', [WorkTypeController::class, 'quickStore'])->name('work-types.quick');
        Route::put('work-types/{workType}', [WorkTypeController::class, 'update'])->name('work-types.update');
        Route::delete('work-types/{workType}', [WorkTypeController::class, 'destroy'])->name('work-types.destroy');

        Route::get('employee-designations', [EmployeeDesignationController::class, 'index'])->name('employee-designations.index');
        Route::post('employee-designations', [EmployeeDesignationController::class, 'store'])->name('employee-designations.store');
        Route::post('employee-designations/quick', [EmployeeDesignationController::class, 'quickStore'])->name('employee-designations.quick');
        Route::put('employee-designations/{employeeDesignation}', [EmployeeDesignationController::class, 'update'])->name('employee-designations.update');
        Route::delete('employee-designations/{employeeDesignation}', [EmployeeDesignationController::class, 'destroy'])->name('employee-designations.destroy');

        Route::get('employee-salary-types', [EmployeeSalaryTypeController::class, 'index'])->name('employee-salary-types.index');
        Route::post('employee-salary-types', [EmployeeSalaryTypeController::class, 'store'])->name('employee-salary-types.store');
        Route::post('employee-salary-types/quick', [EmployeeSalaryTypeController::class, 'quickStore'])->name('employee-salary-types.quick');
        Route::put('employee-salary-types/{employeeSalaryType}', [EmployeeSalaryTypeController::class, 'update'])->name('employee-salary-types.update');
        Route::delete('employee-salary-types/{employeeSalaryType}', [EmployeeSalaryTypeController::class, 'destroy'])->name('employee-salary-types.destroy');

        Route::get('employee-statuses', [EmployeeStatusOptionController::class, 'index'])->name('employee-statuses.index');
        Route::post('employee-statuses', [EmployeeStatusOptionController::class, 'store'])->name('employee-statuses.store');
        Route::post('employee-statuses/quick', [EmployeeStatusOptionController::class, 'quickStore'])->name('employee-statuses.quick');
        Route::put('employee-statuses/{employeeStatusOption}', [EmployeeStatusOptionController::class, 'update'])->name('employee-statuses.update');
        Route::delete('employee-statuses/{employeeStatusOption}', [EmployeeStatusOptionController::class, 'destroy'])->name('employee-statuses.destroy');
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
