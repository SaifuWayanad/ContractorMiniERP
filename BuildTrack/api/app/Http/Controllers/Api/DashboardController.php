<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Money;
use App\Models\CustomerPayment;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalPurchaseValue = (float) Purchase::where('is_archived', false)->sum('line_total');
        $totalExpenses = (float) Expense::where('is_archived', false)->sum('amount');
        $totalReceipts = (float) CustomerPayment::where('is_voided', false)->sum('amount_received');

        $recentProjects = Project::with('customer')
            ->where('is_archived', false)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Project $p) => [
                'id' => $p->id,
                'projectName' => $p->project_name,
                'customerName' => $p->customer?->customer_name ?? '',
                'budget' => Money::of($p->budget),
                'createdAt' => $p->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'totalProjects' => Project::where('is_archived', false)->count(),
            'totalCustomers' => Customer::where('is_archived', false)->count(),
            'totalPurchaseValue' => $totalPurchaseValue,
            'totalExpenses' => $totalExpenses,
            'totalReceipts' => $totalReceipts,
            'totalOutstanding' => ($totalPurchaseValue + $totalExpenses) - $totalReceipts,
            'recentProjects' => $recentProjects,
        ]);
    }
}
