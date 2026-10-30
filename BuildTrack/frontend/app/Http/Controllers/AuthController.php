<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('api_token')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $api = new ApiService();
            $result = $api->post('/auth/login', [
                'username' => $request->input('username'),
                'password' => $request->input('password'),
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors(['password' => 'Invalid credentials'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['password' => $result['conflict']])->withInput();
            }

            session([
                'api_token' => $result['token'],
                'api_token_expires' => $result['expiresAt'] ?? null,
                'auth_user' => $result['user'],
            ]);

            return redirect()->route('dashboard');
        } catch (ApiException $e) {
            return back()->withErrors(['password' => $e->getMessage()])->withInput();
        }
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|confirmed',
        ]);

        try {
            $api = new ApiService(session('api_token'));
            $result = $api->post('/auth/change-password', [
                'currentPassword' => $request->input('current_password'),
                'newPassword'     => $request->input('new_password'),
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Password changed successfully.');
        } catch (ApiException $e) {
            return back()->withErrors(['current_password' => $e->getMessage()])->withInput();
        }
    }

    public function logout(Request $request)
    {
        $token = session('api_token');
        if ($token) {
            try {
                $api = new ApiService($token);
                $api->post('/auth/logout');
            } catch (\Throwable $e) {
                // Ignore errors on logout
            }
        }

        $request->session()->flush();
        return redirect()->route('login')->with('success', 'You have been logged out.');
    }
}
