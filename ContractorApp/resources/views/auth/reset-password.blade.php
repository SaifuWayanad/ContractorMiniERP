@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
<h1 class="guest-title">Choose a new password</h1>
<p class="guest-subtitle">Use at least 8 characters, including letters and numbers.</p>

<form method="post" action="{{ route('password.store') }}" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}" />

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="username"
            @class(['form-control', 'is-invalid' => $errors->has('email')]) />
        @error('email') <div class="invalid-feedback" role="alert">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">New password</label>
        <div @class(['input-group', 'has-validation', 'is-invalid' => $errors->has('password')])>
            <input id="password" name="password" type="password" required autocomplete="new-password"
                @class(['form-control', 'is-invalid' => $errors->has('password')]) />
            <button type="button" class="btn btn-outline-secondary" data-toggle-password="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirm new password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="form-control" />
    </div>

    <button type="submit" class="btn btn-primary w-100" data-loading-text="Saving…">Reset password</button>
</form>
@endsection
