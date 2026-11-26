<?php

namespace Tests\Feature;

use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRequestAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_see_their_own_requests_on_the_dashboard(): void
    {
        $sara = User::factory()->create();
        $daniel = User::factory()->create();
        PaymentRequest::factory()->for($sara, 'requester')->create(['vendor_name' => 'Sara Vendor']);
        PaymentRequest::factory()->for($daniel, 'requester')->create(['vendor_name' => 'Daniel Vendor']);

        $this->actingAs($sara)->get('/dashboard')
            ->assertOk()
            ->assertSee('Sara Vendor')
            ->assertDontSee('Daniel Vendor');
    }

    public function test_staff_cannot_view_another_users_request(): void
    {
        $other = PaymentRequest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('payment-requests.show', $other))
            ->assertForbidden();
    }

    public function test_approver_sees_all_requests(): void
    {
        PaymentRequest::factory()->create(['vendor_name' => 'Alpha Sdn Bhd']);
        PaymentRequest::factory()->create(['vendor_name' => 'Beta Enterprise']);
        $approver = User::factory()->approver()->create();

        $this->actingAs($approver)->get('/dashboard')
            ->assertOk()->assertSee('Alpha Sdn Bhd')->assertSee('Beta Enterprise');
    }

    public function test_approver_cannot_create_requests(): void
    {
        $approver = User::factory()->approver()->create();

        $this->actingAs($approver)->get('/payment-requests/create')->assertForbidden();
        $this->actingAs($approver)->post('/payment-requests', [])->assertForbidden();
    }

    public function test_staff_cannot_approve_or_reject(): void
    {
        $staff = User::factory()->create();
        $own = PaymentRequest::factory()->for($staff, 'requester')->create();

        $this->actingAs($staff)->post(route('payment-requests.approve', $own))->assertForbidden();
        $this->actingAs($staff)->post(route('payment-requests.reject', $own), ['rejection_reason' => 'Not needed'])->assertForbidden();
        $this->assertTrue($own->fresh()->isPending());
    }

    public function test_dashboard_totals_are_scoped_and_correct(): void
    {
        $staff = User::factory()->create();
        PaymentRequest::factory()->for($staff, 'requester')->count(2)->create(['amount' => 100]);
        PaymentRequest::factory()->for($staff, 'requester')->approved()->create(['amount' => 250]);
        PaymentRequest::factory()->count(3)->rejected()->create(); // someone else's

        $response = $this->actingAs($staff)->get('/dashboard')->assertOk();
        $stats = $response->viewData('stats');

        $this->assertSame(2, $stats['pending']['count']);
        $this->assertSame(200.0, $stats['pending']['amount']);
        $this->assertSame(1, $stats['approved']['count']);
        $this->assertSame(0, $stats['rejected']['count']);
    }

    public function test_search_and_filters(): void
    {
        $approver = User::factory()->approver()->create();
        PaymentRequest::factory()->create(['vendor_name' => 'Tenaga Nasional', 'invoice_number' => 'TNB-1', 'created_at' => '2026-10-01 10:00:00']);
        PaymentRequest::factory()->approved()->create(['vendor_name' => 'Air Selangor', 'invoice_number' => 'AS-77', 'created_at' => '2026-11-10 10:00:00']);
        PaymentRequest::factory()->rejected()->create(['vendor_name' => 'Maju Catering', 'invoice_number' => 'MC-5', 'created_at' => '2026-11-20 10:00:00']);

        $names = fn (array $query) => $this->actingAs($approver)->get(route('dashboard', $query))
            ->viewData('requests')->pluck('vendor_name')->sort()->values()->all();

        $this->assertSame(['Tenaga Nasional'], $names(['q' => 'tenaga']));
        $this->assertSame(['Air Selangor'], $names(['q' => 'AS-77']));
        $this->assertSame(['Maju Catering'], $names(['status' => 'rejected']));
        $this->assertSame(['Air Selangor', 'Maju Catering'], $names(['date_from' => '2026-11-01', 'date_to' => '2026-11-30']));
        $this->assertSame(['Air Selangor'], $names(['date_from' => '2026-11-10', 'date_to' => '2026-11-10']));
        $this->assertSame([], $names(['q' => '%'])); // wildcard is treated literally
    }

    public function test_invalid_filters_are_ignored_rather_than_erroring(): void
    {
        PaymentRequest::factory()->count(2)->create();

        $response = $this->actingAs(User::factory()->approver()->create())
            ->get('/dashboard?status=bogus&date_from=not-a-date')
            ->assertOk();

        $this->assertCount(2, $response->viewData('requests'));
        $this->assertTrue($response->viewData('filterErrors')->has('status'));
    }
}
