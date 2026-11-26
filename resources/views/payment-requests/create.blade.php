@extends('layouts.app')

@section('title', 'New payment request')

@section('content')
    <div class="page-head">
        <div>
            <h1>New payment request</h1>
            <p>Submitted requests are reviewed by an approver. You can edit a request until it is approved or rejected.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('payment-requests.store') }}" enctype="multipart/form-data" class="card">
        @csrf
        @include('payment-requests._form')
        <div class="form-actions">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Submit request</button>
        </div>
    </form>
@endsection
