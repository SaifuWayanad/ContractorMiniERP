<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loss_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loss_id')->constrained('losses')->cascadeOnDelete();
            $table->foreignId('source_dispatch_detail_id')->constrained('dispatch_details');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity_lost', 18, 3);
            $table->decimal('recovery_amount', 18, 2)->nullable();
            $table->foreignId('recovery_tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loss_details');
    }
};
