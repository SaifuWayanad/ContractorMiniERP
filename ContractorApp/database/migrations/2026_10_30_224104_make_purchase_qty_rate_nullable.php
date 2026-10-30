<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('quantity', 14, 4)->nullable()->change();
            $table->decimal('unit_amount', 14, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('quantity', 14, 4)->default(1)->change();
            $table->decimal('unit_amount', 14, 2)->default(0)->change();
        });
    }
};
