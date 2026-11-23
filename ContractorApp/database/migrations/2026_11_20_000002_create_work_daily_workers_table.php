<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_daily_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained('contractors')->cascadeOnDelete();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('morning_count')->default(0);
            $table->unsignedSmallInteger('evening_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['work_id', 'date']);
            $table->index(['contractor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_daily_workers');
    }
};
