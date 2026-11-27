<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#e64980">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
</head>
<body>
@php
    $user = auth()->user();
    $status = request()->routeIs('dashboard') ? request('status') : null;
    $onRequest = request()->routeIs('payment-requests.show', 'payment-requests.edit');
    $current = request()->route('paymentRequest');
    $tabCounts = isset($stats) ? $stats->map(fn ($s) => $s['count']) : collect();
@endphp
<div class="app">
    {{-- ===== Left icon rail ===== --}}
    <aside class="rail">
        <div class="rail-logo"><a href="{{ route('dashboard') }}" title="Vendor Payments">VP</a></div>

        <nav class="rail-nav" aria-label="Main">
            <a href="{{ route('dashboard') }}" @class(['rail-item', 'active' => (request()->routeIs('dashboard') && $status !== 'pending') || $onRequest])>
                <x-icon name="grid" /><span class="tip">Dashboard</span><span class="sr-only">Dashboard</span>
            </a>
            @can('create', App\Models\PaymentRequest::class)
                <a href="{{ route('payment-requests.create') }}" @class(['rail-item', 'active' => request()->routeIs('payment-requests.create')])>
                    <x-icon name="plus" /><span class="tip">New request</span><span class="sr-only">New request</span>
                </a>
            @endcan
            <a href="{{ route('dashboard', ['status' => 'pending']) }}" @class(['rail-item', 'active' => $status === 'pending'])>
                <x-icon name="clock" />
                <span class="tip">{{ $user->isApprover() ? 'Awaiting my review' : 'My pending requests' }}</span>
                <span class="sr-only">Pending</span>
            </a>
        </nav>

        <div class="rail-bottom">
            <x-avatar :user="$user" size="lg" />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rail-item"><x-icon name="logout" /><span class="tip">Log out</span><span class="sr-only">Log out</span></button>
            </form>
        </div>
    </aside>

    <div class="main">
        {{-- ===== Top bar ===== --}}
        <header class="topbar">
            <form class="top-search" method="GET" action="{{ route('dashboard') }}" role="search">
                <x-icon name="search" />
                <label for="top-q" class="sr-only">Search requests</label>
                <input id="top-q" type="search" name="q" value="{{ request()->routeIs('dashboard') ? request('q') : '' }}" placeholder="Search by vendor or invoice number…" maxlength="100">
            </form>
            <div class="top-right">
                <div class="who">
                    <div>
                        <div class="name">{{ $user->name }}</div>
                        <div class="role">{{ $user->role->label() }}</div>
                    </div>
                </div>
                <div class="wordmark">vendor<b>pay</b></div>
            </div>
        </header>

        {{-- ===== Pink tab band ===== --}}
        <div class="tabband">
            <nav class="tabs" aria-label="Sections">
                <a href="{{ route('dashboard') }}" @class(['tab', 'active' => request()->routeIs('dashboard') && ! $status])>
                    <x-icon name="list" /> Overview
                </a>
                @foreach (['pending' => 'clock', 'approved' => 'check', 'rejected' => 'x'] as $key => $icon)
                    <a href="{{ route('dashboard', ['status' => $key]) }}" @class(['tab', 'active' => $status === $key])>
                        <x-icon :name="$icon" /> {{ ucfirst($key) }}
                        @if ($tabCounts->has($key)) <span class="count">{{ $tabCounts[$key] }}</span> @endif
                    </a>
                @endforeach
                @can('create', App\Models\PaymentRequest::class)
                    <a href="{{ route('payment-requests.create') }}" @class(['tab', 'active' => request()->routeIs('payment-requests.create')])>
                        <x-icon name="plus" /> New request
                    </a>
                @endcan
                @if ($onRequest && $current instanceof App\Models\PaymentRequest)
                    <a href="{{ route('payment-requests.show', $current) }}" class="tab active">
                        <x-icon name="file" /> {{ $current->reference }}{{ request()->routeIs('payment-requests.edit') ? ' · Edit' : '' }}
                    </a>
                @endif
            </nav>
        </div>

        <main class="content">
            @foreach (['success' => 'check', 'info' => 'alert'] as $type => $icon)
                @if (session($type))
                    <div class="alert alert-{{ $type }}" role="status"><x-icon :name="$icon" width="18" height="18" /> {{ session($type) }}</div>
                @endif
            @endforeach
            @error('status')
                <div class="alert alert-error" role="alert">{{ $message }}</div>
            @enderror

            @yield('content')
        </main>

        <footer class="footer">Vendor Payments · Internal finance approval module</footer>
    </div>
</div>
</body>
</html>
