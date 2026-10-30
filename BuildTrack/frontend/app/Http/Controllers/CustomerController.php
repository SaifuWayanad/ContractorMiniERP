<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function index(Request $request)
    {
        $api = $this->api();
        $customers = [];
        $error = null;
        $query = $request->only(['search', 'page']);

        try {
            $customers = $api->get('/customers', $query) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('customers.index', compact('customers', 'error'));
    }

    public function create()
    {
        $api = $this->api();
        $locations = [];

        try {
            $locations = $api->get('/locations') ?? [];
        } catch (\Throwable $e) {
            // Pass empty list
        }

        return view('customers.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $api = $this->api();

        try {
            $r = $request->except('_token');
            $result = $api->post('/customers', array_filter([
                'customerName' => $r['name'] ?? '',
                'phoneNumber'  => $r['phone'] ?: null,
                'locationId'   => !empty($r['locationId']) ? (int)$r['locationId'] : null,
                'address'      => $r['address'] ?: null,
                'notes'        => $r['notes'] ?: null,
            ], fn($v) => $v !== null));

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['name' => $result['conflict']])->withInput();
            }

            return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(int $id)
    {
        $api = $this->api();
        $customer = [];
        $projects = [];
        $error = null;

        try {
            $customer = $api->get("/customers/{$id}") ?? [];
            $projects = $api->get("/customers/{$id}/projects") ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('customers.show', compact('customer', 'projects', 'error'));
    }

    public function edit(int $id)
    {
        $api = $this->api();
        $customer = [];
        $locations = [];

        try {
            $customer = $api->get("/customers/{$id}") ?? [];
            $locations = $api->get('/locations') ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('customers.edit', compact('customer', 'locations'));
    }

    public function update(Request $request, int $id)
    {
        $api = $this->api();

        try {
            $r = $request->except(['_token', '_method']);
            $result = $api->put("/customers/{$id}", array_filter([
                'customerName' => $r['name'] ?? '',
                'phoneNumber'  => $r['phone'] ?: null,
                'locationId'   => !empty($r['locationId']) ? (int)$r['locationId'] : null,
                'address'      => $r['address'] ?: null,
                'notes'        => $r['notes'] ?: null,
            ], fn($v) => $v !== null));

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['name' => $result['conflict']])->withInput();
            }

            return redirect()->route('customers.show', $id)->with('success', 'Customer updated successfully.');
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
            $result = $api->delete("/customers/{$id}");
            if (is_array($result) && isset($result['conflict'])) {
                return back()->with('error', $result['conflict']);
            }
            return redirect()->route('customers.index')->with('success', 'Customer deleted.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
