<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contractor_id');
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['contractor_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_types');
    }
};
