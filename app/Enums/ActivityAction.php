<?php

namespace App\Enums;

enum ActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
