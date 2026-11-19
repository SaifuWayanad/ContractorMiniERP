<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('dispatches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 18, 3);
            $table->decimal('minimum_rental_days_snapshot', 10, 2);
            $table->decimal('minimum_rental_amount_snapshot', 18, 2);
            $table->decimal('addon_amount_per_day_snapshot', 18, 2);
            $table->string('unit_of_measure_snapshot');
            $table->decimal('tax_rate_snapshot', 8, 4)->nullable();
            $table->date('expected_return_date')->nullable();
            $table->text('rate_override_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_details');
    }
};
