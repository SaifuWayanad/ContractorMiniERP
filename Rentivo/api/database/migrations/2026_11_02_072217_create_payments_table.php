<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('payment_date');
            $table->enum('payment_method', ['CASH', 'BANK', 'UPI', 'CHEQUE', 'OTHER'])->default('BANK');
            $table->string('reference_number')->nullable();
            $table->decimal('amount', 18, 2);
            $table->decimal('allocated_amount', 18, 2)->default(0);
            $table->enum('status', ['DRAFT', 'POSTED', 'CANCELLED'])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();

            $table->index(['customer_id', 'payment_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
