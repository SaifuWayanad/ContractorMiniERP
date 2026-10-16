<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AccessControlSeeder::class);

        Setting::putMany([
            'platform_name' => 'ContractorApp',
            'support_email' => 'support@contractorapp.test',
            'default_country' => 'India',
            'records_per_page' => '25',
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
