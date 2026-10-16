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
        Schema::table('projects', function (Blueprint $table) {
            $table->dateTime('next_meeting_at')->nullable()->after('notes');
            $table->string('next_meeting_notes', 250)->nullable()->after('next_meeting_at');
            $table->index(['contractor_id', 'next_meeting_at']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['contractor_id', 'next_meeting_at']);
            $table->dropColumn(['next_meeting_at', 'next_meeting_notes']);
        });
    }
};
