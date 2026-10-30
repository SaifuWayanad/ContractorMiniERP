<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount_received', 14, 2);
            $table->string('payment_method', 100)->nullable();
            $table->string('receipt_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contractor_id', 'project_id']);
            $table->index(['contractor_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_payments');
    }
};
