<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily summaries are per user, not per project, so they have no project
     * and are deduplicated on the local date instead.
     */
    public function up(): void
    {
        Schema::table('push_notification_logs', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->change();
            $table->date('summary_date')->nullable()->after('meeting_at');
            $table->unique(['user_id', 'type', 'summary_date']);
        });
    }

    public function down(): void
    {
        Schema::table('push_notification_logs', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'type', 'summary_date']);
            $table->dropColumn('summary_date');
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};
