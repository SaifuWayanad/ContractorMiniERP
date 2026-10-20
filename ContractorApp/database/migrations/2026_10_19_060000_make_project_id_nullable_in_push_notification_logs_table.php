<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notification_logs', function (Blueprint $table): void {
            // Drop the existing FK so we can re-declare it as nullable.
            $table->dropForeign(['project_id']);
            $table->foreignId('project_id')->nullable()->change();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_logs', function (Blueprint $table): void {
            $table->dropForeign(['project_id']);
            $table->foreignId('project_id')->nullable(false)->change();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });
    }
};
