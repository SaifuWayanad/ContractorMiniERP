<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign In – {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:   #142B4A;
            --blue:   #2563EB;
            --blue-h: #1d4ed8;
            --bg:     #F5F7FB;
            --white:  #FFFFFF;
            --text:   #172033;
            --muted:  #64748B;
            --border: #E2E8F0;
        }

        html, body { height: 100%; font-family: 'Inter', sans-serif; color: var(--text); background: var(--bg); }

        /* ── Layout ── */
        .login-wrap {
            display: flex;
            min-height: 100vh;
        }

        /* ── Left branding panel ── */
        .login-brand {
            flex: 0 0 52%;
            background: var(--navy);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem 3.5rem;
            position: relative;
            overflow: hidden;
        }

        /* subtle geometric accents */
        .login-brand::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: rgba(37,99,235,.18);
            pointer-events: none;
        }
        .login-brand::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: rgba(37,99,235,.12);
            pointer-events: none;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: .6rem;
            text-decoration: none;
            z-index: 1;
        }
        .brand-logo .logo-icon {
            width: 40px; height: 40px;
            background: rgba(255,255,255,.12);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            color: #fff;
        }
        .brand-logo .logo-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.01em;
        }

        .brand-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            z-index: 1;
            padding: 2rem 0;
        }

        .brand-headline {
            font-size: 2.1rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            letter-spacing: -.02em;
            margin-bottom: 1rem;
        }
        .brand-headline span { color: #7ec8f7; }

        .brand-desc {
            font-size: .975rem;
            color: rgba(200,216,232,.8);
            line-height: 1.65;
            max-width: 380px;
            margin-bottom: 2.5rem;
        }

        /* Feature pills */
        .brand-features {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
        }
        .feat-pill {
            display: flex;
            align-items: center;
            gap: .4rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 999px;
            padding: .35rem .85rem;
            font-size: .8rem;
            color: rgba(255,255,255,.85);
        }
        .feat-pill i { color: #7ec8f7; font-size: .85rem; }

        /* Hero SVG illustration */
        .brand-visual {
            z-index: 1;
            display: flex;
            justify-content: center;
            margin: 2rem 0;
        }
        .brand-visual svg { width: 100%; max-width: 360px; }

        .brand-footer {
            font-size: .78rem;
            color: rgba(200,216,232,.45);
            z-index: 1;
        }

        /* ── Right form panel ── */
        .login-form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: var(--white);
        }

        .login-box {
            width: 100%;
            max-width: 420px;
        }

        .login-box .welcome-head {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -.02em;
            margin-bottom: .35rem;
        }
        .login-box .welcome-sub {
            font-size: .9rem;
            color: var(--muted);
            margin-bottom: 2rem;
        }

        /* Status / signed-out banner */
        .status-banner {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: .8rem 1rem;
            margin-bottom: 1.5rem;
            font-size: .875rem;
            color: #166534;
        }
        .status-banner i { font-size: 1rem; margin-top: .05rem; flex-shrink: 0; }

        /* Form labels & inputs */
        .f-group { margin-bottom: 1.1rem; }

        .f-label {
            display: block;
            font-size: .825rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: .45rem;
        }

        .f-input-wrap {
            position: relative;
        }
        .f-input-wrap .fi-icon {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: .95rem;
            pointer-events: none;
        }
        .f-input-wrap input {
            width: 100%;
            height: 44px;
            padding: 0 2.8rem 0 2.6rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: .9rem;
            color: var(--text);
            background: var(--white);
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        .f-input-wrap input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        .f-input-wrap input.is-err {
            border-color: #ef4444;
        }
        .f-input-wrap input.is-err:focus {
            box-shadow: 0 0 0 3px rgba(239,68,68,.12);
        }
        .f-toggle {
            position: absolute;
            right: .75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: .2rem;
            font-size: 1rem;
            line-height: 1;
        }
        .f-toggle:hover { color: var(--muted); }

        .f-error {
            font-size: .78rem;
            color: #dc2626;
            margin-top: .3rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        /* Row: remember + forgot */
        .f-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .f-check {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .85rem;
            color: var(--muted);
            cursor: pointer;
        }
        .f-check input[type="checkbox"] {
            width: 16px; height: 16px;
            border-radius: 4px;
            border: 1.5px solid var(--border);
            accent-color: var(--blue);
            cursor: pointer;
        }
        .f-forgot {
            font-size: .85rem;
            color: var(--blue);
            text-decoration: none;
            font-weight: 500;
        }
        .f-forgot:hover { color: var(--blue-h); text-decoration: underline; }

        /* Submit button */
        .btn-signin {
            width: 100%;
            height: 46px;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            transition: background .15s, transform .1s, box-shadow .15s;
            box-shadow: 0 2px 8px rgba(37,99,235,.3);
        }
        .btn-signin:hover:not(:disabled) {
            background: var(--blue-h);
            box-shadow: 0 4px 14px rgba(37,99,235,.35);
        }
        .btn-signin:active:not(:disabled) { transform: scale(.99); }
        .btn-signin:disabled { opacity: .7; cursor: not-allowed; }

        /* Spinner */
        .spin {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .login-brand { display: none; }
            .login-form-panel { background: var(--bg); }
            .login-box { background: var(--white); padding: 2rem; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.07); }
        }
        @media (max-width: 480px) {
            .login-form-panel { padding: 1.5rem 1rem; align-items: flex-start; padding-top: 2.5rem; }
            .login-box { padding: 1.5rem; }
        }
    </style>
</head>
<body>
<div class="login-wrap">

    {{-- ── Left: Branding ── --}}
    <aside class="login-brand">
        <a href="{{ url('/') }}" class="brand-logo">
            <span class="logo-icon"><i class="bi bi-cone-striped"></i></span>
            <span class="logo-name">{{ config('app.name') }}</span>
        </a>

        <div class="brand-body">
            <h1 class="brand-headline">Manage Your Projects.<br><span>Build With Confidence.</span></h1>
            <p class="brand-desc">Everything you need to run your contracting business — projects, customers, purchases, expenses, and payments — all in one place.</p>

            <div class="brand-features">
                <span class="feat-pill"><i class="bi bi-building"></i> Project Management</span>
                <span class="feat-pill"><i class="bi bi-cart3"></i> Purchases & Expenses</span>
                <span class="feat-pill"><i class="bi bi-credit-card"></i> Payment Tracking</span>
                <span class="feat-pill"><i class="bi bi-bar-chart-line"></i> Financial Reports</span>
                <span class="feat-pill"><i class="bi bi-people"></i> Customer Management</span>
            </div>

            <div class="brand-visual">
                <svg viewBox="0 0 360 220" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <!-- Ground -->
                    <rect x="0" y="185" width="360" height="35" rx="4" fill="rgba(255,255,255,.06)"/>
                    <!-- Building left -->
                    <rect x="30" y="80" width="70" height="105" rx="4" fill="rgba(255,255,255,.1)"/>
                    <rect x="38" y="90" width="18" height="22" rx="2" fill="rgba(126,200,247,.3)"/>
                    <rect x="64" y="90" width="18" height="22" rx="2" fill="rgba(126,200,247,.3)"/>
                    <rect x="38" y="122" width="18" height="22" rx="2" fill="rgba(126,200,247,.3)"/>
                    <rect x="64" y="122" width="18" height="22" rx="2" fill="rgba(126,200,247,.3)"/>
                    <rect x="47" y="155" width="26" height="30" rx="2" fill="rgba(37,99,235,.35)"/>
                    <!-- Building center (tall) -->
                    <rect x="120" y="30" width="90" height="155" rx="4" fill="rgba(255,255,255,.13)"/>
                    <rect x="130" y="44" width="20" height="24" rx="2" fill="rgba(126,200,247,.35)"/>
                    <rect x="158" y="44" width="20" height="24" rx="2" fill="rgba(126,200,247,.35)"/>
                    <rect x="130" y="78" width="20" height="24" rx="2" fill="rgba(126,200,247,.25)"/>
                    <rect x="158" y="78" width="20" height="24" rx="2" fill="rgba(126,200,247,.35)"/>
                    <rect x="130" y="112" width="20" height="24" rx="2" fill="rgba(126,200,247,.35)"/>
                    <rect x="158" y="112" width="20" height="24" rx="2" fill="rgba(126,200,247,.2)"/>
                    <rect x="142" y="150" width="30" height="35" rx="2" fill="rgba(37,99,235,.4)"/>
                    <!-- Building right -->
                    <rect x="230" y="60" width="80" height="125" rx="4" fill="rgba(255,255,255,.09)"/>
                    <rect x="240" y="72" width="20" height="22" rx="2" fill="rgba(126,200,247,.28)"/>
                    <rect x="268" y="72" width="20" height="22" rx="2" fill="rgba(126,200,247,.28)"/>
                    <rect x="240" y="104" width="20" height="22" rx="2" fill="rgba(126,200,247,.28)"/>
                    <rect x="268" y="104" width="20" height="22" rx="2" fill="rgba(126,200,247,.28)"/>
                    <rect x="240" y="136" width="20" height="22" rx="2" fill="rgba(126,200,247,.2)"/>
                    <rect x="268" y="136" width="20" height="22" rx="2" fill="rgba(126,200,247,.28)"/>
                    <rect x="248" y="160" width="30" height="25" rx="2" fill="rgba(37,99,235,.35)"/>
                    <!-- Crane -->
                    <rect x="316" y="20" width="4" height="120" rx="2" fill="rgba(255,255,255,.18)"/>
                    <rect x="280" y="20" width="40" height="4" rx="2" fill="rgba(255,255,255,.18)"/>
                    <line x1="318" y1="24" x2="288" y2="60" stroke="rgba(126,200,247,.4)" stroke-width="1.5"/>
                    <rect x="284" y="58" width="8" height="16" rx="2" fill="rgba(126,200,247,.5)"/>
                    <!-- Accent dots -->
                    <circle cx="20" cy="40" r="4" fill="rgba(37,99,235,.4)"/>
                    <circle cx="340" cy="160" r="5" fill="rgba(126,200,247,.25)"/>
                    <circle cx="60" cy="185" r="3" fill="rgba(255,255,255,.1)"/>
                </svg>
            </div>
        </div>

        <p class="brand-footer">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </aside>

    {{-- ── Right: Login form ── --}}
    <section class="login-form-panel">
        <div class="login-box">

            <h2 class="welcome-head">Welcome Back</h2>
            <p class="welcome-sub">Sign in to continue to your workspace.</p>

            @if (session('status'))
                <div class="status-banner" role="status">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form method="post" action="{{ route('login') }}" novalidate id="loginForm">
                @csrf

                {{-- Username --}}
                <div class="f-group">
                    <label for="username" class="f-label">Username</label>
                    <div class="f-input-wrap">
                        <i class="bi bi-person fi-icon"></i>
                        <input id="username" name="username" type="text"
                               value="{{ old('username') }}"
                               required autofocus autocomplete="username"
                               placeholder="Enter your username"
                               class="{{ $errors->has('username') ? 'is-err' : '' }}" />
                    </div>
                    @error('username')
                        <div class="f-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="f-group">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:.45rem;">
                        <label for="password" class="f-label" style="margin-bottom:0;">Password</label>
                        <a href="{{ route('password.request') }}" class="f-forgot">Forgot password?</a>
                    </div>
                    <div class="f-input-wrap">
                        <i class="bi bi-lock fi-icon"></i>
                        <input id="password" name="password" type="password"
                               required autocomplete="current-password"
                               placeholder="Enter your password"
                               class="{{ $errors->has('password') ? 'is-err' : '' }}" />
                        <button type="button" class="f-toggle" id="pwToggle" aria-label="Show password">
                            <i class="bi bi-eye" id="pwToggleIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="f-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</div>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="f-row">
                    <label class="f-check">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember')) />
                        Remember me
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-signin" id="signinBtn">
                    <span class="spin" id="signinSpinner"></span>
                    <span id="signinLabel">Sign In</span>
                </button>
            </form>

        </div>
    </section>

</div>

<script>
(function () {
    // Password show/hide
    var pwInput  = document.getElementById('password');
    var pwToggle = document.getElementById('pwToggle');
    var pwIcon   = document.getElementById('pwToggleIcon');

    if (pwToggle) {
        pwToggle.addEventListener('click', function () {
            var show = pwInput.type === 'text';
            pwInput.type = show ? 'password' : 'text';
            pwIcon.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    }

    // Loading state on submit
    var form    = document.getElementById('loginForm');
    var btn     = document.getElementById('signinBtn');
    var spinner = document.getElementById('signinSpinner');
    var label   = document.getElementById('signinLabel');

    if (form) {
        form.addEventListener('submit', function () {
            btn.disabled = true;
            spinner.style.display = 'block';
            label.textContent = 'Signing in…';
        });
    }
})();
</script>
</body>
</html>
