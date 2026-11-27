@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div class="title">
            <div>
                <h1>{{ auth()->user()->isApprover() ? 'All payment requests' : 'My payment requests' }}</h1>
                <p>{{ auth()->user()->isApprover() ? 'Every vendor payment request across the organisation.' : 'Requests you have submitted and their approval status.' }}</p>
            </div>
            @can('create', App\Models\PaymentRequest::class)
                <a href="{{ route('payment-requests.create') }}" class="square-btn pink" title="New request"><x-icon name="plus" /><span class="sr-only">New request</span></a>
            @endcan
        </div>
    </div>

    <h2 class="section-title">Quick access</h2>
    <section class="quick" aria-label="Totals by status">
        @foreach ($stats as $key => $stat)
            <a href="{{ route('dashboard', ['status' => $key]) }}" @class(['folder', 'is-featured' => $key === 'pending', 'is-active' => ($filters['status'] ?? null) === $key])>
                <span class="kicker"><span class="dot {{ $key }}"></span>{{ $stat['label'] }}</span>
                <span class="big">{{ number_format($stat['count']) }}</span>
                <span class="foot">
                    <span><small>Total amount</small>RM {{ number_format($stat['amount'], 2) }}</span>
                    <span><small>Requests</small>{{ $stat['count'] === 1 ? '1 item' : $stat['count'].' items' }}</span>
                </span>
            </a>
        @endforeach

        @if ($latest)
            <a href="{{ route('payment-requests.show', $latest) }}" class="folder latest">
                <span class="top">
                    <x-method-icon :method="$latest->payment_method" />
                    <x-avatar :user="$latest->requester" />
                </span>
                <span class="doc-title">{{ $latest->reference }} · {{ \Illuminate\Support\Str::limit($latest->vendor_name, 34) }}</span>
                <span class="foot">
                    <span><small>Last submitted</small>{{ $latest->created_at->format('d M Y · g:i A') }}</span>
                </span>
            </a>
        @else
            <div class="folder latest">
                <span class="kicker">Last submitted</span>
                <span class="muted">No requests yet.</span>
            </div>
        @endif
    </section>

    <section class="card">
        <form method="GET" action="{{ route('dashboard') }}" class="filters" role="search">
            <div class="field search">
                <label for="q">Search</label>
                <div class="search-field">
                    <x-icon name="search" />
                    <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Vendor or invoice number" maxlength="100">
                </div>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}" @selected(($filters['status'] ?? '') === $option->value)>{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="date_field">Date type</label>
                <select id="date_field" name="date_field">
                    <option value="created_at" @selected($filters['date_field'] === 'created_at')>Submitted</option>
                    <option value="payment_date" @selected($filters['date_field'] === 'payment_date')>Payment date</option>
                </select>
            </div>
            <div class="field">
                <label for="date_from">From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="field">
                <label for="date_to">To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
        @if ($filterErrors->any())
            <div class="alert alert-error" style="margin: 1rem 0 0">
                Some filters were ignored:
                <ul>@foreach ($filterErrors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
    </section>

    <div class="section-head">
        <h2 class="section-title">All requests</h2>
        <span class="meta">{{ $requests->total() }} {{ \Illuminate\Support\Str::plural('result', $requests->total()) }}</span>
    </div>
    <section class="card">
        <div class="table-wrap">
            <table class="responsive">
                <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Reference</th>
                    <th>Created by</th>
                    <th class="num">Amount (RM)</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Payment date</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <td class="first">
                            <div class="cell-file">
                                <x-method-icon :method="$request->payment_method" />
                                <div>
                                    <a href="{{ route('payment-requests.show', $request) }}" class="name">{{ $request->vendor_name }}</a>
                                    <div class="sub">{{ $request->invoice_number }} · {{ $request->payment_method->label() }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Reference"><a href="{{ route('payment-requests.show', $request) }}" class="ref">{{ $request->reference }}</a></td>
                        <td data-label="Created by"><span class="person"><x-avatar :user="$request->requester" />{{ $request->requester->name }}</span></td>
                        <td data-label="Amount (RM)" class="num">{{ number_format($request->amount, 2) }}</td>
                        <td data-label="Status"><x-status-badge :status="$request->status" /></td>
                        <td data-label="Submitted" class="nowrap">{{ $request->created_at->format('d M Y') }}</td>
                        <td data-label="Payment date" class="nowrap">{{ $request->payment_date->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">No payment requests match your filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests->total() > 0)
            <div class="pagination-bar">
                <span>Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }}</span>
                {{ $requests->onEachSide(1)->links('payment-requests.pagination') }}
            </div>
        @endif
    </section>
@endsection
