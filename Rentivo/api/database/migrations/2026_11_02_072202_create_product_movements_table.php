<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number')->unique();
            $table->foreignId('product_id')->constrained('products');
            $table->enum('movement_type', [
                'OPENING', 'DISPATCH', 'RETURN', 'TRANSFER_OUT', 'TRANSFER_IN',
                'LOST', 'WRITE_OFF', 'ADJUSTMENT', 'REVERSAL',
            ]);
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_detail_id')->nullable();
            $table->enum('from_location_type', ['WAREHOUSE', 'PROJECT'])->nullable();
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->enum('to_location_type', ['WAREHOUSE', 'PROJECT'])->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->decimal('quantity', 18, 3);
            $table->date('movement_date');
            $table->unsignedBigInteger('reference_dispatch_detail_id')->nullable();
            $table->enum('status', ['POSTED', 'REVERSED'])->default('POSTED');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['product_id', 'movement_date']);
            $table->index(['source_type', 'source_id', 'source_detail_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_movements');
    }
};
