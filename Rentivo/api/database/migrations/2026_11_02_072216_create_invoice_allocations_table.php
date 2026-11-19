<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_detail_id')->constrained('return_details');
            $table->foreignId('invoice_detail_id')->constrained('invoice_details')->cascadeOnDelete();
            $table->decimal('quantity_invoiced', 18, 3);
            $table->decimal('amount_allocated', 18, 2);
            $table->timestamps();

            $table->index('return_detail_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_allocations');
    }
};
