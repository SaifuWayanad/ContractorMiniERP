@extends('layouts.app')

@section('title', 'Create Login — '.$employee->name)

@section('content')
<x-page-header title="Create Login" :subtitle="$employee->name" :back="route('employees.show', $employee)" />

<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="card form-card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">
                        <i class="bi bi-person-fill text-primary fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $employee->name }}</div>
                        <div class="text-muted small">{{ $employee->designation ?? 'Employee' }}</div>
                    </div>
                </div>

                <form method="post" action="{{ route('employees.login.store', $employee) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="loginEmail" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="loginEmail" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $employee->email) }}"
                            required maxlength="200" autocomplete="email" autofocus />
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">This will be the username to log in.</div>
                    </div>

                    <div class="mb-3">
                        <label for="loginPassword" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="loginPassword" name="password" class="form-control @error('password') is-invalid @enderror"
                                required minlength="6" autocomplete="new-password" />
                            <button type="button" class="btn btn-outline-secondary" id="togglePwd" tabindex="-1"><i class="bi bi-eye"></i></button>
                        </div>
                        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label for="loginPasswordConfirm" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" id="loginPasswordConfirm" name="password_confirmation" class="form-control"
                            required minlength="6" autocomplete="new-password" />
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('employees.show', $employee) }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-person-check me-1"></i>Create Login</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3 border-info border-opacity-50">
            <div class="card-body py-2 px-3">
                <div class="d-flex gap-2 align-items-start">
                    <i class="bi bi-info-circle text-info mt-1"></i>
                    <div class="small text-muted">
                        This creates a <strong>Contractor Staff</strong> login. The employee can sign in at <strong>{{ url('/login') }}</strong> using their email and the password you set.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('togglePwd').addEventListener('click', function () {
    var input = document.getElementById('loginPassword');
    var icon  = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
@endpush
@endsection
