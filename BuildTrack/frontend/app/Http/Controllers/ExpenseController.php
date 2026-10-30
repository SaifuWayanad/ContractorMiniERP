<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function index(Request $request)
    {
        $api = $this->api();
        $expenses = [];
        $error = null;

        try {
            $expenses = $api->get('/expenses', $request->only(['projectId', 'customerId', 'dateFrom', 'dateTo', 'page'])) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('expenses.index', compact('expenses', 'error'));
    }

    public function create(Request $request)
    {
        $api = $this->api();
        $projects = [];
        $preselectedProject = null;

        try {
            $projects = $api->get('/projects') ?? [];

            if ($request->filled('projectId')) {
                $preselectedProject = $api->get('/projects/' . $request->input('projectId')) ?? null;
            }
        } catch (\Throwable $e) {
            // Pass empty lists
        }

        return view('expenses.create', compact('projects', 'preselectedProject'));
    }

    public function store(Request $request)
    {
        $api = $this->api();

        try {
            $r = $request->except('_token');
            $result = $api->post('/expenses', [
                'projectId'          => (int)($r['projectId'] ?? 0),
                'expenseHeading'     => $r['heading'] ?? '',
                'expenseDescription' => $r['category'] ?? '',
                'expenseDate'        => $r['date'] ?? null,
                'amount'             => isset($r['amount']) ? (float)$r['amount'] : 0,
                'paymentMethod'      => '',
                'referenceNumber'    => $r['reference'] ?: null,
                'notes'              => $r['notes'] ?: null,
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['error' => $result['conflict']])->withInput();
            }

            $projectId = $result['projectId'] ?? $request->input('projectId');
            if ($projectId) {
                return redirect()->route('projects.show', $projectId)->with('success', 'Expense recorded successfully.');
            }
            return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(int $id)
    {
        $api = $this->api();
        $expense = [];
        $projects = [];

        try {
            $expense = $api->get("/expenses/{$id}") ?? [];
            $projects = $api->get('/projects') ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('expenses.edit', compact('expense', 'projects'));
    }

    public function update(Request $request, int $id)
    {
        $api = $this->api();

        try {
            $r = $request->except(['_token', '_method']);
            $result = $api->put("/expenses/{$id}", [
                'projectId'          => (int)($r['projectId'] ?? 0),
                'expenseHeading'     => $r['heading'] ?? '',
                'expenseDescription' => $r['category'] ?? '',
                'expenseDate'        => $r['date'] ?? null,
                'amount'             => isset($r['amount']) ? (float)$r['amount'] : 0,
                'paymentMethod'      => '',
                'referenceNumber'    => $r['reference'] ?: null,
                'notes'              => $r['notes'] ?: null,
                'isArchived'         => false,
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['error' => $result['conflict']])->withInput();
            }

            return redirect()->route('expenses.index')->with('success', 'Expense updated successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(int $id)
    {
        $api = $this->api();

        try {
            $result = $api->delete("/expenses/{$id}");
            if (is_array($result) && isset($result['conflict'])) {
                return back()->with('error', $result['conflict']);
            }
            return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
