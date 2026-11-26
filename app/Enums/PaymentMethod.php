<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BankTransfer = 'bank_transfer';
    case DuitNow = 'duitnow';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank Transfer',
            self::DuitNow => 'DuitNow',
            self::Cheque => 'Cheque',
        };
    }
}
