@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
@php
    $currentUserId = session('auth_user')['id'] ?? null;
    $isEditingSelf = ($user['id'] ?? null) == $currentUserId;
@endphp

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
                <span><i class="bi bi-pencil me-2"></i>Edit User: {{ $user['fullName'] ?? $user['username'] ?? '' }}</span>
                @if(($user['role'] ?? '') === 'Admin')
                    <span class="badge bg-primary ms-1">Admin</span>
                @else
                    <span class="badge bg-success ms-1">Contractor</span>
                @endif
            </div>
            <div class="card-body">
                @if($isEditingSelf)
                <div class="alert alert-warning py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    You are editing your own account. You cannot deactivate yourself.
                </div>
                @endif

                <form method="POST" action="{{ route('users.update', $user['id'] ?? 0) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="fullName" class="form-control @error('fullName') is-invalid @enderror"
                               value="{{ old('fullName', $user['fullName'] ?? '') }}" required>
                        @error('fullName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" class="form-control bg-light" value="{{ $user['username'] ?? '' }}" disabled readonly>
                        <small class="text-muted">Username cannot be changed.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user['email'] ?? '') }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                        <div class="cs-wrapper" id="roleSelectWrapper">
                            <div class="cs-trigger @error('role') is-invalid @enderror" tabindex="0">
                                <span class="cs-value cs-placeholder">Select a role...</span>
                                <i class="bi bi-chevron-down cs-arrow"></i>
                            </div>
                            <div class="cs-panel">
                                <div class="cs-list">
                                    <div class="cs-item" data-value="Admin">Admin</div>
                                    <div class="cs-item" data-value="Contractor">Contractor</div>
                                </div>
                            </div>
                            <input type="hidden" name="role" id="roleSelectInput" value="{{ old('role', $user['role'] ?? '') }}">
                        </div>
                        @error('role')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               placeholder="Leave blank to keep current password" autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if(!$isEditingSelf)
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="isActive" id="isActiveCheck" value="1"
                                   {{ old('isActive', ($user['isActive'] ?? true) ? '1' : '') ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActiveCheck">
                                Active <span class="text-muted small">(uncheck to deactivate this user)</span>
                            </label>
                        </div>
                    </div>
                    @else
                    {{-- Silently pass isActive=1 so self-edit doesn't accidentally deactivate own account --}}
                    <input type="hidden" name="isActive" value="1">
                    @endif

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Save Changes
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
new CustomSelect('roleSelectWrapper');
</script>
@endsection
