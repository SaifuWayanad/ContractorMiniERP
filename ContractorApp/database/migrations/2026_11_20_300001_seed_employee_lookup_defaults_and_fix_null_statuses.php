<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $contractorIds = DB::table('contractors')->pluck('id');

        foreach ($contractorIds as $contractorId) {
            // Seed default designations if none exist
            if (DB::table('employee_designations')->where('contractor_id', $contractorId)->doesntExist()) {
                $now = now();
                foreach (['Supervisor', 'Mason', 'Carpenter', 'Electrician', 'Plumber', 'Helper'] as $name) {
                    DB::table('employee_designations')->insertOrIgnore([
                        'contractor_id' => $contractorId,
                        'name'          => $name,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }

            // Seed default salary types if none exist
            if (DB::table('employee_salary_types')->where('contractor_id', $contractorId)->doesntExist()) {
                $now = now();
                foreach (['Daily', 'Monthly'] as $name) {
                    DB::table('employee_salary_types')->insertOrIgnore([
                        'contractor_id' => $contractorId,
                        'name'          => $name,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }

            // Seed default employee statuses if none exist
            if (DB::table('employee_statuses')->where('contractor_id', $contractorId)->doesntExist()) {
                $now = now();
                foreach (['Active', 'Inactive', 'On Leave', 'Terminated'] as $name) {
                    DB::table('employee_statuses')->insertOrIgnore([
                        'contractor_id' => $contractorId,
                        'name'          => $name,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        }

        // Fix any employees with null status — default them to Active
        DB::table('employees')->whereNull('status')->update(['status' => 'Active']);
    }

    public function down(): void
    {
        // Not reversible — data seeding migrations don't have a meaningful down
    }
};
