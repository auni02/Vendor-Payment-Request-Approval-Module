<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * `role` is deliberately not mass-assignable so it can never be set from request input.
     */
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function isApprover(): bool
    {
        return $this->role === Role::Approver;
    }

    public function isStaff(): bool
    {
        return $this->role === Role::Staff;
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }
}
