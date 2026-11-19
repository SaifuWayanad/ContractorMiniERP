<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\NumberingSequence;
use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRolesAndPermissions();
        $this->seedAdminUser();
        $this->seedNumberingSequences();
        $this->seedCompanyProfile();
    }

    private function seedRolesAndPermissions(): void
    {
        $roles = [
            'Administrator',
            'Store/Warehouse User',
            'Operations User',
            'Billing User',
            'Accounts User',
            'Project Manager',
            'Approver',
            'Read-only User',
        ];

        $permissions = [
            // Categories
            'categories.view', 'categories.create', 'categories.edit',
            // Products
            'products.view', 'products.create', 'products.edit',
            // Customers
            'customers.view', 'customers.create', 'customers.edit',
            // Locations
            'locations.view', 'locations.create', 'locations.edit',
            // Projects
            'projects.view', 'projects.create', 'projects.edit',
            // Inventory
            'inventory.view', 'inventory.create', 'inventory.post',
            // Dispatches
            'dispatches.view', 'dispatches.create', 'dispatches.edit', 'dispatches.submit', 'dispatches.approve', 'dispatches.post', 'dispatches.cancel',
            // Returns
            'returns.view', 'returns.create', 'returns.edit', 'returns.submit', 'returns.approve', 'returns.post',
            // Transfers
            'transfers.view', 'transfers.create', 'transfers.edit', 'transfers.submit', 'transfers.approve', 'transfers.post',
            // Losses
            'losses.view', 'losses.create', 'losses.approve', 'losses.post',
            // Invoices
            'invoices.view', 'invoices.create', 'invoices.post', 'invoices.cancel',
            // Payments
            'payments.view', 'payments.create', 'payments.post', 'payments.allocate',
            // Reports
            'reports.view',
            // Admin
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'settings.view', 'settings.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Give Administrator all permissions
        Role::findByName('Administrator', 'web')->syncPermissions(
            Permission::where('guard_name', 'web')->get()
        );

        // Read-only gets view permissions
        Role::findByName('Read-only User', 'web')->syncPermissions(
            Permission::where('name', 'like', '%.view')->where('guard_name', 'web')->get()
        );
    }

    private function seedAdminUser(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@rentivo.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles(['Administrator']);
    }

    private function seedNumberingSequences(): void
    {
        $sequences = [
            ['module' => 'OPENING', 'prefix' => 'OP-', 'next_number' => 1, 'padding_length' => 5],
            ['module' => 'DISPATCH', 'prefix' => 'DC-', 'next_number' => 1, 'padding_length' => 6],
            ['module' => 'RETURN', 'prefix' => 'RT-', 'next_number' => 1, 'padding_length' => 6],
            ['module' => 'TRANSFER', 'prefix' => 'TR-', 'next_number' => 1, 'padding_length' => 6],
            ['module' => 'LOSS', 'prefix' => 'LS-', 'next_number' => 1, 'padding_length' => 5],
            ['module' => 'DAMAGE', 'prefix' => 'DM-', 'next_number' => 1, 'padding_length' => 5],
            ['module' => 'INVOICE', 'prefix' => 'INV-', 'next_number' => 1, 'padding_length' => 6],
            ['module' => 'PAYMENT', 'prefix' => 'RCP-', 'next_number' => 1, 'padding_length' => 6],
            ['module' => 'CLOSURE', 'prefix' => 'CLS-', 'next_number' => 1, 'padding_length' => 5],
            ['module' => 'CREDIT_NOTE', 'prefix' => 'CN-', 'next_number' => 1, 'padding_length' => 5],
            ['module' => 'MOVEMENT', 'prefix' => 'MV-', 'next_number' => 1, 'padding_length' => 8],
        ];

        foreach ($sequences as $seq) {
            NumberingSequence::firstOrCreate(['module' => $seq['module']], $seq);
        }
    }

    private function seedCompanyProfile(): void
    {
        CompanyProfile::firstOrCreate(
            ['id' => 1],
            [
                'legal_name' => 'Rentivo Equipment Rentals Pvt Ltd',
                'address' => '123 Industrial Area, City',
                'phone' => '+91 98765 43210',
                'email' => 'info@rentivo.com',
                'timezone' => 'Asia/Kolkata',
                'day_count_convention' => 'INCLUSIVE',
                'require_full_return_invoicing' => true,
                'require_invoice_before_closure' => false,
                'allow_unpaid_closure' => true,
            ]
        );
    }
}
