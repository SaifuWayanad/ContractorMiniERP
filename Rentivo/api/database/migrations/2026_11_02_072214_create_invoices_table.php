<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->enum('invoice_type', ['RENTAL', 'LOSS_RECOVERY', 'DAMAGE_RECOVERY', 'ADJUSTMENT'])->default('RENTAL');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('amount_paid', 18, 2)->default(0);
            $table->enum('status', ['DRAFT', 'POSTED', 'PART_PAID', 'PAID', 'CANCELLED'])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();

            $table->index(['customer_id', 'invoice_date', 'status']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
