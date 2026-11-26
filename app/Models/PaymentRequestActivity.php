<?php

namespace App\Models;

use App\Enums\ActivityAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit record. There is no updated_at and the application
 * never edits or deletes these rows.
 */
class PaymentRequestActivity extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'changes', 'notes'];

    protected function casts(): array
    {
        return [
            'action' => ActivityAction::class,
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(PaymentRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
