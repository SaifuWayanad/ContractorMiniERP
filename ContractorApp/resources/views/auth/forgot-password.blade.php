@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
<h1 class="guest-title">Forgot your password?</h1>
<p class="guest-subtitle">Enter your email and we'll send you a link to choose a new password.</p>

@if (session('status'))
    <div class="alert alert-success small" role="status">{{ session('status') }}</div>
@endif

<form method="post" action="{{ route('password.email') }}" novalidate>
    @csrf
    <div class="mb-4">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
            @class(['form-control', 'is-invalid' => $errors->has('email')]) />
        @error('email') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>

    <button type="submit" class="btn btn-primary w-100" data-loading-text="Sending…">Email reset link</button>
</form>

<p class="text-center small mt-4 mb-0"><a href="{{ route('login') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to sign in</a></p>
@endsection
