@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="page-head">
        <div>
            <h1>Payment requests</h1>
            <p>{{ auth()->user()->isApprover() ? 'All vendor payment requests across the organisation.' : 'Your vendor payment requests.' }}</p>
        </div>
        @can('create', App\Models\PaymentRequest::class)
            <a href="{{ route('payment-requests.create') }}" class="btn btn-primary">+ New request</a>
        @endcan
    </div>

    <section class="stats" aria-label="Totals by status">
        @foreach ($stats as $key => $stat)
            <a href="{{ route('dashboard', ['status' => $key]) }}" class="stat {{ $key }}">
                <div>
                    <div class="label">{{ $stat['label'] }}</div>
                    <div class="value">{{ number_format($stat['count']) }}</div>
                </div>
                <div class="sub">RM {{ number_format($stat['amount'], 2) }}</div>
            </a>
        @endforeach
    </section>

    <section class="card">
        <form method="GET" action="{{ route('dashboard') }}" class="filters" role="search">
            <div class="field search">
                <label for="q">Search</label>
                <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Vendor or invoice number" maxlength="100">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="date_field">Date type</label>
                <select id="date_field" name="date_field">
                    <option value="created_at" @selected($filters['date_field'] === 'created_at')>Submitted date</option>
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

    <section class="card">
        <div class="table-wrap">
            <table class="responsive">
                <thead>
                <tr>
                    <th>Reference</th>
                    <th>Vendor</th>
                    <th>Invoice no.</th>
                    <th class="num">Amount (RM)</th>
                    <th>Status</th>
                    <th>Created by</th>
                    <th>Submitted</th>
                    <th>Payment date</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($requests as $request)
                    <tr>
                        <td data-label="Reference"><a href="{{ route('payment-requests.show', $request) }}">{{ $request->reference }}</a></td>
                        <td data-label="Vendor">{{ $request->vendor_name }}</td>
                        <td data-label="Invoice no.">{{ $request->invoice_number }}</td>
                        <td data-label="Amount (RM)" class="num">{{ number_format($request->amount, 2) }}</td>
                        <td data-label="Status"><x-status-badge :status="$request->status" /></td>
                        <td data-label="Created by">{{ $request->requester->name }}</td>
                        <td data-label="Submitted">{{ $request->created_at->format('d M Y') }}</td>
                        <td data-label="Payment date">{{ $request->payment_date->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">No payment requests match your filters.</td></tr>
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
