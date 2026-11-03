<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 14, 4)->default(1);
            $table->decimal('unit_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->timestamps();

            $table->index('purchase_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            // total_amount stores the sum of all purchase_items; null means single-item (use line_total)
            $table->decimal('total_amount', 14, 2)->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });

        Schema::dropIfExists('purchase_items');
    }
};
