<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#a35c69">
    <title>Log in · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=3">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-hero">
            <span class="eyebrow">Internal finance · Approval module</span>
            <span class="brand-title">Vendor<br>Payments</span>
            <span class="sub">Submit · Review · Approve</span>
        </div>

        <div class="card">
            <h2>Sign in</h2>
            @if (session('status'))
                <div class="alert alert-info">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@company.com" @class(['is-invalid' => $errors->has('email')])>
                    @error('email') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" @class(['is-invalid' => $errors->has('password')])>
                    @error('password') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label class="checkbox"><input type="checkbox" name="remember" value="1"> Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Log in <span class="arrow">&rarr;</span></button>
            </form>

            @if (app()->environment('local'))
                <div class="demo-creds">
                    <strong>Demo accounts</strong> · password <code>password</code><br>
                    Approver: <code>approver@example.com</code><br>
                    Staff: <code>staff@example.com</code>, <code>staff2@example.com</code>
                </div>
            @endif
        </div>
    </div>
    <div class="footer">Vendor Payments · Internal use only</div>
</div>
</body>
</html>
