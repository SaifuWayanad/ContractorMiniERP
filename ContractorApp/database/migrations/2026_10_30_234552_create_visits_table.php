<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contractor_id');
            $table->unsignedBigInteger('client_id');
            $table->text('description')->nullable();
            $table->string('purpose', 200)->nullable();
            $table->date('meeting_date');
            $table->date('next_meeting_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('contractor_id')->references('id')->on('contractors');
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
