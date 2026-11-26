<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequest>
 */
class PaymentRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vendor_name' => fake()->company(),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'amount' => fake()->randomFloat(2, 50, 50000),
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'payment_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'remarks' => fake()->optional()->sentence(),
            'status' => PaymentStatus::Pending,
        ];
    }

    public function approved(?User $reviewer = null): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Approved,
            'reviewed_by' => $reviewer?->id ?? User::factory()->approver(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(?User $reviewer = null, string $reason = 'Invoice details do not match the purchase order.'): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Rejected,
            'reviewed_by' => $reviewer?->id ?? User::factory()->approver(),
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
