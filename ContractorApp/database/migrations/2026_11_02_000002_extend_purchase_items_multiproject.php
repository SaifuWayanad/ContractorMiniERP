<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allow purchase_items to carry their own project and unit
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('purchase_id')->constrained()->nullOnDelete();
            $table->string('unit', 50)->nullable()->after('product_id');
            $table->string('supplier', 200)->nullable()->after('unit');
        });

        // Allow a purchase header to span multiple projects (no single project_id)
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_id', 'unit', 'supplier']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
        });
    }
};
