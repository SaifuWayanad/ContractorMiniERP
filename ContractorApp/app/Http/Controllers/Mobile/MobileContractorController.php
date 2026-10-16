<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use Illuminate\Http\JsonResponse;

class MobileContractorController extends Controller
{
    public function index(): JsonResponse
    {
        $contractors = Contractor::query()
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'contact_person'])
            ->map(fn (Contractor $c) => [
                'id'   => $c->id,
                'name' => $c->company_name,
            ]);

        return response()->json($contractors);
    }
}
