@props(['method'])
@php
    $icon = match ($method) {
        \App\Enums\PaymentMethod::BankTransfer => 'bank',
        \App\Enums\PaymentMethod::DuitNow => 'phone',
        \App\Enums\PaymentMethod::Cheque => 'cheque',
    };
@endphp
<span class="file-icon {{ $method->value }}" title="{{ $method->label() }}"><x-icon :name="$icon" /></span>
