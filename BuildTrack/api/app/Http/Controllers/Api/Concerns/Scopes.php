<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;

trait Scopes
{
    protected function boolQuery(Request $request, string $key): bool
    {
        return filter_var($request->query($key, false), FILTER_VALIDATE_BOOLEAN);
    }
}
