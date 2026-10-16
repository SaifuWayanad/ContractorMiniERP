<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Page size for lists, from the admin Settings screen.
     */
    protected function perPage(): int
    {
        return max(5, min(100, (int) Setting::get('records_per_page', '25')));
    }

    /**
     * A Y-m-d date from the query string, or null when missing or malformed.
     */
    protected function dateInput(Request $request, string $key): ?string
    {
        $value = $request->string($key)->value();

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }
}
