<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function index(Request $request)
    {
        $api = $this->api();
        $locations = [];
        $error = null;

        try {
            $locations = $api->get('/locations', $request->only(['search', 'page'])) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('locations.index', compact('locations', 'error'));
    }

    public function create()
    {
        return view('locations.create');
    }

    public function store(Request $request)
    {
        $api = $this->api();

        try {
            $r = $request->except('_token');
            $result = $api->post('/locations', array_filter([
                'locationName' => $r['name'] ?? '',
                'notes'        => $r['description'] ?: null,
            ], fn($v) => $v !== null));

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['name' => $result['conflict']])->withInput();
            }

            return redirect()->route('locations.index')->with('success', 'Location created successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(int $id)
    {
        $api = $this->api();
        $location = [];

        try {
            $location = $api->get("/locations/{$id}") ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, int $id)
    {
        $api = $this->api();

        try {
            $r = $request->except(['_token', '_method']);
            $result = $api->put("/locations/{$id}", [
                'locationName' => $r['name'] ?? '',
                'notes'        => $r['description'] ?: null,
                'isArchived'   => false,
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['name' => $result['conflict']])->withInput();
            }

            return redirect()->route('locations.index')->with('success', 'Location updated successfully.');
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
            $result = $api->delete("/locations/{$id}");
            if (is_array($result) && isset($result['conflict'])) {
                return back()->with('error', $result['conflict']);
            }
            return redirect()->route('locations.index')->with('success', 'Location deleted.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
