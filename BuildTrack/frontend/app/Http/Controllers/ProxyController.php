<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use App\Services\ApiException;
use App\Services\AuthException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProxyController extends Controller
{
    private function api(): ApiService
    {
        return new ApiService(session('api_token'));
    }

    public function getProject(int $id): JsonResponse
    {
        try {
            $project = $this->api()->get("/projects/{$id}") ?? [];
            return response()->json([
                'customerId'   => $project['customerId'] ?? null,
                'customerName' => $project['customerName'] ?? ($project['customer']['name'] ?? ''),
            ]);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCustomers(): JsonResponse
    {
        try {
            $customers = $this->api()->get('/customers') ?? [];
            $items = $customers['data'] ?? $customers['items'] ?? $customers;
            return response()->json($items);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getLocations(): JsonResponse
    {
        try {
            $locations = $this->api()->get('/locations') ?? [];
            $items = $locations['data'] ?? $locations['items'] ?? $locations;
            return response()->json($items);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProducts(): JsonResponse
    {
        try {
            $products = $this->api()->get('/products') ?? [];
            $items = $products['data'] ?? $products['items'] ?? $products;
            return response()->json($items);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createCustomer(Request $request): JsonResponse
    {
        try {
            $result = $this->api()->post('/customers', $request->all());
            if (isset($result['errors'])) {
                return response()->json(['errors' => $result['errors']], 422);
            }
            return response()->json($result, 201);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createLocation(Request $request): JsonResponse
    {
        try {
            $payload = ['locationName' => $request->input('locationName', $request->input('name', ''))];
            $notes = $request->input('notes', $request->input('description'));
            if (!empty($notes)) $payload['notes'] = $notes;
            $result = $this->api()->post('/locations', $payload);
            if (isset($result['errors'])) {
                return response()->json(['errors' => $result['errors']], 422);
            }
            return response()->json($result, 201);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createProduct(Request $request): JsonResponse
    {
        try {
            $payload = ['productName' => $request->input('productName', '')];
            $unit = $request->input('unitOfMeasure');
            if (!empty($unit)) $payload['unitOfMeasure'] = $unit;
            $result = $this->api()->post('/products', $payload);
            if (isset($result['errors'])) {
                return response()->json(['errors' => $result['errors']], 422);
            }
            return response()->json($result, 201);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getContractors(): JsonResponse
    {
        try {
            $result = $this->api()->get('/users/contractors') ?? [];
            return response()->json($result);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProjectTransactions(int $id): JsonResponse
    {
        try {
            $result = $this->api()->get("/reports/outstanding/{$id}/transactions") ?? [];
            return response()->json($result);
        } catch (AuthException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        } catch (ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
