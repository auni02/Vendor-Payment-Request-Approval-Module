<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentRequest extends Model
{
    /** @use HasFactory<PaymentRequestFactory> */
    use HasFactory;

    /**
     * Only the fields a requester may supply. Ownership, status and review
     * fields are set explicitly in code, never mass-assigned from input.
     */
    protected $fillable = [
        'vendor_name',
        'invoice_number',
        'amount',
        'payment_method',
        'payment_date',
        'remarks',
    ];

    /** Fields tracked in the audit trail when a request is edited. */
    public const AUDITED_FIELDS = [
        'vendor_name' => 'Vendor name',
        'invoice_number' => 'Invoice / reference no.',
        'amount' => 'Amount',
        'payment_method' => 'Payment method',
        'payment_date' => 'Requested payment date',
        'remarks' => 'Remarks',
        'attachment_name' => 'Attachment',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(PaymentRequestActivity::class)->latest('created_at')->latest('id');
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    /** Human-friendly reference, e.g. PR-00042. */
    public function getReferenceAttribute(): string
    {
        return 'PR-'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function logActivity(User $user, ActivityAction $action, ?array $changes = null, ?string $notes = null): PaymentRequestActivity
    {
        return $this->activities()->create([
            'user_id' => $user->id,
            'action' => $action,
            'changes' => $changes ?: null,
            'notes' => $notes,
        ]);
    }

    /** Restrict the query to what the given user is allowed to see. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isApprover() ? $query : $query->where('user_id', $user->id);
    }

    /**
     * Apply listing filters: free-text search on vendor / invoice number,
     * status, and an inclusive date range on the chosen date column.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $column = ($filters['date_field'] ?? null) === 'payment_date' ? 'payment_date' : 'created_at';

        return $query
            ->when($filters['q'] ?? null, function (Builder $q, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn (Builder $w) => $w
                    ->where('vendor_name', 'like', $like)
                    ->orWhere('invoice_number', 'like', $like));
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $from) => $q->whereDate($column, '>=', $from))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $to) => $q->whereDate($column, '<=', $to));
    }
}
