<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function index(Request $request)
    {
        $api = $this->api();
        $purchases = [];
        $error = null;

        try {
            $purchases = $api->get('/purchases', $request->only(['projectId', 'customerId', 'dateFrom', 'dateTo', 'page'])) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('purchases.index', compact('purchases', 'error'));
    }

    public function create(Request $request)
    {
        $api = $this->api();
        $projects = [];
        $products = [];
        $preselectedProject = null;

        try {
            $projects = $api->get('/projects') ?? [];
            $products = $api->get('/products') ?? [];

            if ($request->filled('projectId')) {
                $preselectedProject = $api->get('/projects/' . $request->input('projectId')) ?? null;
            }
        } catch (\Throwable $e) {
            // Pass empty lists
        }

        return view('purchases.create', compact('projects', 'products', 'preselectedProject'));
    }

    public function store(Request $request)
    {
        $api = $this->api();

        try {
            $r = $request->except('_token');
            $result = $api->post('/purchases', [
                'projectId'        => (int)($r['projectId'] ?? 0),
                'productId'        => (int)($r['productId'] ?? 0),
                'purchaseDate'     => $r['date'] ?? null,
                'quantity'         => isset($r['quantity']) ? (float)$r['quantity'] : 0,
                'unitAmount'       => isset($r['unitRate']) ? (float)$r['unitRate'] : 0,
                'lineTotal'        => isset($r['lineTotal']) && $r['lineTotal'] !== '' ? (float)$r['lineTotal'] : null,
                'supplier'         => $r['supplier'] ?? '',
                'invoiceReference' => $r['reference'] ?: null,
                'notes'            => $r['notes'] ?: null,
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['error' => $result['conflict']])->withInput();
            }

            if (($r['from'] ?? '') === 'project' && !empty($r['fromProjectId'])) {
                return redirect()->route('projects.show', (int)$r['fromProjectId'])->with('success', 'Purchase recorded successfully.');
            }
            return redirect()->route('purchases.index')->with('success', 'Purchase recorded successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(int $id, Request $request)
    {
        $api = $this->api();
        $purchase = [];
        $projects = [];
        $products = [];

        try {
            $purchase = $api->get("/purchases/{$id}") ?? [];
            $projects = $api->get('/projects') ?? [];
            $products = $api->get('/products') ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        $from = $request->query('from', '');
        $fromProjectId = $request->query('projectId', $purchase['projectId'] ?? '');
        return view('purchases.edit', compact('purchase', 'projects', 'products', 'from', 'fromProjectId'));
    }

    public function update(Request $request, int $id)
    {
        $api = $this->api();

        try {
            $r = $request->except(['_token', '_method']);
            $result = $api->put("/purchases/{$id}", [
                'projectId'        => (int)($r['projectId'] ?? 0),
                'productId'        => (int)($r['productId'] ?? 0),
                'purchaseDate'     => $r['date'] ?? null,
                'quantity'         => isset($r['quantity']) ? (float)$r['quantity'] : 0,
                'unitAmount'       => isset($r['unitRate']) ? (float)$r['unitRate'] : 0,
                'lineTotal'        => isset($r['lineTotal']) && $r['lineTotal'] !== '' ? (float)$r['lineTotal'] : null,
                'supplier'         => $r['supplier'] ?? '',
                'invoiceReference' => $r['reference'] ?: null,
                'notes'            => $r['notes'] ?: null,
                'isArchived'       => false,
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['error' => $result['conflict']])->withInput();
            }

            if (($r['from'] ?? '') === 'project' && !empty($r['fromProjectId'])) {
                return redirect()->route('projects.show', (int)$r['fromProjectId'])->with('success', 'Purchase updated successfully.');
            }
            return redirect()->route('purchases.index')->with('success', 'Purchase updated successfully.');
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
            $result = $api->delete("/purchases/{$id}");
            if (is_array($result) && isset($result['conflict'])) {
                return back()->with('error', $result['conflict']);
            }
            return redirect()->route('purchases.index')->with('success', 'Purchase deleted.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
