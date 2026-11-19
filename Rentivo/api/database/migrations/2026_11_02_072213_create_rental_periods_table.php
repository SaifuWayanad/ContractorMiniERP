<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_dispatch_detail_id')->constrained('dispatch_details');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 18, 3);
            $table->date('rental_start_date');
            $table->date('rental_end_date')->nullable();
            $table->enum('end_reason', ['RETURN', 'TRANSFER', 'LOST', 'WRITE_OFF'])->nullable();
            $table->foreignId('parent_rental_period_id')->nullable()->constrained('rental_periods')->nullOnDelete();
            $table->decimal('minimum_rental_days_snapshot', 10, 2);
            $table->decimal('minimum_rental_amount_snapshot', 18, 2);
            $table->decimal('addon_amount_per_day_snapshot', 18, 2);
            $table->decimal('tax_rate_snapshot', 8, 4)->nullable();
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['source_dispatch_detail_id', 'status']);
            $table->index(['project_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_periods');
    }
};
