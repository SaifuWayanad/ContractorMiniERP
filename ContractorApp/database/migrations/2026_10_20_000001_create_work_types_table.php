<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->timestamps();

            $table->unique(['contractor_id', 'name']);
        });

        // Existing contractors get the default list; new contractors get it from Contractor::booted().
        $now = now();
        DB::table('contractors')->pluck('id')->each(function (int $contractorId) use ($now): void {
            DB::table('work_types')->insert(array_map(fn (string $name) => [
                'contractor_id' => $contractorId,
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ], ['Interior', 'Exterior', 'Outside', 'Completed']));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_types');
    }
};
