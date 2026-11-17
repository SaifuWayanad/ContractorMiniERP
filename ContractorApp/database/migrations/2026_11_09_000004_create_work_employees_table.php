<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_employees', function (Blueprint $table) {
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->primary(['work_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_employees');
    }
};
