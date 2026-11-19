<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->enum('location_type', ['WAREHOUSE', 'PROJECT']);
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity_on_hand', 18, 3)->default(0);
            $table->decimal('quantity_reserved', 18, 3)->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['product_id', 'location_type', 'location_id']);
            $table->index(['location_type', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
