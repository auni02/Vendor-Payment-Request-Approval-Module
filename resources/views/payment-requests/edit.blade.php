@extends('layouts.app')

@section('title', 'Edit '.$paymentRequest->reference)

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Pending · editable</span>
            <h1>Edit {{ $paymentRequest->reference }}</h1>
            <p>Changes are recorded in the audit trail.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('payment-requests.update', $paymentRequest) }}" enctype="multipart/form-data" class="card">
        @csrf
        @method('PUT')
        <h2>Request details</h2>
        @include('payment-requests._form')
        <div class="form-actions">
            <a href="{{ route('payment-requests.show', $paymentRequest) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
    </form>
@endsection
