<?php

namespace App\Policies;

use App\Models\PaymentRequest;
use App\Models\User;

/**
 * Single source of truth for who may do what with a payment request.
 * Used by routes (can: middleware), controllers and Blade (@can).
 */
class PaymentRequestPolicy
{
    /** Staff see their own requests and approvers see all; queries are scoped by PaymentRequest::scopeVisibleTo(). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PaymentRequest $request): bool
    {
        return $user->isApprover() || $request->user_id === $user->id;
    }

    /** Only staff raise requests, which keeps requesting and approving separate. */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /** The owner may edit only while the request is still pending. */
    public function update(User $user, PaymentRequest $request): bool
    {
        return $request->user_id === $user->id && $request->isPending();
    }

    public function review(User $user, PaymentRequest $request): bool
    {
        return $user->isApprover()
            && $request->isPending()
            && $request->user_id !== $user->id; // never approve your own request
    }
}
