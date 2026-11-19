<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit_of_measure');
            $table->enum('tracking_method', ['QUANTITY', 'SERIALIZED'])->default('QUANTITY');
            $table->decimal('minimum_rental_days', 10, 2)->default(1);
            $table->decimal('minimum_rental_amount', 18, 2)->default(0);
            $table->decimal('addon_amount_per_day', 18, 2)->default(0);
            $table->foreignId('default_tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
