@php
    /** @var \App\Models\PaymentRequest $paymentRequest */
    $method = old('payment_method', $paymentRequest->payment_method?->value);
    $amount = old('amount', $paymentRequest->amount);
@endphp

@if ($errors->any())
    <div class="alert alert-error" role="alert">Please correct the highlighted fields below.</div>
@endif

<div class="form-grid">
    <div class="field">
        <label for="vendor_name">Vendor name <span class="req">*</span></label>
        <input type="text" id="vendor_name" name="vendor_name" value="{{ old('vendor_name', $paymentRequest->vendor_name) }}" required maxlength="255" @class(['is-invalid' => $errors->has('vendor_name')])>
        @error('vendor_name') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="invoice_number">Invoice / reference no. <span class="req">*</span></label>
        <input type="text" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $paymentRequest->invoice_number) }}" required maxlength="100" @class(['is-invalid' => $errors->has('invoice_number')])>
        @error('invoice_number') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="amount">Amount <span class="req">*</span></label>
        <div class="input-prefix">
            <span>RM</span>
            <input type="number" id="amount" name="amount" value="{{ $amount }}" required min="0.01" max="{{ \App\Http\Requests\PaymentRequestFormRequest::MAX_AMOUNT }}" step="0.01" inputmode="decimal" @class(['is-invalid' => $errors->has('amount')])>
        </div>
        @error('amount') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="payment_method">Payment method <span class="req">*</span></label>
        <select id="payment_method" name="payment_method" required @class(['is-invalid' => $errors->has('payment_method')])>
            @foreach ($methods as $option)
                <option value="{{ $option->value }}" @selected($method === $option->value)>{{ $option->label() }}</option>
            @endforeach
        </select>
        @error('payment_method') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="payment_date">Requested payment date <span class="req">*</span></label>
        <input type="date" id="payment_date" name="payment_date" value="{{ old('payment_date', $paymentRequest->payment_date?->format('Y-m-d')) }}" min="{{ now()->toDateString() }}" required @class(['is-invalid' => $errors->has('payment_date')])>
        @error('payment_date') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="attachment">Invoice attachment <span class="hint">(optional)</span></label>
        <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" @class(['is-invalid' => $errors->has('attachment')])>
        <div class="hint">PDF, JPG or PNG, up to {{ \App\Http\Requests\PaymentRequestFormRequest::MAX_UPLOAD_KB / 1024 }} MB.</div>
        @error('attachment') <div class="error">{{ $message }}</div> @enderror

        @if ($paymentRequest->attachment_path)
            <div class="hint" style="margin-top:.5rem">
                Current file: <a href="{{ route('payment-requests.attachment', $paymentRequest) }}">{{ $paymentRequest->attachment_name }}</a>
                — uploading a new file replaces it.
            </div>
            <label class="checkbox" style="margin-top:.35rem">
                <input type="checkbox" name="remove_attachment" value="1" @checked(old('remove_attachment'))> Remove current attachment
            </label>
        @endif
    </div>

    <div class="field full">
        <label for="remarks">Remarks</label>
        <textarea id="remarks" name="remarks" maxlength="2000" placeholder="Purpose of payment, PO number, cost centre…" @class(['is-invalid' => $errors->has('remarks')])>{{ old('remarks', $paymentRequest->remarks) }}</textarea>
        @error('remarks') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>
