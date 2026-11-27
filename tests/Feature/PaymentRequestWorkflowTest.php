<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'vendor_name' => 'Tenaga Nasional Berhad',
            'invoice_number' => 'tnb-2026-001',
            'amount' => '1,234.50',
            'payment_method' => 'duitnow',
            'payment_date' => now()->addWeek()->toDateString(),
            'remarks' => 'Electricity bill',
        ], $overrides);
    }

    public function test_staff_can_submit_a_request_with_attachment(): void
    {
        Storage::fake('attachments');
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post('/payment-requests', $this->validData([
            'attachment' => UploadedFile::fake()->create('invoice.pdf', 200, 'application/pdf'),
        ]));

        $request = PaymentRequest::sole();
        $response->assertRedirect(route('payment-requests.show', $request));

        $this->assertSame(PaymentStatus::Pending, $request->status);
        $this->assertSame($staff->id, $request->user_id);
        $this->assertSame('1234.50', $request->amount);
        $this->assertSame('TNB-2026-001', $request->invoice_number);
        $this->assertSame('invoice.pdf', $request->attachment_name);
        Storage::disk('attachments')->assertExists($request->attachment_path);

        $this->assertSame([ActivityAction::Created], $request->activities->pluck('action')->all());
        $this->assertSame($staff->id, $request->activities->first()->user_id);
    }

    public function test_status_and_owner_cannot_be_mass_assigned(): void
    {
        $staff = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($staff)->post('/payment-requests', $this->validData([
            'status' => 'approved', 'user_id' => $other->id, 'reviewed_by' => $other->id,
        ]));

        $request = PaymentRequest::sole();
        $this->assertSame(PaymentStatus::Pending, $request->status);
        $this->assertSame($staff->id, $request->user_id);
        $this->assertNull($request->reviewed_by);
    }

    public function test_validation_rules(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)->post('/payment-requests', [
            'vendor_name' => '',
            'invoice_number' => '<script>',
            'amount' => '-5',
            'payment_method' => 'paypal',
            'payment_date' => now()->subDay()->toDateString(),
            'remarks' => str_repeat('x', 2001),
            'attachment' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors([
            'vendor_name', 'invoice_number', 'amount', 'payment_method', 'payment_date', 'remarks', 'attachment',
        ]);

        $this->actingAs($staff)->post('/payment-requests', $this->validData(['amount' => '10.555']))
            ->assertSessionHasErrors('amount');
        $this->actingAs($staff)->post('/payment-requests', $this->validData([
            'attachment' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
        ]))->assertSessionHasErrors('attachment');

        $this->assertSame(0, PaymentRequest::count());
    }

    public function test_duplicate_invoice_for_same_vendor_is_blocked_unless_rejected(): void
    {
        $staff = User::factory()->create();
        $existing = PaymentRequest::factory()->create(['vendor_name' => 'Tenaga Nasional Berhad', 'invoice_number' => 'TNB-2026-001']);

        $this->actingAs($staff)->post('/payment-requests', $this->validData())->assertSessionHasErrors('invoice_number');
        $this->actingAs($staff)->post('/payment-requests', $this->validData(['vendor_name' => 'Another vendor']))->assertSessionHasNoErrors();

        $existing->forceFill(['status' => PaymentStatus::Rejected])->save();
        $this->actingAs($staff)->post('/payment-requests', $this->validData())->assertSessionHasNoErrors();
    }

    public function test_owner_can_edit_pending_request_and_changes_are_audited(): void
    {
        $staff = User::factory()->create();
        $request = PaymentRequest::factory()->for($staff, 'requester')->create(['amount' => 100, 'vendor_name' => 'Old Vendor', 'invoice_number' => 'A-1']);

        $this->actingAs($staff)->get(route('payment-requests.edit', $request))->assertOk();
        $this->actingAs($staff)->put(route('payment-requests.update', $request), $this->validData([
            'vendor_name' => 'Old Vendor', 'invoice_number' => 'A-1', 'amount' => '150.00',
            'payment_method' => $request->payment_method->value,
            'payment_date' => $request->payment_date->toDateString(),
            'remarks' => $request->remarks,
        ]))->assertRedirect(route('payment-requests.show', $request));

        $activity = $request->activities()->first();
        $this->assertSame(ActivityAction::Updated, $activity->action);
        $this->assertSame(['amount' => ['old' => '100.00', 'new' => '150.00']], $activity->changes);
        $this->assertSame('150.00', $request->fresh()->amount);
    }

    public function test_saving_without_changes_does_not_create_audit_noise(): void
    {
        $staff = User::factory()->create();
        $request = PaymentRequest::factory()->for($staff, 'requester')->create(['remarks' => null]);

        $this->actingAs($staff)->put(route('payment-requests.update', $request), [
            'vendor_name' => $request->vendor_name,
            'invoice_number' => $request->invoice_number,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method->value,
            'payment_date' => $request->payment_date->toDateString(),
            'remarks' => '',
        ])->assertSessionHas('info', 'No changes were made.');

        $this->assertSame(0, $request->activities()->count());
    }

    public function test_staff_cannot_edit_someone_elses_request(): void
    {
        $request = PaymentRequest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('payment-requests.update', $request), $this->validData())
            ->assertForbidden();
    }

    public function test_staff_cannot_edit_after_approval_or_rejection(): void
    {
        $staff = User::factory()->create();
        $approved = PaymentRequest::factory()->for($staff, 'requester')->approved()->create();
        $rejected = PaymentRequest::factory()->for($staff, 'requester')->rejected()->create();

        foreach ([$approved, $rejected] as $request) {
            $this->actingAs($staff)->get(route('payment-requests.edit', $request))->assertForbidden();
            $this->actingAs($staff)->put(route('payment-requests.update', $request), $this->validData())->assertForbidden();
            $this->actingAs($staff)->get(route('payment-requests.show', $request))->assertOk()->assertDontSee('>Edit</a>', false);
        }
    }

    public function test_approver_can_approve_pending_request(): void
    {
        $approver = User::factory()->approver()->create();
        $request = PaymentRequest::factory()->create();

        $this->actingAs($approver)->post(route('payment-requests.approve', $request))
            ->assertRedirect(route('payment-requests.show', $request));

        $request->refresh();
        $this->assertSame(PaymentStatus::Approved, $request->status);
        $this->assertSame($approver->id, $request->reviewed_by);
        $this->assertNotNull($request->reviewed_at);

        $activity = $request->activities()->first();
        $this->assertSame(ActivityAction::Approved, $activity->action);
        $this->assertSame($approver->id, $activity->user_id);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $approver = User::factory()->approver()->create();
        $request = PaymentRequest::factory()->create();

        $this->actingAs($approver)->post(route('payment-requests.reject', $request), ['rejection_reason' => '  '])
            ->assertSessionHasErrorsIn('reject', 'rejection_reason');
        $this->assertTrue($request->fresh()->isPending());

        $this->actingAs($approver)->post(route('payment-requests.reject', $request), ['rejection_reason' => 'Missing quotation'])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(PaymentStatus::Rejected, $request->status);
        $this->assertSame('Missing quotation', $request->rejection_reason);
        $this->assertSame('Missing quotation', $request->activities()->first()->notes);
    }

    public function test_finalised_request_cannot_be_reviewed_again(): void
    {
        $approver = User::factory()->approver()->create();
        $request = PaymentRequest::factory()->approved()->create();

        $this->actingAs($approver)->post(route('payment-requests.reject', $request), ['rejection_reason' => 'Changed my mind'])
            ->assertForbidden();
        $this->assertSame(PaymentStatus::Approved, $request->fresh()->status);
    }

    public function test_approval_is_refused_if_request_changed_since_it_was_viewed(): void
    {
        $approver = User::factory()->approver()->create();
        $request = PaymentRequest::factory()->create();
        $staleVersion = (string) $request->updated_at->getTimestamp();

        $this->travel(5)->seconds();
        $request->forceFill(['amount' => 99999])->save(); // edited after the approver loaded the page

        $this->actingAs($approver)->post(route('payment-requests.approve', $request), ['version' => $staleVersion])
            ->assertSessionHasErrors('status');
        $this->assertTrue($request->fresh()->isPending());
    }

    public function test_audit_trail_shows_actor_and_time(): void
    {
        $staff = User::factory()->create(['name' => 'Sara Staff']);
        $approver = User::factory()->approver()->create(['name' => 'Aina Approver']);

        $this->actingAs($staff)->post('/payment-requests', $this->validData());
        $request = PaymentRequest::sole();
        $this->actingAs($approver)->post(route('payment-requests.approve', $request));

        $this->actingAs($staff)->get(route('payment-requests.show', $request))
            ->assertOk()
            ->assertSeeInOrder(['Audit trail', 'Aina Approver', 'Approved', 'Sara Staff', 'Created']);
    }
}
