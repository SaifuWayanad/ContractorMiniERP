<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_id')->constrained('damages')->cascadeOnDelete();
            $table->foreignId('source_dispatch_detail_id')->constrained('dispatch_details');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 18, 3);
            $table->text('damage_description')->nullable();
            $table->decimal('estimated_repair_cost', 18, 2)->nullable();
            $table->enum('disposition', ['REPAIR', 'WRITE_OFF', 'RETURN_TO_AVAILABLE'])->default('REPAIR');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_details');
    }
};
