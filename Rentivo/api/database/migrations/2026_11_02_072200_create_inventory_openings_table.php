<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_openings', function (Blueprint $table) {
            $table->id();
            $table->string('opening_number')->unique();
            $table->foreignId('location_id')->constrained('locations');
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('quantity', 18, 3);
            $table->date('opening_date');
            $table->enum('status', ['DRAFT', 'POSTED', 'CANCELLED'])->default('DRAFT');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_openings');
    }
};
