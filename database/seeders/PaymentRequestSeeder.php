<?php

namespace Database\Seeders;

use App\Enums\ActivityAction;
use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Sample requests in every status, with a matching audit trail,
 * so the dashboard has something to show straight after seeding.
 */
class PaymentRequestSeeder extends Seeder
{
    public function run(): void
    {
        if (PaymentRequest::exists()) {
            return; // keep the seeder safe to re-run
        }

        $approver = User::where('email', 'approver@example.com')->firstOrFail();
        $sara = User::where('email', 'staff@example.com')->firstOrFail();
        $daniel = User::where('email', 'staff2@example.com')->firstOrFail();

        $rows = [
            [$sara, 'Tenaga Nasional Berhad', 'TNB-2026-11-0381', 4820.55, 'bank_transfer', -1, 'November electricity bill – HQ.', 'approved', 12],
            [$sara, 'Syarikat Air Selangor', 'SAS-778120', 612.40, 'duitnow', 3, null, 'approved', 9],
            [$sara, 'Office Hub Supplies Sdn Bhd', 'OH-INV-20931', 1275.00, 'bank_transfer', 5, 'Stationery and printer toner for Q4.', 'rejected', 6],
            [$sara, 'Klang Valley Movers', 'KVM-00412', 3500.00, 'cheque', 6, 'Office relocation – level 3 to level 5.', 'pending', 3],
            [$sara, 'CloudNest Hosting', 'CN-2026-1187', 899.00, 'bank_transfer', 12, 'Annual VPS renewal.', 'pending', 1],
            [$daniel, 'Maju Catering Services', 'MCS-5521', 2140.00, 'duitnow', 2, 'Town hall lunch, 85 pax.', 'approved', 7],
            [$daniel, 'Prime Security Sdn Bhd', 'PS-11-2026', 7600.00, 'bank_transfer', 4, 'Guard services for November.', 'pending', 4],
            [$daniel, 'Lim & Co. Printing', 'LCP-3390', 455.80, 'cheque', 9, 'Business cards – 6 staff.', 'rejected', 2],
            [$daniel, 'Telekom Malaysia', 'TM-88213004', 389.00, 'duitnow', 15, null, 'pending', 0],
        ];

        foreach ($rows as [$owner, $vendor, $invoice, $amount, $method, $paymentInDays, $remarks, $status, $daysAgo]) {
            $createdAt = Carbon::now()->subDays($daysAgo)->setTime(9, 15);

            $request = new PaymentRequest([
                'vendor_name' => $vendor,
                'invoice_number' => $invoice,
                'amount' => $amount,
                'payment_method' => $method,
                'payment_date' => Carbon::today()->addDays($paymentInDays)->toDateString(),
                'remarks' => $remarks,
            ]);
            $request->user_id = $owner->id;
            $request->created_at = $createdAt;
            $request->updated_at = $createdAt;
            $request->save();

            $request->activities()->forceCreate([
                'user_id' => $owner->id, 'action' => ActivityAction::Created, 'created_at' => $createdAt,
            ]);

            if ($status === 'pending') {
                continue;
            }

            $reviewedAt = $createdAt->copy()->addDay()->setTime(11, 40);
            $reason = $status === 'rejected' ? 'Quotation not attached; please resubmit with the approved quotation.' : null;

            $request->forceFill([
                'status' => PaymentStatus::from($status),
                'reviewed_by' => $approver->id,
                'reviewed_at' => $reviewedAt,
                'rejection_reason' => $reason,
                'updated_at' => $reviewedAt,
            ])->save();

            $request->activities()->forceCreate([
                'user_id' => $approver->id,
                'action' => ActivityAction::from($status),
                'notes' => $reason,
                'created_at' => $reviewedAt,
            ]);
        }
    }
}
