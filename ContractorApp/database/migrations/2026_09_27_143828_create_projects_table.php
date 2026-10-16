<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('dealer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('project_number', 30);
            $table->string('type_of_work', 80)->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('status', 20)->default('planning');
            $table->decimal('budget', 14, 2)->nullable();
            $table->string('brand_preferred', 80)->nullable();
            $table->unsignedInteger('expected_qty')->nullable();
            $table->date('expected_maturity_date')->nullable();
            $table->string('specification_details', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contractor_id', 'status']);
            $table->index(['contractor_id', 'created_at']);
            $table->index(['contractor_id', 'expected_end_date']);
            $table->index(['contractor_id', 'project_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
