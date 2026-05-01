<?php

namespace App\Domain\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Requester = 'requester';
    case Approver = 'approver';

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
