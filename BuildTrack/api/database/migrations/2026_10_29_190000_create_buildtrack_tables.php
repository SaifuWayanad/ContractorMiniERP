<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('password_hash');
            $table->string('full_name', 200);
            $table->string('email', 200)->default('');
            $table->string('role', 20)->default('Admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('location_name', 200);
            $table->string('address', 500)->default('');
            $table->string('city', 100)->default('');
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_name', 200);
            $table->string('phone_number', 50)->default('');
            $table->string('address', 500)->default('');
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('project_name', 200);
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('project_address', 500)->default('');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->decimal('budget', 18, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('product_name', 200);
            $table->string('unit_of_measure', 50)->default('');
            $table->decimal('default_unit_rate', 18, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->dateTime('purchase_date');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_amount', 18, 2);
            $table->decimal('line_total', 18, 2);
            $table->string('supplier', 200)->default('');
            $table->string('invoice_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->string('expense_heading', 200);
            $table->string('expense_description', 500)->default('');
            $table->dateTime('expense_date');
            $table->decimal('amount', 18, 2);
            $table->string('payment_method', 100)->default('');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        Schema::create('customer_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->restrictOnDelete();
            $table->dateTime('payment_date');
            $table->decimal('amount_received', 18, 2);
            $table->string('payment_method', 100)->default('');
            $table->string('receipt_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_voided')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('products');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('users');
    }
};
