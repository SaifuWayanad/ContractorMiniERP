<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function index(Request $request)
    {
        $api = $this->api();
        $products = [];
        $error = null;

        try {
            $products = $api->get('/products', $request->only(['search', 'page'])) ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            $error = $e->getMessage();
        }

        return view('products.index', compact('products', 'error'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $api = $this->api();

        try {
            $r = $request->except('_token');
            $result = $api->post('/products', array_filter([
                'productName'   => $r['name'] ?? '',
                'unitOfMeasure' => $r['unit'] ?: null,
                'description'   => $r['description'] ?: null,
            ], fn($v) => $v !== null));

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['name' => $result['conflict']])->withInput();
            }

            return redirect()->route('products.index')->with('success', 'Product created successfully.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(int $id)
    {
        $api = $this->api();
        $product = [];

        try {
            $product = $api->get("/products/{$id}") ?? [];
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, int $id)
    {
        $api = $this->api();

        try {
            $r = $request->except(['_token', '_method']);
            $result = $api->put("/products/{$id}", [
                'productName'   => $r['name'] ?? '',
                'unitOfMeasure' => $r['unit'] ?? '',
                'description'   => $r['description'] ?: null,
                'isActive'      => true,
            ]);

            if (isset($result['errors'])) {
                return back()->withErrors($result['errors'])->withInput();
            }

            if (isset($result['conflict'])) {
                return back()->withErrors(['name' => $result['conflict']])->withInput();
            }

            return redirect()->route('products.index')->with('success', 'Product updated successfully.');
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
            $result = $api->delete("/products/{$id}");
            if (is_array($result) && isset($result['conflict'])) {
                return back()->with('error', $result['conflict']);
            }
            return redirect()->route('products.index')->with('success', 'Product deleted.');
        } catch (AuthException $e) {
            return redirect()->route('login')->with('error', 'Session expired, please log in again');
        } catch (ApiException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
