<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('contractor_id');
            $table->string('name', 100);
            $table->timestamps();

            $table->unique(['contractor_id', 'name']);
        });

        // Seed defaults for all existing contractors
        $contractorIds = DB::table('contractors')->pluck('id');
        foreach ($contractorIds as $contractorId) {
            $now = now();
            foreach (['Cash', 'Bank Transfer', 'UPI', 'Cheque'] as $name) {
                DB::table('salary_payment_methods')->insertOrIgnore([
                    'contractor_id' => $contractorId,
                    'name'          => $name,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payment_methods');
    }
};
