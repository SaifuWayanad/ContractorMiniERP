<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change ENUM → VARCHAR to allow dynamic values
        DB::statement('ALTER TABLE employees MODIFY salary_type VARCHAR(100) NULL');
        DB::statement('ALTER TABLE employees MODIFY status VARCHAR(100) NULL');

        // Capitalise existing data: 'daily' → 'Daily', 'active' → 'Active', etc.
        DB::statement("UPDATE employees SET salary_type = CONCAT(UPPER(LEFT(salary_type, 1)), SUBSTRING(salary_type, 2)) WHERE salary_type IS NOT NULL");
        DB::statement("UPDATE employees SET status = CONCAT(UPPER(LEFT(status, 1)), SUBSTRING(status, 2)) WHERE status IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE employees MODIFY salary_type ENUM('daily','monthly') DEFAULT 'daily'");
        DB::statement("ALTER TABLE employees MODIFY status ENUM('active','inactive') DEFAULT 'active'");
    }
};
