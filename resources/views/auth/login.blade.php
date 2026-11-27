<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#e64980">
    <title>Log in · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
</head>
<body>
<div class="auth-page">
    <div class="auth-split">
        <section class="auth-brand">
            <div class="logo">VP</div>
            <h1>Vendor Payments</h1>
            <p>Submit vendor payment requests and get them reviewed by an approver, with every step on record.</p>
            <ul>
                <li>Bank Transfer, DuitNow &amp; Cheque</li>
                <li>Approve or reject with a reason</li>
                <li>Full audit trail</li>
            </ul>
        </section>

        <section class="auth-form">
            <h2>Welcome back</h2>
            <p class="lead">Sign in to your account to continue.</p>

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
                <button type="submit" class="btn btn-primary btn-block">Log in</button>
            </form>

            @if (app()->environment('local'))
                <div class="demo-creds">
                    <strong>Demo accounts</strong> · password <code>password</code><br>
                    Approver: <code>approver@example.com</code><br>
                    Staff: <code>staff@example.com</code>, <code>staff2@example.com</code>
                </div>
            @endif
        </section>
    </div>
</div>
</body>
</html>
