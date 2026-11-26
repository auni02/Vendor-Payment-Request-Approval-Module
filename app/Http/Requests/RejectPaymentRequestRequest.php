<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequestRequest extends FormRequest
{
    protected $errorBag = 'reject';

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
            'version' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['rejection_reason' => 'reason for rejection'];
    }
}
