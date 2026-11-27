@extends('layouts.app')

@section('title', $paymentRequest->reference)

@php
    use App\Enums\PaymentStatus;
    $ext = $paymentRequest->attachment_name ? strtoupper(pathinfo($paymentRequest->attachment_name, PATHINFO_EXTENSION)) : null;
@endphp

@section('content')
    <article class="board">
        <header class="board-head">
            <a href="{{ route('dashboard') }}" class="nav-cell" title="Back to list"><x-icon name="arrow-left" /><span class="sr-only">Back to list</span></a>
            <div class="title-cell">
                <h1>{{ $paymentRequest->reference }} <x-status-badge :status="$paymentRequest->status" /></h1>
                <div class="sub">Requested payment date: {{ $paymentRequest->payment_date->format('l, d M Y') }}</div>
            </div>
            @can('update', $paymentRequest)
                <div class="actions-cell">
                    <a href="{{ route('payment-requests.edit', $paymentRequest) }}" class="btn btn-secondary"><x-icon name="edit" />Edit</a>
                </div>
            @endcan
        </header>

        <div class="board-body">
            <div class="board-main">
                @if ($paymentRequest->status === PaymentStatus::Rejected)
                    <div class="callout callout-rejected">
                        <x-icon name="x" />
                        <div>
                            <strong>Rejected by {{ $paymentRequest->reviewer?->name ?? 'a former user' }}</strong>
                            · {{ $paymentRequest->reviewed_at?->format('d M Y, g:i A') }}
                            <div class="pre" style="margin-top:.25rem">{{ $paymentRequest->rejection_reason }}</div>
                        </div>
                    </div>
                @elseif ($paymentRequest->status === PaymentStatus::Approved)
                    <div class="callout callout-approved">
                        <x-icon name="check" />
                        <div><strong>Approved by {{ $paymentRequest->reviewer?->name ?? 'a former user' }}</strong> · {{ $paymentRequest->reviewed_at?->format('d M Y, g:i A') }}</div>
                    </div>
                @endif

                <div class="block">
                    <span class="label">Vendor</span>
                    <div class="value" style="font-size:1.05rem">{{ $paymentRequest->vendor_name }}</div>
                </div>

                <div class="facts">
                    <div><span class="label">Amount</span><div class="amount-lg">RM {{ number_format($paymentRequest->amount, 2) }}</div></div>
                    <div><span class="label">Invoice / reference no.</span><div class="value">{{ $paymentRequest->invoice_number }}</div></div>
                    <div><span class="label">Payment method</span>
                        <div class="value person"><x-method-icon :method="$paymentRequest->payment_method" />{{ $paymentRequest->payment_method->label() }}</div>
                    </div>
                    <div><span class="label">Requested payment date</span><div class="value">{{ $paymentRequest->payment_date->format('d M Y') }}</div></div>
                    <div><span class="label">Created by</span>
                        <div class="value person"><x-avatar :user="$paymentRequest->requester" />{{ $paymentRequest->requester->name }}</div>
                    </div>
                    <div><span class="label">Submitted</span><div class="value">{{ $paymentRequest->created_at->format('d M Y, g:i A') }}</div></div>
                </div>

                <div class="block">
                    <span class="label">Remarks</span>
                    <div class="value pre" style="font-weight:500">{{ $paymentRequest->remarks ?: '—' }}</div>
                </div>

                <h2 class="section-title" style="margin-top:1.8rem">Audit trail</h2>
                <ul class="trail">
                    @foreach ($paymentRequest->activities as $activity)
                        <li>
                            <x-avatar :user="$activity->user" size="lg" />
                            <div style="min-width:0">
                                <div class="head">
                                    <strong>{{ $activity->user->name }}</strong>
                                    <span class="when" title="{{ $activity->created_at->toIso8601String() }}">
                                        {{ $activity->user->role->label() }} · {{ $activity->created_at->format('g:i:s A · d/m/y') }} · {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="bubble {{ $activity->action->value }}">
                                    <span class="act">{{ $activity->action->label() }}</span>
                                    {{ match ($activity->action->value) {
                                        'created' => 'this payment request.',
                                        'updated' => 'the request:',
                                        'approved' => 'the payment.',
                                        'rejected' => 'the payment.',
                                    } }}
                                    @if ($activity->changes)
                                        <ul>
                                            @foreach ($activity->changes as $field => $change)
                                                <li>{{ \App\Models\PaymentRequest::AUDITED_FIELDS[$field] ?? $field }}: <del>{{ $change['old'] ?? '—' }}</del> → {{ $change['new'] ?? '—' }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if ($activity->notes)
                                        <div class="note">{{ $activity->notes }}</div>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @can('review', $paymentRequest)
                    <section class="composer" aria-label="Review">
                        <h2 class="section-title">Review this request</h2>
                        <form id="reject-form" method="POST" action="{{ route('payment-requests.reject', $paymentRequest) }}">
                            @csrf
                            <input type="hidden" name="version" value="{{ $version }}">
                            <label for="rejection_reason" class="sr-only">Reason for rejection</label>
                            <textarea id="rejection_reason" name="rejection_reason" maxlength="1000" placeholder="Reason for rejection (required to reject)…" @class(['is-invalid' => $errors->reject->has('rejection_reason')])>{{ old('rejection_reason') }}</textarea>
                            @error('rejection_reason', 'reject') <div class="error">{{ $message }}</div> @enderror
                        </form>
                        <form id="approve-form" method="POST" action="{{ route('payment-requests.approve', $paymentRequest) }}"
                              onsubmit="return confirm('Approve {{ $paymentRequest->reference }} for RM {{ number_format($paymentRequest->amount, 2) }}?')">
                            @csrf
                            <input type="hidden" name="version" value="{{ $version }}">
                        </form>
                        <div class="composer-actions">
                            <span class="hint">Approving pays RM {{ number_format($paymentRequest->amount, 2) }} to {{ $paymentRequest->vendor_name }}.</span>
                            <div class="buttons">
                                <button type="submit" form="reject-form" class="btn btn-danger"><x-icon name="x" />Reject</button>
                                <button type="submit" form="approve-form" class="btn btn-success"><x-icon name="check" />Approve</button>
                            </div>
                        </div>
                    </section>
                @endcan
            </div>

            <aside class="board-side">
                <h2 class="side-title">Actions</h2>
                <ul class="side-menu">
                    @can('review', $paymentRequest)
                        <li><a href="#rejection_reason" class="primary"><x-icon name="shield" />Review request</a></li>
                    @endcan
                    @can('update', $paymentRequest)
                        <li><a href="{{ route('payment-requests.edit', $paymentRequest) }}"><x-icon name="edit" />Edit details</a></li>
                    @endcan
                    @if ($paymentRequest->attachment_path)
                        <li><a href="{{ route('payment-requests.attachment', $paymentRequest) }}"><x-icon name="download" />Download invoice</a></li>
                    @endif
                    <li><a href="{{ route('dashboard') }}"><x-icon name="list" />All requests</a></li>
                    @if (! $paymentRequest->isPending())
                        <li><span><x-icon name="shield" />Locked: already {{ $paymentRequest->status->value }}</span></li>
                    @endif
                </ul>

                <h2 class="side-title">Attachment</h2>
                @if ($paymentRequest->attachment_path)
                    <a href="{{ route('payment-requests.attachment', $paymentRequest) }}" class="file-card">
                        <span class="file-icon doc"><x-icon name="file" /></span>
                        <span style="min-width:0">
                            <span class="name">{{ $paymentRequest->attachment_name }}</span>
                            <span class="sub" style="display:block">{{ $ext }} · private file</span>
                        </span>
                    </a>
                @else
                    <p class="side-empty">No invoice attached.</p>
                @endif
            </aside>
        </div>
    </article>
@endsection
