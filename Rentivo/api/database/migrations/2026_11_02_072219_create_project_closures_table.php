<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_closures', function (Blueprint $table) {
            $table->id();
            $table->string('closure_number')->unique();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('customer_id')->constrained('customers');
            $table->date('initiated_date');
            $table->date('closure_date')->nullable();
            $table->enum('status', [
                'DRAFT', 'RECONCILIATION', 'PENDING_INVOICING',
                'READY_FOR_APPROVAL', 'CLOSED', 'CANCELLED',
            ])->default('DRAFT');
            $table->decimal('total_dispatched_quantity', 18, 3)->default(0);
            $table->decimal('total_returned_quantity', 18, 3)->default(0);
            $table->decimal('total_transferred_out_quantity', 18, 3)->default(0);
            $table->decimal('total_lost_quantity', 18, 3)->default(0);
            $table->decimal('total_damaged_quantity', 18, 3)->default(0);
            $table->decimal('total_invoiced_amount', 18, 2)->default(0);
            $table->decimal('total_paid_amount', 18, 2)->default(0);
            $table->decimal('financial_outstanding', 18, 2)->default(0);
            $table->text('closure_notes')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_closures');
    }
};
