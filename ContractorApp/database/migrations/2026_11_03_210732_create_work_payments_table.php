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
        Schema::create('work_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method', 100)->nullable();
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });

        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_payments');

        Schema::table('works', function (Blueprint $table) {
            $table->string('payment_method', 100)->nullable()->after('amount');
        });
    }
};
