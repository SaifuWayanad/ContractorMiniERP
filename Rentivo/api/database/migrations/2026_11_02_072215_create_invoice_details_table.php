<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('return_detail_id')->nullable()->constrained('return_details')->nullOnDelete();
            $table->foreignId('rental_period_id')->nullable()->constrained('rental_periods')->nullOnDelete();
            $table->foreignId('dispatch_detail_id')->nullable()->constrained('dispatch_details')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('description_snapshot');
            $table->decimal('quantity', 18, 3);
            $table->integer('rental_days');
            $table->decimal('minimum_days_snapshot', 10, 2);
            $table->decimal('minimum_amount_snapshot', 18, 2);
            $table->decimal('addon_per_day_snapshot', 18, 2);
            $table->decimal('unit_amount', 18, 2);
            $table->decimal('line_amount', 18, 2);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_rate_snapshot', 8, 4)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
