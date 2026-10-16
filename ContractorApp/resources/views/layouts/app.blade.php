<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v={{ filemtime(public_path('css/site.css')) }}" />
</head>
<body>
    <div class="app-shell">
        <x-sidebar />

        <div class="main-area">
            <x-navbar />

            <main role="main" class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <x-flash />
    <x-confirm-modal />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}"></script>
    @stack('scripts')
</body>
</html>
