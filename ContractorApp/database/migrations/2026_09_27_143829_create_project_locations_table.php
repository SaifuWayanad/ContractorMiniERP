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
        Schema::create('project_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('address', 250);
            $table->string('city', 80);
            $table->string('district', 80)->nullable();
            $table->string('state', 80);
            $table->string('country', 80)->default('India');
            $table->string('postal_code', 12)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contractor_id', 'state', 'district']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_locations');
    }
};
