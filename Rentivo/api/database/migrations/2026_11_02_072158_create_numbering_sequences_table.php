<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('module')->unique();
            $table->string('prefix')->default('');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding_length')->default(6);
            $table->string('suffix')->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_sequences');
    }
};
