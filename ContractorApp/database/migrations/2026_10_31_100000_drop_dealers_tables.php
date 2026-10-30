<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dealer_id');
        });

        Schema::dropIfExists('project_dealer');
        Schema::dropIfExists('dealers');
    }

    public function down(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contractor_id');
            $table->string('name', 120);
            $table->string('city', 80)->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_dealer', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('dealer_id');
            $table->timestamps();
            $table->primary(['project_id', 'dealer_id']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('dealer_id')->nullable()->after('client_id');
        });
    }
};
