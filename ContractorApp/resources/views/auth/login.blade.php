@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<h1 class="guest-title">Sign in</h1>
<p class="guest-subtitle">Welcome back. Enter your details to continue.</p>

@if (session('status'))
    <div class="alert alert-success small" role="status">{{ session('status') }}</div>
@endif

<form method="post" action="{{ route('login') }}" novalidate>
    @csrf
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username"
            @class(['form-control', 'is-invalid' => $errors->has('username')]) />
        @error('username') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-baseline">
            <label for="password" class="form-label">Password</label>
            <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
        </div>
        <div @class(['input-group', 'has-validation', 'is-invalid' => $errors->has('password')])>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                @class(['form-control', 'is-invalid' => $errors->has('password')]) />
            <button type="button" class="btn btn-outline-secondary" data-toggle-password="password" aria-label="Show password">
                <i class="bi bi-eye"></i>
            </button>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" @checked(old('remember')) />
        <label class="form-check-label" for="remember">Remember me</label>
    </div>

    <button type="submit" class="btn btn-primary w-100" data-loading-text="Signing in…">Sign in</button>
</form>
@endsection
