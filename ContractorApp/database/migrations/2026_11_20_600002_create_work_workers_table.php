<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_workers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contractor_id');
            $table->unsignedBigInteger('work_id');
            $table->string('name', 200);
            $table->timestamps();

            $table->index(['work_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_workers');
    }
};
