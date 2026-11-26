<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts. All share the password "password" — for local/demo use only.
 */
class UserSeeder extends Seeder
{
    public const USERS = [
        ['name' => 'Aina Approver', 'email' => 'approver@example.com', 'role' => Role::Approver],
        ['name' => 'Sara Staff', 'email' => 'staff@example.com', 'role' => Role::Staff],
        ['name' => 'Daniel Staff', 'email' => 'staff2@example.com', 'role' => Role::Staff],
    ];

    public function run(): void
    {
        foreach (self::USERS as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);
            $user->forceFill([
                'name' => $data['name'],
                'password' => 'password',
                'role' => $data['role'],
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
