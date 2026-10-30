<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private function requireAdmin()
    {
        $user = session('auth_user');
        if (($user['role'] ?? '') !== 'Admin') {
            abort(403, 'Admin access required.');
        }
    }

    public function index(Request $request)
    {
        $this->requireAdmin();
        $token = session('api_token');
        $api = new ApiService($token);
        $result = [];
        $error = null;

        try {
            $result = $api->get('/users', [
                'search'   => $request->search,
                'page'     => $request->page ?? 1,
                'pageSize' => 20,
            ]) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('users.index', ['data' => $result, 'search' => $request->search, 'error' => $error]);
    }

    public function create()
    {
        $this->requireAdmin();
        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->requireAdmin();
        $token = session('api_token');
        $api = new ApiService($token);

        try {
            $result = $api->post('/users', $request->only(['username', 'password', 'fullName', 'email', 'role']));

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['username' => $result['conflict']])->withInput();
            }

            return redirect()->route('users.index')->with('success', 'User created successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(int $id)
    {
        $this->requireAdmin();
        $token = session('api_token');
        $api = new ApiService($token);
        $user = [];

        try {
            $user = $api->get("/users/{$id}") ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return redirect()->route('users.index')->with('error', $e->getMessage());
        }

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, int $id)
    {
        $this->requireAdmin();
        $token = session('api_token');
        $api = new ApiService($token);

        try {
            $data = $request->only(['fullName', 'email', 'role', 'password']);
            $data['isActive'] = $request->has('isActive');

            // Remove password if blank
            if (empty($data['password'])) {
                unset($data['password']);
            }

            $result = $api->put("/users/{$id}", $data);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->with('error', $result['conflict'])->withInput();
            }

            return redirect()->route('users.index')->with('success', 'User updated successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(int $id)
    {
        $this->requireAdmin();
        $token = session('api_token');
        $api = new ApiService($token);

        try {
            $result = $api->delete("/users/{$id}");

            if (isset($result['conflict'])) {
                return back()->with('error', $result['conflict']);
            }

            return redirect()->route('users.index')->with('success', 'User deactivated.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
