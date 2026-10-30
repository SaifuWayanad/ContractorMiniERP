<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function index(Request $request)
    {
        $api = $this->api();
        $projects = [];
        $error = null;

        try {
            $projects = $api->get('/projects', $request->only(['search', 'customerId', 'locationId', 'page'])) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('projects.index', compact('projects', 'error'));
    }

    public function create()
    {
        $api = $this->api();
        $customers = [];
        $locations = [];

        try {
            $customers = $api->get('/customers') ?? [];
            $locations = $api->get('/locations') ?? [];
        } catch (\Throwable $e) {
            // Pass empty lists
        }

        return view('projects.create', compact('customers', 'locations'));
    }

    public function store(Request $request)
    {
        $api = $this->api();

        try {
            $raw = $request->only(['name', 'customerId', 'locationId', 'address', 'startDate', 'endDate', 'budget', 'description', 'assignedUserId']);
            $data = array_filter([
                'projectName'    => $raw['name'] ?? '',
                'customerId'     => $raw['customerId'] ?? null,
                'locationId'     => $raw['locationId'] ?: null,
                'projectAddress' => $raw['address'] ?? '',
                'startDate'      => $raw['startDate'] ?: null,
                'endDate'        => $raw['endDate'] ?: null,
                'budget'         => isset($raw['budget']) && $raw['budget'] !== '' ? (float)$raw['budget'] : null,
                'description'    => $raw['description'] ?? null,
                'assignedUserId' => $raw['assignedUserId'] ?? null,
            ], fn($v) => $v !== null);
            $data['projectName'] = $raw['name'] ?? '';
            $data['customerId']  = (int)($raw['customerId'] ?? 0);
            $result = $api->post('/projects', $data);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['name' => $result['conflict']])->withInput();
            }

            $id = $result['id'] ?? null;
            return redirect()->route('projects.show', $id)->with('success', 'Project created successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(int $id)
    {
        $api = $this->api();
        $project = [];
        $purchases = [];
        $expenses = [];
        $payments = [];
        $summary = [];
        $error = null;

        try {
            $project = $api->get("/projects/{$id}") ?? [];
            $purchases = $api->get('/purchases', ['projectId' => $id]) ?? [];
            $expenses = $api->get('/expenses', ['projectId' => $id]) ?? [];
            $payments = $api->get('/payments', ['projectId' => $id]) ?? [];
            $summary = $api->get("/projects/{$id}/summary") ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('projects.show', compact('project', 'purchases', 'expenses', 'payments', 'summary', 'error'));
    }

    public function edit(int $id)
    {
        $api = $this->api();
        $project = [];
        $customers = [];
        $locations = [];

        try {
            $project = $api->get("/projects/{$id}") ?? [];
            $customers = $api->get('/customers') ?? [];
            $locations = $api->get('/locations') ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('projects.edit', compact('project', 'customers', 'locations'));
    }

    public function update(Request $request, int $id)
    {
        $api = $this->api();

        try {
            $raw = $request->only(['name', 'customerId', 'locationId', 'address', 'startDate', 'endDate', 'budget', 'description', 'assignedUserId', 'isArchived']);
            $data = [
                'projectName'    => $raw['name'] ?? '',
                'customerId'     => (int)($raw['customerId'] ?? 0),
                'locationId'     => $raw['locationId'] ? (int)$raw['locationId'] : null,
                'projectAddress' => $raw['address'] ?? '',
                'startDate'      => $raw['startDate'] ?: null,
                'endDate'        => $raw['endDate'] ?: null,
                'budget'         => isset($raw['budget']) && $raw['budget'] !== '' ? (float)$raw['budget'] : null,
                'description'    => $raw['description'] ?? null,
                'assignedUserId' => !empty($raw['assignedUserId']) ? (int)$raw['assignedUserId'] : null,
                'isArchived'     => isset($raw['isArchived']) ? (bool)$raw['isArchived'] : false,
            ];
            $result = $api->put("/projects/{$id}", $data);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['name' => $result['conflict']])->withInput();
            }

            return redirect()->route('projects.show', $id)->with('success', 'Project updated successfully.');
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
            $result = $api->delete("/projects/{$id}");
            if (is_array($result) && isset($result['conflict'])) {
                return back()->with('error', $result['conflict']);
            }
            return redirect()->route('projects.index')->with('success', 'Project deleted.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
