<?php

namespace App\Enums;

enum Role: string
{
    case Staff = 'staff';
    case Approver = 'approver';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Approver => 'Approver / Admin',
        };
    }
}
