<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#a35c69">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=3">
</head>
<body>
@auth
    <div class="shell">
        <header class="panel topbar">
            <a href="{{ route('dashboard') }}" class="brand">
                <span class="brand-caption">Internal finance · Approval</span>
                <span class="brand-title">Vendor Payments</span>
            </a>

            <nav class="nav" aria-label="Main">
                <a href="{{ route('dashboard') }}" title="Dashboard" @class(['icon-btn', 'active' => request()->routeIs('dashboard') && request('status') !== 'pending'])>
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.3 3.3a1 1 0 0 1 1.4 0l8 7.5A1 1 0 0 1 20 12.5h-1V20a1 1 0 0 1-1 1h-4v-5.5a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1V21H6a1 1 0 0 1-1-1v-7.5H4a1 1 0 0 1-.7-1.7l8-7.5Z"/></svg>
                    <span class="sr-only">Dashboard</span>
                </a>
                @can('create', App\Models\PaymentRequest::class)
                    <a href="{{ route('payment-requests.create') }}" title="New request" @class(['icon-btn', 'active' => request()->routeIs('payment-requests.create')])>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        <span class="sr-only">New request</span>
                    </a>
                @endcan
                <a href="{{ route('dashboard', ['status' => 'pending']) }}" title="Pending requests" @class(['icon-btn', 'active' => request()->routeIs('dashboard') && request('status') === 'pending'])>
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="2.5"/><rect x="13" y="3" width="8" height="8" rx="2.5"/><rect x="3" y="13" width="8" height="8" rx="2.5"/><rect x="13" y="13" width="8" height="8" rx="2.5"/></svg>
                    <span class="sr-only">Pending requests</span>
                </a>
            </nav>

            <div class="user-chip">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true" style="color:var(--maroon)"><circle cx="12" cy="8" r="4.2"/><path d="M3.5 20.2c.9-4 4.3-6.2 8.5-6.2s7.6 2.2 8.5 6.2a1 1 0 0 1-1 1.3h-15a1 1 0 0 1-1-1.3Z"/></svg>
                <span class="user-name">{{ auth()->user()->name }}</span>
                <span class="role-pill">{{ auth()->user()->isApprover() ? 'Approver' : 'Staff' }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn" title="Log out">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"/></svg>
                        <span class="sr-only">Log out</span>
                    </button>
                </form>
            </div>
        </header>
    </div>
@endauth

<main class="container">
    @foreach (['success', 'info'] as $type)
        @if (session($type))
            <div class="alert alert-{{ $type }}" role="status">{{ session($type) }}</div>
        @endif
    @endforeach
    @error('status')
        <div class="alert alert-error" role="alert">{{ $message }}</div>
    @enderror

    @yield('content')
</main>

<footer class="footer">Vendor Payments · Internal use only</footer>
</body>
</html>
