<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_closure_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_closure_id')->constrained('project_closures')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('dispatched_quantity', 18, 3)->default(0);
            $table->decimal('returned_quantity', 18, 3)->default(0);
            $table->decimal('transferred_out_quantity', 18, 3)->default(0);
            $table->decimal('transferred_in_quantity', 18, 3)->default(0);
            $table->decimal('lost_quantity', 18, 3)->default(0);
            $table->decimal('written_off_quantity', 18, 3)->default(0);
            $table->decimal('outstanding_quantity', 18, 3)->default(0);
            $table->string('resolution_status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_closure_details');
    }
};
