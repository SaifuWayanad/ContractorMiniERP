<?php

namespace App\Http\Controllers;

use App\Models\SalaryPaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryPaymentMethodController extends Controller
{
    public function quickStore(Request $request): JsonResponse
    {
        $this->authorize('create', SalaryPaymentMethod::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $method = SalaryPaymentMethod::firstOrCreate(['name' => $validated['name']]);

        return response()->json(['name' => $method->name], 201);
    }
}
