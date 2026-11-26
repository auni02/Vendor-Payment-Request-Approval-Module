@extends('layouts.app')

@section('title', $paymentRequest->reference)

@section('content')
    <div class="page-head">
        <div>
            <h1>{{ $paymentRequest->reference }} <x-status-badge :status="$paymentRequest->status" /></h1>
            <p>{{ $paymentRequest->vendor_name }} · submitted by {{ $paymentRequest->requester->name }} on {{ $paymentRequest->created_at->format('d M Y, g:i A') }}</p>
        </div>
        <div style="display:flex; gap:.5rem">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">&larr; Back</a>
            @can('update', $paymentRequest)
                <a href="{{ route('payment-requests.edit', $paymentRequest) }}" class="btn btn-primary">Edit</a>
            @endcan
        </div>
    </div>

    <div class="grid-2">
        <div>
            <section class="card">
                @if ($paymentRequest->status === \App\Enums\PaymentStatus::Rejected)
                    <div class="callout callout-rejected">
                        <strong>Rejected by {{ $paymentRequest->reviewer?->name ?? 'a former user' }}</strong>
                        on {{ $paymentRequest->reviewed_at?->format('d M Y, g:i A') }}
                        <div class="pre" style="margin-top:.35rem">{{ $paymentRequest->rejection_reason }}</div>
                    </div>
                @elseif ($paymentRequest->status === \App\Enums\PaymentStatus::Approved)
                    <div class="callout callout-approved">
                        <strong>Approved by {{ $paymentRequest->reviewer?->name ?? 'a former user' }}</strong>
                        on {{ $paymentRequest->reviewed_at?->format('d M Y, g:i A') }}
                    </div>
                @endif

                <dl class="details">
                    <dt>Amount</dt>
                    <dd class="amount-lg">RM {{ number_format($paymentRequest->amount, 2) }}</dd>
                    <dt>Vendor name</dt>
                    <dd>{{ $paymentRequest->vendor_name }}</dd>
                    <dt>Invoice / reference no.</dt>
                    <dd>{{ $paymentRequest->invoice_number }}</dd>
                    <dt>Payment method</dt>
                    <dd>{{ $paymentRequest->payment_method->label() }}</dd>
                    <dt>Requested payment date</dt>
                    <dd>{{ $paymentRequest->payment_date->format('d M Y') }}</dd>
                    <dt>Remarks</dt>
                    <dd class="pre">{{ $paymentRequest->remarks ?: '—' }}</dd>
                    <dt>Attachment</dt>
                    <dd>
                        @if ($paymentRequest->attachment_path)
                            <a href="{{ route('payment-requests.attachment', $paymentRequest) }}">{{ $paymentRequest->attachment_name }}</a>
                        @else
                            —
                        @endif
                    </dd>
                    <dt>Created by</dt>
                    <dd>{{ $paymentRequest->requester->name }} <span class="meta">({{ $paymentRequest->requester->email }})</span></dd>
                </dl>
            </section>

            @can('review', $paymentRequest)
                <section class="card">
                    <h2>Review</h2>
                    <div class="grid-2" style="grid-template-columns: 1fr 1fr">
                        <form method="POST" action="{{ route('payment-requests.approve', $paymentRequest) }}"
                              onsubmit="return confirm('Approve {{ $paymentRequest->reference }} for RM {{ number_format($paymentRequest->amount, 2) }}?')">
                            @csrf
                            <input type="hidden" name="version" value="{{ $version }}">
                            <p class="meta" style="margin-top:0">Approve this payment for RM {{ number_format($paymentRequest->amount, 2) }} to {{ $paymentRequest->vendor_name }}.</p>
                            <button type="submit" class="btn btn-success btn-block">Approve</button>
                        </form>

                        <form method="POST" action="{{ route('payment-requests.reject', $paymentRequest) }}">
                            @csrf
                            <input type="hidden" name="version" value="{{ $version }}">
                            <div class="field">
                                <label for="rejection_reason">Reason for rejection <span class="req">*</span></label>
                                <textarea id="rejection_reason" name="rejection_reason" required minlength="5" maxlength="1000" rows="3" @class(['is-invalid' => $errors->reject->has('rejection_reason')])>{{ old('rejection_reason') }}</textarea>
                                @error('rejection_reason', 'reject') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-danger btn-block" style="margin-top:.5rem">Reject</button>
                        </form>
                    </div>
                </section>
            @endcan
        </div>

        <aside class="card">
            <h2>Audit trail</h2>
            <ul class="timeline">
                @foreach ($paymentRequest->activities as $activity)
                    <li class="{{ $activity->action->value }}">
                        <div><strong>{{ $activity->action->label() }}</strong> by {{ $activity->user->name }}
                            <span class="meta">({{ $activity->user->role->label() }})</span></div>
                        <div class="when" title="{{ $activity->created_at->toIso8601String() }}">
                            {{ $activity->created_at->format('d M Y, g:i:s A') }} · {{ $activity->created_at->diffForHumans() }}
                        </div>
                        @if ($activity->changes)
                            <ul class="changes">
                                @foreach ($activity->changes as $field => $change)
                                    <li>
                                        {{ \App\Models\PaymentRequest::AUDITED_FIELDS[$field] ?? $field }}:
                                        <del>{{ $change['old'] ?? '—' }}</del> &rarr; {{ $change['new'] ?? '—' }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($activity->notes)
                            <div class="changes pre">{{ $activity->notes }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </aside>
    </div>
@endsection
