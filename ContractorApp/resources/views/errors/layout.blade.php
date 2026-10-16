<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('code') · @yield('title') - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="{{ asset('css/site.css') }}" />
</head>
<body class="guest-body">
    <main class="guest-shell error-shell">
        <div class="error-code">@yield('code')</div>
        <h1 class="guest-title">@yield('title')</h1>
        <p class="guest-subtitle">@yield('message')</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            @yield('actions')
            @auth
                <a href="{{ auth()->user()->homeRoute() }}" class="btn btn-primary"><i class="bi bi-house me-1"></i>Go to dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Sign in</a>
            @endauth
        </div>
    </main>
</body>
</html>
