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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('contractor_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->foreignId('role_id')->nullable()->after('contractor_id')->constrained()->restrictOnDelete();
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('status', 20)->default('active')->after('password')->index();
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contractor_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['phone', 'status', 'last_login_at', 'deleted_at']);
        });
    }
};
