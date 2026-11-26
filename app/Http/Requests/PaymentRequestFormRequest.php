<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Validation for creating and editing a payment request.
 * Authorisation is handled by the route's `can:` middleware.
 */
class PaymentRequestFormRequest extends FormRequest
{
    public const MAX_AMOUNT = 999_999_999.99;

    public const MAX_UPLOAD_KB = 5 * 1024;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'vendor_name' => is_string($this->vendor_name) ? trim(preg_replace('/\s+/', ' ', $this->vendor_name)) : $this->vendor_name,
            'invoice_number' => is_string($this->invoice_number) ? strtoupper(trim($this->invoice_number)) : $this->invoice_number,
            'amount' => is_string($this->amount) ? str_replace([',', ' '], '', $this->amount) : $this->amount,
        ]);
    }

    public function rules(): array
    {
        /** @var PaymentRequest|null $existing */
        $existing = $this->route('paymentRequest');

        return [
            'vendor_name' => ['required', 'string', 'min:2', 'max:255'],
            'invoice_number' => [
                'required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-\/\.# ]*$/',
                // Guard against paying the same invoice twice. A rejected request
                // may be resubmitted, so rejected rows don't count.
                Rule::unique('payment_requests', 'invoice_number')
                    ->where('vendor_name', $this->input('vendor_name'))
                    ->where(fn ($q) => $q->where('status', '!=', PaymentStatus::Rejected->value))
                    ->ignore($existing?->id),
            ],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:'.self::MAX_AMOUNT],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(self::MAX_UPLOAD_KB)],
            'remove_attachment' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'invoice_number' => 'invoice / reference number',
            'payment_date' => 'requested payment date',
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.unique' => 'A request for this invoice number from this vendor already exists.',
            'invoice_number.regex' => 'The invoice / reference number may only contain letters, numbers, spaces and - / . #',
            'amount.max' => 'The amount may not exceed RM '.number_format(self::MAX_AMOUNT, 2).'.',
            'payment_date.after_or_equal' => 'The requested payment date cannot be in the past.',
        ];
    }

    /** The model attributes only; files and flags are handled separately. */
    public function requestData(): array
    {
        return $this->safe()->only(['vendor_name', 'invoice_number', 'amount', 'payment_method', 'payment_date', 'remarks']);
    }
}
