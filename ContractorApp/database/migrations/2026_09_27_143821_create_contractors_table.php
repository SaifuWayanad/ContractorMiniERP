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
        Schema::create('contractors', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150);
            $table->string('contact_person', 100);
            $table->string('email', 150);
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->string('address', 250)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('country', 80)->default('India');
            $table->string('postal_code', 12)->nullable();
            $table->string('website', 200)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('tax_number', 30)->nullable();
            $table->string('license_number', 50)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractors');
    }
};
