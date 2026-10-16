<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'day_before' | 'hour_before'
            $table->dateTime('meeting_at');
            $table->timestamps();

            // Prevent duplicate notifications for the same user/project/type/meeting-time
            $table->unique(['user_id', 'project_id', 'type', 'meeting_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_logs');
    }
};
