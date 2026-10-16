<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}" />
</head>
<body class="guest-body">
    <main class="guest-shell">
        <a href="{{ url('/') }}" class="guest-brand">
            <i class="bi bi-cone-striped" aria-hidden="true"></i> {{ config('app.name') }}
        </a>
        <div class="card guest-card">
            <div class="card-body">
                @yield('content')
            </div>
        </div>
        <p class="guest-footer">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </main>

    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}"></script>
</body>
</html>
