<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contractor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('meeting_at');
            $table->string('purpose', 500);
            $table->timestamps();

            $table->index(['contractor_id', 'meeting_at']);
            $table->index(['project_id', 'meeting_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_meetings');
    }
};
