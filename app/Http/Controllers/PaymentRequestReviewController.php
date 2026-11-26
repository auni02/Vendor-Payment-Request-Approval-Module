<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectPaymentRequestRequest;
use App\Models\PaymentRequest;
use App\Services\PaymentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Approve / reject actions. Route middleware ensures the user can('review'). */
class PaymentRequestReviewController extends Controller
{
    public function __construct(private readonly PaymentRequestService $service) {}

    public function approve(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->service->approve($request->user(), $paymentRequest, $request->string('version')->toString() ?: null);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', "{$paymentRequest->reference} approved.");
    }

    public function reject(RejectPaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $this->service->reject(
            $request->user(),
            $paymentRequest,
            trim($request->validated('rejection_reason')),
            $request->validated('version') ?: null,
        );

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', "{$paymentRequest->reference} rejected.");
    }
}
