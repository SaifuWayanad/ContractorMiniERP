<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copies the data from the ASP.NET API's SQLite file (buildtrack.db) into this app's database.
 * IDs are kept so existing references (projects, purchases, payments) stay valid.
 */
class ImportLegacySqlite extends Command
{
    protected $signature = 'buildtrack:import-legacy
        {path : Path to the legacy buildtrack.db file}
        {--force : Empty the target tables first}';

    protected $description = 'Import data from the legacy ASP.NET SQLite database';

    /** Legacy table => target table, in dependency order. */
    private const TABLES = [
        'Locations' => 'locations',
        'Users' => 'users',
        'Customers' => 'customers',
        'Products' => 'products',
        'Projects' => 'projects',
        'Purchases' => 'purchases',
        'Expenses' => 'expenses',
        'CustomerPayments' => 'customer_payments',
    ];

    private const DATE_COLUMNS = [
        'created_at', 'updated_at', 'start_date', 'end_date', 'purchase_date',
        'expense_date', 'payment_date',
    ];

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("No file at {$path}");

            return self::FAILURE;
        }

        config(['database.connections.legacy_sqlite.database' => realpath($path)]);
        DB::purge('legacy_sqlite');

        if (! $this->option('force') && DB::table('users')->exists()) {
            $this->error('Target database already has users. Re-run with --force to replace its data.');

            return self::FAILURE;
        }

        DB::transaction(function (): void {
            if ($this->option('force')) {
                $this->emptyTargets();
            }

            foreach (self::TABLES as $legacy => $target) {
                $rows = DB::connection('legacy_sqlite')->table($legacy)->get();

                foreach ($rows->chunk(200) as $chunk) {
                    DB::table($target)->insert($chunk->map(fn ($row) => $this->convert((array) $row))->all());
                }

                $this->line(sprintf('  %-18s -> %-16s %d rows', $legacy, $target, $rows->count()));
            }
        });

        $this->info('Import finished.');

        return self::SUCCESS;
    }

    /** @param  array<string, mixed>  $row  @return array<string, mixed> */
    private function convert(array $row): array
    {
        $out = [];

        foreach ($row as $column => $value) {
            $key = Str::snake($column);

            if (in_array($key, self::DATE_COLUMNS, true) && $value !== null) {
                $value = Carbon::parse($value)->format('Y-m-d H:i:s');
            }

            // .NET's BCrypt writes "$2a$"; Laravel only accepts "$2y$" (same algorithm, same result for ASCII passwords).
            if ($key === 'password_hash' && str_starts_with((string) $value, '$2a$')) {
                $value = '$2y$'.substr($value, 4);
            }

            $out[$key] = $value;
        }

        return $out;
    }

    private function emptyTargets(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach (array_reverse(array_values(self::TABLES)) as $table) {
            DB::table($table)->delete();
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
