<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function index(Request $request)
    {
        $api = $this->api();
        $payments = [];
        $error = null;

        try {
            $payments = $api->get('/payments', $request->only(['projectId', 'customerId', 'dateFrom', 'dateTo', 'page'])) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('payments.index', compact('payments', 'error'));
    }

    public function create(Request $request)
    {
        $api = $this->api();
        $projects = [];
        $customers = [];
        $preselectedProject = null;

        try {
            $projects = $api->get('/projects') ?? [];
            $customers = $api->get('/customers') ?? [];

            if ($request->filled('projectId')) {
                $preselectedProject = $api->get('/projects/' . $request->input('projectId')) ?? null;
            }
        } catch (\Throwable $e) {
            // Pass empty lists
        }

        return view('payments.create', compact('projects', 'customers', 'preselectedProject'));
    }

    public function store(Request $request)
    {
        $api = $this->api();

        try {
            $r = $request->except('_token');
            $result = $api->post('/payments', [
                'projectId'        => (int)($r['projectId'] ?? 0),
                'paymentDate'      => $r['date'] ?? null,
                'amountReceived'   => isset($r['amount']) ? (float)$r['amount'] : 0,
                'paymentMethod'    => $r['method'] ?? '',
                'receiptReference' => $r['reference'] ?: null,
                'notes'            => $r['notes'] ?: null,
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['error' => $result['conflict']])->withInput();
            }

            if (($r['from'] ?? '') === 'project' && !empty($r['fromProjectId'])) {
                return redirect()->route('projects.show', (int)$r['fromProjectId'])->with('success', 'Payment recorded successfully.');
            }
            return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(int $id, Request $request)
    {
        $api = $this->api();
        $payment = [];
        $projects = [];
        $customers = [];

        try {
            $payment = $api->get("/payments/{$id}") ?? [];
            $projects = $api->get('/projects') ?? [];
            $customers = $api->get('/customers') ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        $from = $request->query('from', '');
        $fromProjectId = $request->query('projectId', $payment['projectId'] ?? '');
        return view('payments.edit', compact('payment', 'projects', 'customers', 'from', 'fromProjectId'));
    }

    public function update(Request $request, int $id)
    {
        $api = $this->api();

        try {
            $r = $request->except(['_token', '_method']);
            $result = $api->put("/payments/{$id}", [
                'projectId'        => (int)($r['projectId'] ?? 0),
                'paymentDate'      => $r['date'] ?? null,
                'amountReceived'   => isset($r['amount']) ? (float)$r['amount'] : 0,
                'paymentMethod'    => $r['method'] ?? '',
                'receiptReference' => $r['reference'] ?: null,
                'notes'            => $r['notes'] ?: null,
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['error' => $result['conflict']])->withInput();
            }

            if (($r['from'] ?? '') === 'project' && !empty($r['fromProjectId'])) {
                return redirect()->route('projects.show', (int)$r['fromProjectId'])->with('success', 'Payment updated successfully.');
            }
            return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');
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
            $result = $api->delete("/payments/{$id}");
            if (is_array($result) && isset($result['conflict'])) {
                return back()->with('error', $result['conflict']);
            }
            return redirect()->route('payments.index')->with('success', 'Payment deleted.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
