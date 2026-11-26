<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1">
</head>
<body>
@auth
    <header class="topbar">
        <div class="topbar-inner">
            <a href="{{ route('dashboard') }}" class="brand">Vendor Payments</a>
            <nav class="nav">
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>
                @can('create', App\Models\PaymentRequest::class)
                    <a href="{{ route('payment-requests.create') }}" @class(['active' => request()->routeIs('payment-requests.create')])>New request</a>
                @endcan
            </nav>
            <div class="user-menu">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <span class="badge badge-role">{{ auth()->user()->role->label() }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-link">Log out</button>
                </form>
            </div>
        </div>
    </header>
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
</body>
</html>
