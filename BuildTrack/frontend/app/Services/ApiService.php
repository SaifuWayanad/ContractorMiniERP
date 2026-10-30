<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class ApiException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode = 0)
    {
        parent::__construct($message);
    }
}

class AuthException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Unauthenticated', 401);
    }
}

class ApiService
{
    private string $baseUrl;
    private ?string $token;

    public function __construct(?string $token = null)
    {
        $this->baseUrl = config('services.backend.base_url');
        $this->token = $token;
    }

    private function buildRequest(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout(15);
        if ($this->token) {
            $request = $request->withToken($this->token);
        }
        return $request;
    }

    private function handleResponse(\Illuminate\Http\Client\Response $response): mixed
    {
        if ($response->status() === 401) {
            throw new AuthException();
        }

        if ($response->status() === 400 || $response->status() === 422) {
            $body = $response->json();
            $errors = $body['errors'] ?? $body['message'] ?? ['An error occurred'];
            if (is_string($errors)) {
                $errors = [$errors];
            }
            return ['errors' => $errors];
        }

        if ($response->status() === 409) {
            $body = $response->json();
            return ['conflict' => $body['message'] ?? 'A conflict occurred'];
        }

        if ($response->failed()) {
            throw new ApiException('Request failed: ' . $response->status(), $response->status());
        }

        if ($response->status() === 204 || empty($response->body())) {
            return null;
        }

        return $response->json();
    }

    public function get(string $path, array $query = []): mixed
    {
        try {
            $url = $this->baseUrl . $path;
            $response = $this->buildRequest()->get($url, $query);
            return $this->handleResponse($response);
        } catch (ConnectionException $e) {
            throw new ApiException('Service temporarily unavailable, please try again');
        }
    }

    public function post(string $path, array $data = []): mixed
    {
        try {
            $url = $this->baseUrl . $path;
            $response = $this->buildRequest()->post($url, $data);
            return $this->handleResponse($response);
        } catch (ConnectionException $e) {
            throw new ApiException('Service temporarily unavailable, please try again');
        }
    }

    public function put(string $path, array $data = []): mixed
    {
        try {
            $url = $this->baseUrl . $path;
            $response = $this->buildRequest()->put($url, $data);
            return $this->handleResponse($response);
        } catch (ConnectionException $e) {
            throw new ApiException('Service temporarily unavailable, please try again');
        }
    }

    public function delete(string $path): mixed
    {
        try {
            $url = $this->baseUrl . $path;
            $response = $this->buildRequest()->delete($url);
            return $this->handleResponse($response);
        } catch (ConnectionException $e) {
            throw new ApiException('Service temporarily unavailable, please try again');
        }
    }
}
