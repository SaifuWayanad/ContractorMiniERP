<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;

class DashboardController extends Controller
{
    public function index()
    {
        $token = session('api_token');
        $api = new ApiService($token);

        $dashboard = [];
        $error = null;

        try {
            $dashboard = $api->get('/dashboard') ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('dashboard.index', compact('dashboard', 'error'));
    }
}
