<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('company', 150)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 250)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('country', 80)->default('India');
            $table->string('postal_code', 12)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contractor_id', 'status']);
            $table->index(['contractor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
