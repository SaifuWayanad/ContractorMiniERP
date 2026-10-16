<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_dealer', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'dealer_id']);
            $table->timestamps();
        });

        // Migrate existing dealer_id data into the pivot table
        DB::table('projects')
            ->whereNotNull('dealer_id')
            ->orderBy('id')
            ->each(function ($project) {
                DB::table('project_dealer')->insertOrIgnore([
                    'project_id' => $project->id,
                    'dealer_id'  => $project->dealer_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_dealer');
    }
};
