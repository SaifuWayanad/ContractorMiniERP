<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('transfers')->cascadeOnDelete();
            $table->foreignId('source_dispatch_detail_id')->constrained('dispatch_details');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity_transferred', 18, 3);
            $table->decimal('minimum_rental_days_snapshot', 10, 2)->nullable();
            $table->decimal('minimum_rental_amount_snapshot', 18, 2)->nullable();
            $table->decimal('addon_amount_per_day_snapshot', 18, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_details');
    }
};
