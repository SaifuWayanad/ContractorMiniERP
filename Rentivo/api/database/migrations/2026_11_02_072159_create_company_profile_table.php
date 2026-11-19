<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profile', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->text('address')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->string('timezone')->default('UTC');
            $table->enum('day_count_convention', ['INCLUSIVE', 'EXCLUSIVE_START'])->default('INCLUSIVE');
            $table->boolean('require_full_return_invoicing')->default(true);
            $table->boolean('require_invoice_before_closure')->default(true);
            $table->boolean('allow_unpaid_closure')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profile');
    }
};
