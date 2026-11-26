<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Workflow operations for payment requests. Every state change is written
 * together with its audit entry inside one transaction, so the trail can
 * never miss an action that actually happened.
 */
class PaymentRequestService
{
    public const DISK = 'attachments';

    public function create(User $user, array $data, ?UploadedFile $file): PaymentRequest
    {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($user, $data, $file, &$storedPath) {
                $request = new PaymentRequest($data);
                $request->user_id = $user->id;
                $request->status = PaymentStatus::Pending;
                $request->save();

                if ($file) {
                    $storedPath = $this->storeAttachment($request, $file);
                    $request->save();
                }

                $request->logActivity($user, ActivityAction::Created);

                return $request;
            });
        } catch (\Throwable $e) {
            $this->deleteFile($storedPath); // don't leave orphaned files behind
            throw $e;
        }
    }

    /**
     * @return bool false when nothing actually changed
     */
    public function update(User $user, PaymentRequest $request, array $data, ?UploadedFile $file, bool $removeAttachment): bool
    {
        $storedPath = null;
        $replacedPath = null;

        try {
            $changed = DB::transaction(function () use ($user, $request, $data, $file, $removeAttachment, &$storedPath, &$replacedPath) {
                $this->lockPending($request);

                $before = $this->auditSnapshot($request);
                $request->fill($data);

                if ($file || $removeAttachment) {
                    $replacedPath = $request->attachment_path;
                    $request->attachment_path = null;
                    $request->attachment_name = null;
                }
                if ($file) {
                    $storedPath = $this->storeAttachment($request, $file);
                }

                $changes = $this->diff($before, $this->auditSnapshot($request));
                if ($file && $replacedPath && ! isset($changes['attachment_name'])) {
                    // same file name re-uploaded: still worth recording
                    $changes['attachment_name'] = ['old' => $before['attachment_name'], 'new' => $request->attachment_name.' (replaced)'];
                }

                if ($changes === []) {
                    return false;
                }

                $request->save();
                $request->logActivity($user, ActivityAction::Updated, $changes);

                return true;
            });
        } catch (\Throwable $e) {
            $this->deleteFile($storedPath);
            throw $e;
        }

        if ($changed) {
            $this->deleteFile($replacedPath);
        } else {
            $this->deleteFile($storedPath);
        }

        return $changed;
    }

    public function approve(User $approver, PaymentRequest $request, ?string $expectedVersion): void
    {
        DB::transaction(function () use ($approver, $request, $expectedVersion) {
            $this->lockPending($request, $expectedVersion);

            $request->forceFill([
                'status' => PaymentStatus::Approved,
                'reviewed_by' => $approver->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();

            $request->logActivity($approver, ActivityAction::Approved);
        });
    }

    public function reject(User $approver, PaymentRequest $request, string $reason, ?string $expectedVersion): void
    {
        DB::transaction(function () use ($approver, $request, $reason, $expectedVersion) {
            $this->lockPending($request, $expectedVersion);

            $request->forceFill([
                'status' => PaymentStatus::Rejected,
                'reviewed_by' => $approver->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $request->logActivity($approver, ActivityAction::Rejected, null, $reason);
        });
    }

    /** Token that changes whenever the request is edited; used to stop approving a stale view. */
    public static function versionOf(PaymentRequest $request): string
    {
        return (string) $request->updated_at?->getTimestamp();
    }

    /**
     * Re-read the row under a lock and make sure it is still pending (and, for
     * reviews, unchanged since the approver loaded it). Guards against two
     * approvers acting at once, or an edit landing just before approval.
     */
    private function lockPending(PaymentRequest $request, ?string $expectedVersion = null): void
    {
        $fresh = PaymentRequest::whereKey($request->getKey())->lockForUpdate()->firstOrFail();

        if (! $fresh->isPending()) {
            throw ValidationException::withMessages([
                'status' => "This request has already been {$fresh->status->value} and can no longer be changed.",
            ]);
        }

        if ($expectedVersion !== null && $expectedVersion !== self::versionOf($fresh)) {
            throw ValidationException::withMessages([
                'status' => 'This request was edited while you were viewing it. Please review the latest details and try again.',
            ]);
        }

        $request->setRawAttributes($fresh->getAttributes(), true);
    }

    private function storeAttachment(PaymentRequest $request, UploadedFile $file): string
    {
        // Random file name with an extension derived from the detected MIME type;
        // the user's original name is kept only as display metadata.
        $path = $file->store('payment-requests/'.$request->id, self::DISK);

        if ($path === false) {
            throw new \RuntimeException('Could not store the attachment.');
        }

        $name = Str::limit(basename($file->getClientOriginalName()), 200, '');
        $request->attachment_path = $path;
        $request->attachment_name = $name !== '' ? $name : basename($path);

        return $path;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** Comparable, display-ready values of the audited fields. */
    private function auditSnapshot(PaymentRequest $request): array
    {
        $snapshot = [];
        foreach (array_keys(PaymentRequest::AUDITED_FIELDS) as $field) {
            $value = $request->{$field};
            $snapshot[$field] = match (true) {
                $value instanceof \BackedEnum && method_exists($value, 'label') => $value->label(),
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                $field === 'amount' && $value !== null => number_format((float) $value, 2, '.', ''),
                $value === '' => null,
                default => $value,
            };
        }

        return $snapshot;
    }

    private function diff(array $before, array $after): array
    {
        $changes = [];
        foreach ($after as $field => $new) {
            if ($before[$field] !== $new) {
                $changes[$field] = ['old' => $before[$field], 'new' => $new];
            }
        }

        return $changes;
    }
}
