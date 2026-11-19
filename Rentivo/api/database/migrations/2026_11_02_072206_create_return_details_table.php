<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('dispatch_detail_id')->constrained('dispatch_details');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity_returned', 18, 3);
            $table->enum('condition', ['GOOD', 'DAMAGED', 'NEEDS_INSPECTION'])->default('GOOD');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('dispatch_detail_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_details');
    }
};
